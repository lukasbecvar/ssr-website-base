<?php

namespace App\Controller\Admin;

use App\Util\AppUtil;
use App\Entity\Visitor;
use App\Util\ExportUtil;
use App\Form\BanFormType;
use App\Manager\BanManager;
use App\Util\VisitorInfoUtil;
use App\Manager\VisitorManager;
use App\Annotation\CsrfProtection;
use App\Form\VisitorListExportType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class VisitorManagerController
 *
 * Visitor manager controller provides view/ban/delete visitors
 *
 * @package App\Controller\Admin
 */
class VisitorManagerController extends AbstractController
{
    private AppUtil $appUtil;
    private ExportUtil $exportUtil;
    private BanManager $banManager;
    private VisitorManager $visitorManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        AppUtil $appUtil,
        ExportUtil $exportUtil,
        BanManager $banManager,
        VisitorManager $visitorManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->appUtil = $appUtil;
        $this->exportUtil = $exportUtil;
        $this->banManager = $banManager;
        $this->visitorManager = $visitorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Handle visitors table page
     *
     * @param Request $request The request object
     *
     * @return Response The visitor manager page view
     */
    #[Route('/admin/visitors', methods: ['GET'], name: 'admin_visitor_manager')]
    public function visitorsTable(Request $request): Response
    {
        // get page int
        $page = intval($this->appUtil->getQueryString('page', $request));

        // get filter value
        $filter = $this->appUtil->getQueryString('filter', $request);

        // get sort values
        $sort = $this->appUtil->getQueryString('sort', $request, 'id');
        $order = $this->appUtil->getQueryString('order', $request, 'asc');

        // return visitor manager view
        return $this->render('admin/visitors-manager.twig', [
            'page' => $page,
            'sort' => $sort,
            'order' => $order,
            'filter' => $filter,
            'visitorMetrics' => null,
            'visitorInfoData' => null,
            'currentIp' => $this->visitorInfoUtil->getIP(),
            'bannedCount' => $this->banManager->getBannedCount(),
            'visitorsLimit' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE'),
            'onlineVisitors' => $this->visitorManager->getOnlineVisitorIDs(),
            'visitorsCount' => $this->visitorManager->getVisitorsCount($filter),
            'visitorsData' => $this->visitorManager->getVisitors($page, $filter, $sort, $order)
        ]);
    }

    /**
     * Handle ip information for a given visitor ip address
     *
     * @param Request $request The request object
     *
     * @return Response The IP information view
     */
    #[Route('/admin/visitors/ipinfo', methods: ['GET'], name: 'admin_visitor_ipinfo')]
    public function visitorIpInfo(Request $request): Response
    {
        // get ip address from query string
        $ipAddress = $this->appUtil->getQueryString('ip', $request);

        // check if ip parameter found
        if ($ipAddress == 1) {
            return $this->redirectToRoute('admin_visitor_manager');
        }

        // get ip info
        $ipInfoData = $this->visitorInfoUtil->getIpInfo($ipAddress);
        $ipInfoData = json_decode(json_encode($ipInfoData), true);

        // return visitor manager view
        return $this->render('admin/visitors-manager.twig', [
            'page' => 1,
            'filter' => 1,
            'visitorMetrics' => null,
            'currentIp' => $ipAddress,
            'visitorInfoData' => $ipInfoData,
            'bannedCount' => $this->banManager->getBannedCount(),
            'onlineVisitors' => $this->visitorManager->getOnlineVisitorIDs()
        ]);
    }

    /**
     * Handle confirmation form for deleting all visitors
     *
     * @param Request $request The request object
     *
     * @return Response The delete confirmation page view
     */
    #[Route('/admin/visitors/delete', methods: ['GET'], name: 'admin_visitor_delete')]
    public function deleteAllVisitors(Request $request): Response
    {
        // get page int
        $page = $this->appUtil->getQueryString('page', $request);

        // return delete confirmation view
        return $this->render('admin/element/confirmation/delete-visitors.twig', [
            'page' => $page
        ]);
    }

