<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\DocumentationProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class DocumentationProviderTest extends TestCase
{
    private function getFileLocator(): FileLocator
    {
        return new FileLocator(__DIR__ . '/../Fixtures/Resources/doc');
    }

    public function testGetDocumentation(): void
    {
        $documentationProvider = new DocumentationProvider('test_doc.md', $this->getFileLocator());

        self::assertEquals(
            'Test **documentation**' . "\n",
            $documentationProvider->getDocumentation(new RequestType([RequestType::REST]))
        );
    }

    public function testGetDocumentationForNotMarkdownFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The documentation resource "test_doc.txt" must be a Markdown document.');

        $documentationProvider = new DocumentationProvider('test_doc.txt', $this->getFileLocator());

        $documentationProvider->getDocumentation(new RequestType([RequestType::REST]));
    }

    public function testGetDocumentationForNotExistingFile(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $documentationProvider = new DocumentationProvider('not_existing.md', $this->getFileLocator());

        $documentationProvider->getDocumentation(new RequestType([RequestType::REST]));
    }
}
