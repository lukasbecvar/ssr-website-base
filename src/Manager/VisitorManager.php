<?php

namespace App\Manager;

use Exception;
use App\Util\AppUtil;
use App\Entity\Visitor;
use App\Util\CacheUtil;
use App\Util\VisitorInfoUtil;
use App\Repository\VisitorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Visitor manager provides methods for managing visitors
 *
 * @package App\Manager
 */
class VisitorManager
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private VisitorRepository $visitorRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ErrorManager $errorManager,
        VisitorInfoUtil $visitorInfoUtil,
        VisitorRepository $visitorRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        $this->visitorRepository = $visitorRepository;
    }

    /**
     * Get visitor repository by array search criteria
     *
     * @param array<string,mixed> $search The search criteria
     *
     * @return Visitor|null The visitor entity if found, null otherwise
     */
    public function getRepositoryByArray(array $search): ?object
    {
        // try to find visitor in database
        try {
            return $this->visitorRepository->findOneBy($search);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'find error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get visitor ID by IP address
     *
     * @param string $ipAddress The IP address of the visitor
     *
     * @return int The ID of the visitor
     */
    public function getVisitorID(string $ipAddress): int
    {
        // get visitor id
        $visitor = $this->getVisitorRepository($ipAddress);

        if ($visitor == null) {
            return 1;
        }

        return $visitor->getID();
    }

    /**
     * Update visitor email by IP address
     *
     * @param string $ipAddress The IP address of the visitor
     * @param string $email The email address of the visitor
     *
     * @return void
     */
    public function updateVisitorEmail(string $ipAddress, string $email): void
    {
        $visitor = $this->getVisitorRepository($ipAddress);

        // check visitor found
        if ($visitor !== null) {
            $visitor->setEmail($email);

            try {
                // update email
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'flush error: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    /**
     * Get paginated list of visitors
     *
     * @param int $page The page number
     * @param string $filter The filter value
     * @param string $sort The sort value
     * @param string $order The order value
     *
     * @return Visitor[]|null The list of visitors if found, null otherwise
     */
    public function getVisitors(int $page, string $filter = '1', string $sort = 'last_visit', string $order = 'desc'): ?array
    {
        $perPage = (int) $this->appUtil->getEnvValue('ITEMS_PER_PAGE');

        // get online visitors list
        $onlineVisitors = $this->getOnlineVisitorIDs();

        // calculate offset
        $offset = ($page - 1) * $perPage;

        // get visitors from database
        try {
            $queryBuilder = $this->visitorRepository->createQueryBuilder('l')
                ->setFirstResult($offset)
                ->setMaxResults($perPage)
                ->orderBy('l.' . $sort, $order);

            // filter online visitors
            if ($filter === 'online') {
                $queryBuilder->andWhere('l.id IN (:onlineIds)')->setParameter('onlineIds', $onlineVisitors);
            }

            $visitors = $queryBuilder->getQuery()->getResult();

            // replace browser with formated value for log reader
            array_walk($visitors, function ($visitor) {
                $userAgent = $visitor->getBrowser();
                $formatedBrowser = $this->visitorInfoUtil->getBrowserShortify($userAgent);
                $visitor->setBrowser($formatedBrowser);
            });
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get visitors: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $visitors;
    }

    /**
     * Get visitor language based on IP address
     *
     * @return string|null The language of the visitor
     */
    public function getVisitorLanguage(): ?string
    {
        $repo = $this->getVisitorRepository($this->visitorInfoUtil->getIP());

        // check visitor found
        if ($repo !== null) {
            return strtolower($repo->getCountry());
        }

        return null;
    }

    /**
     * Get visitor repository by ID
     *
     * @param int $id The ID of the visitor
     *
     * @return Visitor|null The visitor entity if found, null otherwise
     */
    public function getVisitorRepositoryByID(int $id): ?object
    {
        return $this->getRepositoryByArray(['id' => $id]);
    }

    /**
     * Get visitor repository by IP address
     *
     * @param string $ipAddress The IP address of the visitor
     *
     * @return Visitor|null The visitor entity if found, null otherwise
     */
    public function getVisitorRepository(string $ipAddress): ?object
    {
        return $this->getRepositoryByArray(['ip_address' => $ipAddress]);
    }

    /**
     * Get count of visitors
     *
     * @param string $filter The filter for counting visitors ('online' or 'all')
     *
     * @return int The count of visitors
     */
    public function getVisitorsCount(string $filter = 'all'): int
    {
        try {
            $queryBuilder = $this->visitorRepository->createQueryBuilder('v')->select('COUNT(v.id)');

            // filter online visitors
            if ($filter === 'online') {
                $onlineVisitors = $this->getOnlineVisitorIDs();
                if (empty($onlineVisitors)) {
                    return 0;
                }
                $queryBuilder->where('v.id IN (:onlineIds)')->setParameter('onlineIds', $onlineVisitors);
            }

            return $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error getting visitor count: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get total count of visitors
     *
     * @return int The total count of visitors
     */
    public function getTotalVisitorsCount(): int
    {
        return $this->visitorRepository->count();
    }

    /**
     * Get count of visitors by time period
     *
     * @param string $timePeriod The time period for which to retrieve the visitor count
     *
     * @return int The count of visitors for the specified time period
     */
    public function getVisitorsCountByTimePeriod(string $timePeriod): int
    {
        return count($this->visitorRepository->findByTimeFilter($timePeriod));
    }

    /**
     * Get status of a visitor with the given ID
     *
     * @param int $id The ID of the visitor.
     * @return string The status of the visitor ('online' if online, 'offline' if not found or offline)
     */
    public function getVisitorStatus(int $id): string
    {
        $userCacheKey = 'online_user_' . $id;

        // get user status
        $status = $this->cacheUtil->getValue($userCacheKey);

        // check if status found
        if ($status->get() == null) {
            return 'offline';
        }

        return $status->get();
    }

    /**
     * Get array with online visitors IDs
     *
     * @return array<int> An array containing IDs of visitors who are currently online
     */
    public function getOnlineVisitorIDs(): array
    {
        $onlineVisitors = [];

        // get all visitors id list
        $visitorIds = $this->visitorRepository->getAllIds();

        foreach ($visitorIds as $visitorId) {
            // get visitor status
            $status = $this->getVisitorStatus($visitorId);

            // check visitor status
            if ($status == 'online') {
                array_push($onlineVisitors, $visitorId);
            }
        }

        return $onlineVisitors;
    }

    /**
     * The mirror for filter method in VisitorRepository
     *
     * This method retrieves all visitors from the database and filters them based on the given time period
     * The filter can be one of the following:
     * - 'H' for the last hour
     * - 'D' for the last day
     * - 'W' for the last week
     * - 'M' for the last month
     * - 'Y' for the last year
     * - 'ALL' to retrieve all visitors
     *
     * @param string $filter The filter for the time period
     *
     * @return array<mixed> An array of visitors filtered by the specified time range
     */
    public function getVisitorsByFilter(string $filter): array
    {
        return $this->visitorRepository->findByTimeFilter($filter);
    }

    /**
     * The mirror for filter method in VisitorRepository (iterable)
     *
     * This method retrieves all visitors from the database and filters them based on the given time period
     * The filter can be one of the following:
     * - 'H' for the last hour
     * - 'D' for the last day
     * - 'W' for the last week
     * - 'M' for the last month
     * - 'Y' for the last year
     * - 'ALL' to retrieve all visitors
     *
     * @param string $filter The filter for the time period
     *
     * @return iterable<Visitor> An iterable of visitors filtered by the specified time range
     */
    public function getVisitorsByFilterIterable(string $filter): iterable
    {
        return $this->visitorRepository->findByTimeFilterIterable($filter);
    }

    /**
     * Get the visitor metrics based on the specified count filter
     *
     * @param string $countFilter The count filter for the visitors
     *
     * @return array<mixed> The visitor metrics data
     */
    public function getVisitorMetrics(string $countFilter): array
    {
        // get visitors count metrics
        $visitorsCount = $this->visitorRepository->getVisitorsCountByPeriod($countFilter);

        // get visitors country metrics
        $visitorsCountry = $this->visitorRepository->getVisitorsByCountry();

        // get visitors city metrics
        $visitorsCity = $this->visitorRepository->getVisitorsByCity();

        // get visitors browser metrics
        $visitorsBrowsers = $this->visitorRepository->getVisitorsUsedBrowsers();

        // get visitors referer metrics
        $visitorsReferers = $this->visitorRepository->getVisitorsReferers();

        // shotify browsers array
        $visitorsBrowsersShortify = [];

        foreach ($visitorsBrowsers as $browser => $count) {
            // get short browser name
            $browserShort = $this->visitorInfoUtil->getBrowserShortify($browser);

            // merge browsers count
            if (isset($visitorsBrowsersShortify[$browserShort])) {
                $visitorsBrowsersShortify[$browserShort] += $count;
            } else {
                $visitorsBrowsersShortify[$browserShort] = $count;
            }
        }

        // sort visitors count order newest to oldest
        ksort($visitorsCount);

        // sort counters decreasing order
        arsort($visitorsCity);
        arsort($visitorsCountry);
        arsort($visitorsBrowsersShortify);

        // build return metrics data
        return [
            'visitorsCity' => $visitorsCity,
            'visitorsCount' => $visitorsCount,
            'visitorsCountry' => $visitorsCountry,
            'visitorsReferers' => $visitorsReferers,
            'visitorsBrowsers' => $visitorsBrowsersShortify
        ];
    }
}
