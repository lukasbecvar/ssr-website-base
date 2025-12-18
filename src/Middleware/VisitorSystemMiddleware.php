<?php

namespace App\Middleware;

use DateTime;
use Exception;
use App\Util\AppUtil;
use Twig\Environment;
use App\Entity\Visitor;
use App\Util\CacheUtil;
use App\Util\SecurityUtil;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use App\Manager\VisitorManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VisitorSystemMiddleware
 *
 * Middleware for save and update visitor information
 *
 * @package App\Middleware
 */
class VisitorSystemMiddleware
{
    private AppUtil $appUtil;
    private Environment $twig;
    private CacheUtil $cacheUtil;
    private BanManager $banManager;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private VisitorManager $visitorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        Environment $twig,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        BanManager $banManager,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
        VisitorManager $visitorManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->twig = $twig;
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->banManager = $banManager;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->visitorManager = $visitorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Handle visitor system functionality
     *
     * Save visitor information
     * Updates visitors statistics
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // get data to insert
        $date = new DateTime();
        $os = $this->visitorInfoUtil->getOS();
        $ipAddress = $this->visitorInfoUtil->getIP();
        $browser = $this->visitorInfoUtil->getUserAgent();

        // escape visitor ip address
        $ipAddress = $this->securityUtil->escapeString($ipAddress);

        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if visitor found in database
        if ($visitor == null) {
            // save new visitor data
            $this->insertNewVisitor($date, $ipAddress, $browser, $os);
        } else {
            // check if visitor banned
            if ($this->banManager->isVisitorBanned($ipAddress)) {
                $reason = $this->banManager->getBanReason($ipAddress);
                $this->logManager->log(
                    name: 'ban-system',
                    value: 'visitor with ip: ' . $ipAddress . ' trying to access page, but visitor banned for: ' . $reason
                );

                // render banned page
                die($this->twig->render('errors/error-banned.twig', [
                    'message' => $reason,
                    'contactEmail' => $_ENV['CONTACT_EMAIL']
                ]));
            } else {
                // update exist visitor
                $this->updateVisitor($date, $ipAddress, $browser, $os);
            }
        }
    }

    /**
     * Insert new visitor record into the database
     *
     * @param DateTime $date The date of the visit
     * @param string $ipAddress The IP address of the visitor
     * @param string $browser The browser used by the visitor
     * @param string $os The operating system of the visitor
     *
     * @return void
     */
    public function insertNewVisitor(DateTime $date, string $ipAddress, string $browser, string $os): void
    {
        // get visitor ip address
        $location = $this->visitorInfoUtil->getLocation($ipAddress);

        // get visitor referer
        $referer = $this->visitorInfoUtil->getReferer();
        if ($referer && str_contains($referer, $this->appUtil->getHttpHost())) {
            $referer = 'Unknown';
        }

        // log geolocate error
        if ($location['city'] == 'Unknown' || $location['country'] == 'Unknown') {
            $this->logManager->log('geolocate-error', 'error to geolocate ip: ' . $ipAddress);
        }

        // prevent maximal user agent length
        if (strlen($browser) >= 200) {
            $browser = substr($browser, 0, 197) . "...";
        }

        // get http host
        $currentHttpHost = $this->appUtil->getHttpHost();

        // create new visitor entity
        $visitorEntity = new Visitor();
        $visitorEntity->setFirstVisit($date)
            ->setLastVisit($date)
            ->setFirstVisitSite($currentHttpHost)
            ->setBrowser($browser)
            ->setOs($os)
            ->setReferer($referer)
            ->setCity($location['city'])
            ->setCountry($location['country'])
            ->setIpAddress($ipAddress)
            ->setBannedStatus(false)
            ->setBanReason('non-banned')
            ->setBannedTime(null)
            ->setEmail('Unknown');

        try {
            // flush new visitor to database
            $this->entityManager->persist($visitorEntity);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->logError(
                msg: 'flush error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update existing visitor record in the database
     *
     * @param DateTime $date The date of the visit
     * @param string $ipAddress The IP address of the visitor
     * @param string $browser The updated browser used by the visitor
     * @param string $os The updated operating system of the visitor
     *
     * @return void
     */
    public function updateVisitor(DateTime $date, string $ipAddress, string $browser, string $os): void
    {
        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // prevent maximal useragent to save
        if (strlen($browser) >= 200) {
            $browser = substr($browser, 0, 197) . "...";
        }

        // check if visitor data found
        if (!$visitor != null) {
            $this->errorManager->logError(
                msg: 'unexpected visitor with ip: ' . $ipAddress . ' update error, please check database structure',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } else {
            // update visitor data
            $visitor->setLastVisit($date);
            $visitor->setBrowser($browser);
            $visitor->setOs($os);

            // update visitor referer (only if referer not in current host domain)
            $referer = $this->visitorInfoUtil->getReferer();
            if ($visitor->getReferer() == 'Unknown' && !str_contains($referer, $this->appUtil->getHttpHost())) {
                $visitor->setReferer($referer);
            }

            try {
                // flush updated visitor data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->logError(
                    msg: 'flush error: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // cache visitor to online list
        $this->cacheUtil->setValue('online_user_' . $visitor->getId(), 'online', 10);
    }
}
