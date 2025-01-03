<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that purges emails attachments.
 */
class PurgeEmailAttachmentsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const LIMIT = 1000;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        ConfigManager $configManager
    ) {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->configManager = $configManager;
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $size = $this->getSize($message->getBody());
        $emailAttachments = $this->getEmailAttachments($size);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($emailAttachments, $size) {
                $ids = [];
                $count = 0;
                $lastIndex = count($emailAttachments) - 1;
                $newMessageData = ($size > 0) ? ['size' => $size] : [];

                foreach ($emailAttachments as $index => $attachment) {
                    $ids[] = $attachment['id'];

                    if (++$count == self::LIMIT || $lastIndex == $index) {
                        $newMessageData['ids'] = $ids;
                        $jobRunner->createDelayed(
                            sprintf('%s:%s', 'oro.email.purge_email_attachments_by_ids', md5(implode(',', $ids))),
                            function (JobRunner $jobRunner, Job $child) use ($newMessageData) {
                                $newMessageData['jobId'] = $child->getId();
                                $this->producer->send(PurgeEmailAttachmentsByIdsTopic::getName(), $newMessageData);
                            }
                        );
                        $count = 0;
                        $ids = [];
                    }
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    #[\Override]
    public static function getSubscribedTopics()
    {
        return [PurgeEmailAttachmentsTopic::getName()];
    }

    /**
     * Returns size in bytes
     *
     * @param array $payload
     *
     * @return int
     */
    private function getSize(array $payload)
    {
        $size = $payload['size'];

        if ($payload['all']) {
            return 0;
        }

        if ($size === null) {
            $size = $this->configManager->get('oro_email.attachment_sync_max_size');
        }

        /** Convert Megabytes to Bytes */
        return (int) $size * pow(10, 6);
    }

    /**
     * @param int $size
     *
     * @return BufferedIdentityQueryResultIterator
     */
    private function getEmailAttachments($size)
    {
        $qb = $this->createEmailAttachmentQb($size);
        $em = $this->getEntityManager();

        $emailAttachments = (new BufferedIdentityQueryResultIterator($qb))
            ->setPageCallback(
                function () use ($em) {
                    $em->flush();
                    $em->clear();
                }
            );

        return $emailAttachments;
    }

    /**
     * @param int $size
     *
     * @return QueryBuilder
     */
    private function createEmailAttachmentQb($size)
    {
        $qb = $this->getEmailAttachmentRepository()
            ->createQueryBuilder('a')
            ->select('a.id')
            ->join('a.attachmentContent', 'attachment_content');

        if ($size > 0) {
            $qb
                ->andWhere(
                    <<<'DQL'
                    CASE WHEN attachment_content.contentTransferEncoding = 'base64' THEN
    (LENGTH(attachment_content.content) - LENGTH(attachment_content.content)/77) * 3 / 4 - 2
ELSE
    LENGTH(attachment_content.content)
END >= :size
DQL
                )
                ->setParameter('size', $size);
        }

        return $qb;
    }

    /**
     * @return EntityRepository
     */
    private function getEmailAttachmentRepository()
    {
        return $this->doctrine->getRepository(EmailAttachment::class);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(EmailAttachment::class);
    }
}