    /**
     * Handle visitor ban form
     *
     * @param Request $request The request object
     *
     * @return Response The redirect back to visitor manager
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/visitors/ban', methods: ['GET', 'POST'], name: 'admin_visitor_ban')]
    public function banVisitor(Request $request): Response
    {
        // init visitor entity
        $visitor = new Visitor();

        // get query parameters
        $page = intval($this->appUtil->getQueryString('page', $request));
        $id = intval($this->appUtil->getQueryString('id', $request));

        // create register form
        $form = $this->createForm(BanFormType::class, $visitor);
        $form->handleRequest($request);

        // check is form submited
        if ($form->isSubmitted() && $form->isValid()) {
            // get ban reason
            $banReason = $form->get('ban_reason')->getData();

            // check if reason set
            if (empty($banReason)) {
                $banReason = 'no-reason';
            }

            // get visitor ip
            $ipAddress = $this->banManager->getVisitorIP($id);

            // ban visitor
            $this->banManager->banVisitor($ipAddress, $banReason);

            // check if banned by inbox
            if ($request->query->get('referer') == 'inbox') {
                return $this->redirectToRoute('admin_inbox', [
                    'page' => $page
                ]);
            }

            // redirect back to visitor page
            return $this->redirectToRoute('admin_visitor_manager', [
                'page' => $page
            ]);
        }

        // render ban form view
        return $this->render('admin/element/form/ban-form.twig', [
            'banForm' => $form,
            'page' => $page
        ]);
    }

    /**
     * Handle visitor unban functionality
     *
     * @param Request $request The request object
     *
     * @return Response The redirect back to visitor manager
     */
    #[Route('/admin/visitors/unban', methods: ['POST'], name: 'admin_visitor_unban')]
    public function unbanVisitor(Request $request): Response
    {
        // get query parameters
        $page = intval($this->appUtil->getQueryString('page', $request));
        $id = intval($this->appUtil->getQueryString('id', $request));

        // get visitor ip
        $ipAddress = $this->banManager->getVisitorIP($id);

        // check if visitor is banned
        if ($this->banManager->isVisitorBanned($ipAddress)) {
            // unban visitor
            $this->banManager->unbanVisitor($ipAddress);
        }

        // check if unban init by inbox
        if ($request->query->get('referer') == 'inbox') {
            return $this->redirectToRoute('admin_inbox', [
                'page' => $page
            ]);
        }

        // redirect back to visitor page
        return $this->redirectToRoute('admin_visitor_manager', [
            'page' => $page
        ]);
    }

    /**
     * Handle export visitors list data to Excel or Pdf file
     *
     * @param Request $request The request object
     *
     * @return Response The export form view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/visitors/download', methods: ['GET', 'POST'], name: 'admin_visitor_manager_download')]
    public function downloadVisitorsList(Request $request): Response
    {
        // init error message variable
        $errorMsg = null;

        // create form
        $form = $this->createForm(VisitorListExportType::class);
        $form->handleRequest($request);

        // check is form submitted
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // get form data
            $filter = $data['filter'];
            $format = $data['format'];

            // check if data is empty
            if ($format == null || $filter == null) {
                $errorMsg = 'Please select a filter and a format';
            }

            // check if format is valid
            if ($format != 'PDF' && $format != 'EXCEL') {
                $errorMsg = 'Please select a valid format';
            }

            // get visitors list
            $visitorsList = iterator_to_array($this->visitorManager->getVisitorsByFilterIterable($filter));
            if (empty($visitorsList)) {
                $errorMsg = 'no visitors found in selected time period';
            }

            // get visitors list as array
            $visitorsList = iterator_to_array($this->visitorManager->getVisitorsByFilterIterable($filter));

            // check if empty
            if (empty($visitorsList)) {
                $errorMsg = 'no visitors found in selected time period';
            }

            // check if error found
            if ($errorMsg == null) {
                // export data with valid method
                if ($format === 'EXCEL') {
                    return $this->exportUtil->exportVisitorsToExcel($visitorsList);
                } elseif ($format === 'PDF') {
                    return $this->exportUtil->exportVisitorsListToFPDF($visitorsList);
                }

                // redirect back to export page
                return $this->redirectToRoute('admin_visitor_manager_download');
            }
        }

        // return visitors data export form view
        return $this->render('admin/element/form/visitors-export-form.twig', [
            'form' => $form->createView(),
            'errorMsg' => $errorMsg
        ]);
    }

    /**
     * Handle visitors metrics page
     *
     * @param Request $request The request object
     *
     * @return Response The visitors metrics page view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/visitors/metrics', methods: ['GET', 'POST'], name: 'admin_visitor_manager_metrics')]
    public function visitorsMetrics(Request $request): Response
    {
        // get time period from query string
        $timePeriod = $this->appUtil->getQueryString('time_period', $request);

        // set default time period
        if ($timePeriod == '1') {
            $timePeriod = 'last_week';
        }

        // get visitor metrics
        $metrics = $this->visitorManager->getVisitorMetrics($timePeriod);

        // return visitor metrics view
        return $this->render('admin/visitors-manager.twig', [
            'visitorMetrics' => $metrics,
            'visitorInfoData' => null,
            'onlineVisitors' => null,
            'bannedCount' => null,
            'page' => null,
            'filter' => 1
        ]);
    }
}
