<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Message;
use App\Entity\Visitor;
use App\Util\SecurityUtil;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Manager for managing inbox/contact system
 *
 * @package App\Manager
*/
class MessagesManager
{
    private AppUtil $appUtil;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private VisitorManager $visitorManager;
    private MessageRepository $messageRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        VisitorManager $visitorManager,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->visitorManager = $visitorManager;
        $this->messageRepository = $messageRepository;
    }

    /**
     * Save message to inbox database
     *
     * @param string $name The name of the sender
     * @param string $email The email of the sender
     * @param string $messageInput The message input
     * @param string $ipAddress The IP address of the sender
     * @param Visitor $visitor The visitor associated with the sender
     *
     * @return void
     */
    public function saveMessage(string $name, string $email, string $messageInput, string $ipAddress, Visitor $visitor): void
    {
        $message = new Message();

        // update visitor email
        $this->visitorManager->updateVisitorEmail($ipAddress, $email);

        // ecrypt message
        $messageInput = $this->securityUtil->encryptAes($messageInput);

        // set message entity values
        $message->setName($name)
            ->setEmail($email)
            ->setMessage($messageInput)
            ->setTime(new DateTime())
            ->setIpAddress($ipAddress)
            ->setStatus('open')
            ->setVisitor($visitor);

        try {
            // insert new message to database
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to save message: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get count of open messages from a specific IP address
     *
     * @param string $ipAddress The IP address of the user
     *
     * @return int The count of open messages from the IP address
     */
    public function getMessageCountByIpAddress(string $ipAddress): int
    {
        // build query
        $query = $this->entityManager->createQuery(
            'SELECT COUNT(m.id) FROM App\Entity\Message m WHERE m.ip_address = :ip_address AND m.status = :status'
        );

        // set query parameter
        $query->setParameter('status', 'open');
        $query->setParameter('ip_address', $ipAddress);

        // execute query
        try {
            return $query->getSingleScalarResult();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get messages count: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get messages by status
     *
     * @param string $status The status of messages to retrieve
     * @param int $page The page number for pagination (default: 1)
     *
     * @return list<array<string, mixed>>|null The list of messages filtered by status
     */
    public function getMessages(string $status, int $page): ?array
    {
        $limit = (int) $this->appUtil->getEnvValue('ITEMS_PER_PAGE');

        // calculate offset
        $offset = ($page - 1) * $limit;

        // get messages entity from database
        try {
            $inbox = $this->messageRepository->getMessagesByStatus($status, $offset, $limit);
            $messages = [];

            foreach ($inbox as $inboxMessage) {
                // decrypt message
                $messageDecrypted = $this->securityUtil->decryptAes($inboxMessage->getMessage());

                // check if message data is decrypted
                if ($messageDecrypted == null) {
                    $this->errorManager->handleError(
                        msg: 'error to decrypt aes message data',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // build message content
                $message = [
                    'id' => $inboxMessage->getId(),
                    'name' => $inboxMessage->getName(),
                    'email' => $inboxMessage->getEmail(),
                    'message' => $messageDecrypted,
                    'time' => $inboxMessage->getTime(),
                    'ip_address' => $inboxMessage->getIpAddress(),
                    'status' => $inboxMessage->getStatus(),
                ];

                // add visitor only if it exists
                if ($inboxMessage->getVisitor()) {
                    $message['visitor'] = [
                        'id' => $inboxMessage->getVisitor()->getId()
                    ];
                }

                // add message to final list
                array_push($messages, $message);
            }

            return $messages;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get messages: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Close message by updating its status to 'closed'
     *
     * @param int $id The ID of the message to close
     *
     * @return void
     */
    public function closeMessage(int $id): void
    {
        $message = $this->messageRepository->find($id);

        // check if message exists
        if ($message == null) {
            $this->errorManager->handleError(
                msg: 'Message not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        try {
            // close message
            $message->setStatus('closed');
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to close message: ' . $id . ', ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Re-encrypt all messages in the database
     *
     * @param string $oldKey The old encryption key
     * @param string $newKey The new encryption key
     *
     * @return void
     */
    public function reEncryptMessages(string $oldKey, string $newKey): void
    {
        // get all messages
        $messages = $this->messageRepository->findAll();

        // re-encrypt messages
        foreach ($messages as $message) {
            // get message data
            $encryptedData = $message->getMessage();
            if ($encryptedData == null) {
                continue;
            }

            // decrypt message
            $messageDecrypted = $this->securityUtil->decryptAes(encryptedData: $encryptedData, key: $oldKey);

            // check if message data is decrypted
            if ($messageDecrypted == null) {
                $this->errorManager->handleError(
                    msg: 'error to decrypt aes message data',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // re-encrypt message
            $message->setMessage($this->securityUtil->encryptAes(plainText: $messageDecrypted, key: $newKey));

            // persist message object
            $this->entityManager->persist($message);
        }

        // flush all message objects to the database
        $this->entityManager->flush();
    }
}
