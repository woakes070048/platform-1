<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Event;

use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after PDF options are resolved.
 */
final class AfterPdfOptionsResolvedEvent extends Event
{
    public function __construct(private PdfOptionsInterface $pdfOptions, private string $pdfEngineName)
    {
    }

    public function getPdfOptions(): PdfOptionsInterface
    {
        return $this->pdfOptions;
    }

    public function getPdfEngineName(): string
    {
        return $this->pdfEngineName;
    }
}
