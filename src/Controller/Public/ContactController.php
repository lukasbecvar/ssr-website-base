<?php

namespace App\Controller\Public;

use App\Util\AppUtil;
use App\Entity\Message;
use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use App\Form\ContactFormType;
use App\Manager\VisitorManager;
use App\Manager\MessagesManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ContactController
 *
 * Contact controller provides contact links & contact form
 *
 * @package App\Controller\Public
*/
class ContactController extends AbstractController
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private VisitorManager $visitorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private MessagesManager $messagesManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        VisitorManager $visitorManager,
        VisitorInfoUtil $visitorInfoUtil,
        MessagesManager $messagesManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->visitorManager = $visitorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        $this->messagesManager = $messagesManager;
    }

    /**
     * Handle contact page
     *
     * @param Request $request The request object
     *
     * @return Response The contact page view response
     */
    #[Route('/contact', methods: ['GET', 'POST'], name: 'public_contact')]
    public function contactPage(Request $request): Response
    {
        // init status messages variables
        $errorMsg = null;
        $successMsg = null;

        // get visitor ip address
        $ipAddress = $this->visitorInfoUtil->getIP();

        // handle success status
        if ($request->query->get('status') == 'ok') {
            $successMsg = 'contact.success.message';
        }

        // handle limit reached status
        if ($request->query->get('status') == 'reached') {
            $errorMsg = 'contact.error.limit.reached.message';
        }

        // handle error status
        if ($request->query->get('status') == 'ko') {
            $errorMsg = 'contact.error.ko.message';
        }

        // create message entity
        $message = new Message();

        // create register form
        $form = $this->createForm(ContactFormType::class, $message);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get form data
            $name = $form->get('name')->getData();
            $email = $form->get('email')->getData();
            $messageInput = $form->get('message')->getData();

            // get honeypot value
            $honeypot = $form->get('websiteIN')->getData();

            // check if values empty
            if (empty($name)) {
                $errorMsg = 'contact.error.username.empty';
            } elseif (empty($email)) {
                $errorMsg = 'contact.error.email.empty';
            } elseif (empty($messageInput)) {
                $errorMsg = 'contact.error.message.empty';
            } elseif (strlen($messageInput) > 2000) {
                $errorMsg = 'contact.error.characters.limit.reached';

            // check if honeypot is empty
            } elseif (isset($honeypot)) {
                $errorMsg = 'contact.error.blocked.message';
            } else {
                // get others data
                $visitorId = $this->visitorManager->getVisitorID($ipAddress);

                // check if user have unclosed messages
                if ($this->messagesManager->getMessageCountByIpAddress($ipAddress) >= 5) {
                    $this->logManager->log(
                        name: 'message-sender',
                        message: 'visitor: ' . $visitorId . ' trying send new message but he has open messages'
                    );

                    // redirect back to from & handle limit reached error status
                    return $this->redirectToRoute('public_contact', ['status' => 'reached']);
                } else {
                    // save message to database
                    $this->messagesManager->saveMessage($name, $email, $messageInput, $ipAddress, $visitorId);

                    // redirect back to from & handle ok status
                    return $this->redirectToRoute('public_contact', ['status' => 'ok']);
                }
            }
        }

        // render contact page view
        return $this->render('public/contact.twig', [
            // app util instance
            'appUtil' => $this->appUtil,

            // contact form data
            'errorMsg' => $errorMsg,
            'successMsg' => $successMsg,
            'contactForm' => $form->createView()
        ]);
    }
}
