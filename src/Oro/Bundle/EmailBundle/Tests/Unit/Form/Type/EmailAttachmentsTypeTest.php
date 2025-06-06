<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class EmailAttachmentsTypeTest extends TestCase
{
    private EmailAttachmentsType $emailAttachmentsType;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailAttachmentsType = new EmailAttachmentsType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(CollectionType::class, $this->emailAttachmentsType->getParent());
    }

    public function testSanitizeAttachmentsWithCorrectExistAttachments(): void
    {
        $attachment = new EmailAttachment();
        $attachment->setId(1);

        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(1, $resultData->count());
        $resultAttachment = $resultData->current();
        $this->assertEquals($attachment, $resultAttachment);
    }

    public function testSanitizeAttachmentsWithCorrectNewAttachments(): void
    {
        $attachment = new EmailAttachment();
        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(1, $resultData->count());
        $resultAttachment = $resultData->current();
        $this->assertEquals($attachment, $resultAttachment);
    }

    public function testSanitizeAttachmentsWithNonCorrectAttachment(): void
    {
        $attachment = null;
        $attachments = new ArrayCollection(['first' => $attachment]);
        $event = new FormEvent($this->createMock(FormInterface::class), $attachments);

        $this->emailAttachmentsType->sanitizeAttachments($event);

        /** @var ArrayCollection $resultData */
        $resultData = $event->getData();
        $this->assertEquals(0, $resultData->count());
    }
}
