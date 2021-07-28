<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer\DirectMailerDecorator;
use Oro\Bundle\TestFrameworkBundle\Behat\Client\FileDownloader;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Processor\MessageQueueProcessorAwareTrait;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailContext extends OroFeatureContext implements MessageQueueProcessorAwareInterface
{
    use AssertTrait, MessageQueueProcessorAwareTrait;

    private ManagerRegistry $managerRegistry;

    private DirectMailer $mailer;

    private EmailTemplateManager $emailTemplateManager;

    /** @var string */
    private $downloadedFile;

    public function __construct(
        ManagerRegistry $managerRegistry,
        DirectMailer $mailer,
        EmailTemplateManager $emailTemplateManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->mailer = $mailer;
        $this->emailTemplateManager = $emailTemplateManager;
    }

    /**
     * @BeforeScenario
     * @AfterScenario
     */
    public function clear()
    {
        if ($this->mailer instanceof DirectMailerDecorator) {
            $this->mailer->clear();
        }
    }

    /**
     * @Given /^Email should contains the following "([^"]*)" text$/
     * @Given /^An email containing the following "([^"]*)" text was sent$/
     * @Given /^Email should contains the following text:/
     *
     * @param string $text
     */
    public function emailShouldContainsTheFollowingText($text)
    {
        self::assertNotEmpty($text, 'Assertion text can\'t be empty.');

        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $pattern = $this->getPattern($text);
        $found = false;

        $messages = $this->getSentMessages($this->mailer);

        /** @var \Swift_Mime_SimpleMessage $message */
        foreach ($messages as $message) {
            $data = array_map(
                function ($field) use ($message) {
                    return $this->getMessageData($message, $field);
                },
                ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Body']
            );

            $data = preg_replace('/\s+/u', ' ', implode(' ', $data));

            $found = (bool) preg_match($pattern, $data);
            if ($found !== false) {
                break;
            }
        }

        self::assertNotFalse(
            $found,
            sprintf(
                'Sent emails bodies don\'t contain expected text. The following messages has been sent: %s',
                print_r($this->getSentMessagesData($messages), true)
            )
        );
    }

    /**
     * Example: Then Email should contains the following:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Subject | Test Subject      |
     *            | Body    | Test Body         |
     *
     * @Given /^Email should contains the following:/
     * @Given /^An email containing the following was sent:/
     */
    public function emailShouldContainsTheFollowing(TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedRows = [];
        foreach ($table->getRows() as [$field, $text]) {
            //Keys makes possible to use multiple Body field in expected table
            $expectedRows[] = ['field' => $field, 'pattern' => $this->getPattern($text)];
        }

        $sentMessages = $this->getSentMessages($this->mailer);

        self::assertNotEmpty($sentMessages, 'There are no sent messages');

        $found = false;
        /** @var \Swift_Mime_SimpleMessage $message */
        foreach ($sentMessages as $message) {
            foreach ($expectedRows as $expectedContent) {
                $found = (bool) preg_match(
                    $expectedContent['pattern'],
                    $this->getMessageData($message, $expectedContent['field'])
                );
                if ($found === false) {
                    break;
                }
            }

            if ($found) {
                break;
            }
        }

        self::assertNotFalse(
            $found,
            sprintf(
                'Sent emails bodies don\'t contain expected data. The following messages has been sent: %s',
                print_r($this->getSentMessagesData($sentMessages), true)
            )
        );
    }

    private function getSentMessagesData(array $messages): array
    {
        $messagesData = [];
        foreach ($messages as $message) {
            $item = [];
            foreach (['From', 'To', 'Cc', 'Bcc', 'Subject', 'Body'] as $field) {
                $item[$field] = $this->getMessageData($message, $field);
            }
            $messagesData[] = $item;
        }

        return $messagesData;
    }

    /**
     * Example: Then Email should not contains the following:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Subject | Test Subject      |
     *            | Body    | Test Body         |
     *
     * @Given /^Email should not contains the following:/
     * @Given /^An email does not containing the following was sent:/
     */
    public function emailShouldNotContainsTheFollowing(TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedRows = [];
        foreach ($table->getRows() as [$field, $text]) {
            //Keys makes possible to use multiple Body field in expected table
            $expectedRows[] = ['field' => $field, 'pattern' => $this->getPattern($text)];
        }

        $sentMessages = $this->getSentMessages($this->mailer);

        self::assertNotEmpty($sentMessages, 'There are no sent messages');

        $found = false;
        /** @var \Swift_Mime_SimpleMessage $message */
        foreach ($sentMessages as $message) {
            foreach ($expectedRows as $expectedContent) {
                $found = (bool) preg_match(
                    $expectedContent['pattern'],
                    $this->getMessageData($message, $expectedContent['field'])
                );
                if ($found === false) {
                    break;
                }
            }

            if ($found) {
                break;
            }
        }

        self::assertFalse(
            $found,
            sprintf(
                'Sent emails contains extra data. The following messages has been sent: %s',
                print_r($this->getSentMessagesData($sentMessages), true)
            )
        );
    }

    /**
     * @Given /^take the link from email and download the file from this link$/
     */
    public function downloadFileFromEmail()
    {
        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/mi';
        $found = null;

        /** @var \Swift_Mime_Message $message */
        foreach ($this->getSentMessages($this->mailer) as $message) {
            $body = $message->getBody();

            if (!preg_match($pattern, $body, $matches)) {
                continue;
            }

            $found = $matches[2];
            break;
        }

        if ($found) {
            $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'email';
            if (!is_dir($path)) {
                mkdir($path);
            }
            $this->downloadedFile = tempnam($path, 'file_from_email_');

            self::assertTrue((new FileDownloader())->download($found, $this->downloadedFile, $this->getSession()));

            return;
        }

        self::assertNotFalse($found, 'Sent emails don\'t contain expected data.');
    }

    /**
     * Example: And the downloaded file from email contains at least the following data:
     *          | SKU   | Related SKUs {{ "type": "array", "separator": ";" }} |
     *          | PSKU2 | PSKU5;PSKU4;PSKU3;PSKU1                              |
     *
     * @Given /^the downloaded file from email contains at least the following data:$/
     */
    public function downloadedFileFromEmailMustContains(TableNode $expectedEntities)
    {
        try {
            $exportedFile = new \SplFileObject($this->downloadedFile, 'rb');
            // Treat file as CSV, skip empty lines.
            $exportedFile->setFlags(\SplFileObject::READ_CSV
                | \SplFileObject::READ_AHEAD
                | \SplFileObject::SKIP_EMPTY
                | \SplFileObject::DROP_NEW_LINE);

            $headers = $exportedFile->current();
            [$expectedHeaders, $metadata] = $this->getExpectedHeadersWithMetadata($expectedEntities->getRow(0));

            foreach ($exportedFile as $line => $data) {
                // Skip the first (header) line
                if ($line === 0) {
                    continue;
                }
                $entityDataFromCsv = $this->normalizeData(array_combine($headers, array_values($data)), $metadata);
                $expectedEntityData = $this->normalizeData(
                    array_combine($expectedHeaders, array_values($expectedEntities->getRow($line))),
                    $metadata
                );

                // Ensure that at least expected data is present.
                foreach ($expectedEntityData as $property => $value) {
                    static::assertEquals($value, $entityDataFromCsv[$property]);
                }
            }

            static::assertCount($exportedFile->key(), $expectedEntities->getRows());
        } finally {
            // We have to release SplFileObject before trying to delete the underlying file.
            $exportedFile = null;
            unlink($this->downloadedFile);
        }
    }

    /**
     * Example: Then email with Subject "Your RFQ has been received." containing the following was sent:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Body    | Test Body         |
     *
     * @Given /^email with (?P<searchField>[\w]+) "(?P<searchText>(?:[^"]|\\")*)" containing the following was sent:/
     */
    public function emailWithFieldMustContainsTheFollowing(string $searchField, string $searchText, TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        self::assertEmailFieldValid($searchField);

        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedContent = [];
        foreach ($table->getRows() as [$field, $text]) {
            $expectedContent[$field] = $this->getPattern($text);
        }

        $found = false;

        /** @var \Swift_Mime_SimpleMessage $message */
        foreach ($this->getSentMessages($this->mailer) as $message) {
            if ($searchText !== $this->getMessageData($message, $searchField)) {
                continue;
            }

            foreach ($expectedContent as $field => $pattern) {
                $found = (bool) preg_match($pattern, $this->getMessageData($message, $field));
                if ($found === false) {
                    break 2;
                }
            }
        }

        self::assertNotFalse($found, 'Sent emails don\'t contain expected data.');
    }

    /**
     * Example: Then email with Subject "Your RFQ has been received." was not sent:
     *
     * @Given /^email with (?P<searchField>[\w]+) "(?P<searchText>(?:[^"]|\\")*)" was not sent/
     */
    public function emailWithFieldIsNotSent(string $searchField, string $searchText)
    {
        self::assertEmailFieldValid($searchField);

        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        /** @var \Swift_Mime_SimpleMessage $message */
        foreach ($this->getSentMessages($this->mailer) as $message) {
            if ($searchText === $this->getMessageData($message, $searchField)) {
                self::fail(sprintf('Email with %s \"%s\" was not expected to be sent', $searchField, $searchText));
            }
        }
    }

    /**
     * Example: Then email date less than "+3 days"
     *
     * @Given /^email date (?P<condition>[\w]+) than "(?P<date>[-+\s\w]+)"$/
     */
    public function assertDateInEmail(string $condition, string $expectedDate)
    {
        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $found = null;
        /** @var \Swift_Mime_Message $message */
        foreach ($this->getSentMessages($this->mailer) as $message) {
            $found = (bool) preg_match(
                '/\D{2,3}\s\d{1,2},\s\d{4} at \d{1,2}:\d{2}\s(AM|PM)/',
                $message->getBody(),
                $matches
            );
            if ($found) {
                $date = \DateTime::createFromFormat('M d, Y ?? h:i A', $matches[0], new \DateTimeZone('UTC'));
                $result = null;
                switch ($condition) {
                    case 'less':
                        $result = $date < new \DateTime($expectedDate, new \DateTimeZone('UTC'));
                        break;
                    case 'greater':
                        $result = $date > new \DateTime($expectedDate, new \DateTimeZone('UTC'));
                        break;
                }
                self::assertTrue($result, sprintf('Email date is not %s than %s', $condition, $expectedDate));

                break;
            }
        }

        self::assertTrue($found, 'Sent emails bodies don\'t contain dates.');
    }

    /**
     * @param string $text
     * @return string
     */
    private function getPattern($text)
    {
        return sprintf('/%s/ui', preg_replace('/\s+/', '[[:space:][:cntrl:]]+', preg_quote($text, '/')));
    }

    /**
     * @param \Swift_Mime_SimpleMessage $message
     * @param string $field
     * @return string
     */
    private function getMessageData(\Swift_Mime_SimpleMessage $message, $field)
    {
        switch (strtolower(trim($field))) {
            case 'from':
                $data = array_keys($message->getFrom());
                break;
            case 'to':
                $data = array_keys($message->getTo());
                break;
            case 'cc':
                $data = is_array($message->getCc()) ? array_keys($message->getCc()) : $message->getCc();
                break;
            case 'bcc':
                $data = is_array($message->getBcc()) ? array_keys($message->getBcc()) : $message->getBcc();
                break;
            case 'subject':
                $data = $message->getSubject();
                break;
            case 'body':
                $data = strip_tags($message->getBody());
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported email field "%s".', $field));
                break;
        }

        $messageData = implode(' ', (array) $data);

        return trim(html_entity_decode(strip_tags($messageData), ENT_QUOTES));
    }

    /**
     * @param string $fieldName
     */
    private static function assertEmailFieldValid(string $fieldName): void
    {
        $allowedFields = ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Body'];
        self::assertContains(
            $fieldName,
            $allowedFields,
            'Email field must be one of '.implode(', ', $allowedFields)
        );
    }

    /**
     * Example: Then I follow "Confirm" link from the email
     *
     * @Given /^(?:|I )follow "(?P<linkCaption>[^"]+)" link from the email$/
     * @Given /^(?:|I )follow link from the email$/
     */
    public function followLinkFromEmail(string $linkCaption = '[^\<]+')
    {
        if (!$this->mailer instanceof DirectMailerDecorator) {
            return;
        }

        $url = $this->getLinkUrlFromEmail($linkCaption);

        self::assertNotNull($url, sprintf('"%s" link not found in the email', $linkCaption));

        $this->visitPath($url);
    }

    public function getLinkUrlFromEmail(string $linkCaption): ?string
    {
        if (!$this->mailer instanceof DirectMailerDecorator) {
            return null;
        }

        $pattern = sprintf('/<a.*href\s*=\s*"(?P<url>[^"]+)".*>\s*%s\s*<\/a>/s', $linkCaption);
        $url = $this->spin(function () use ($pattern) {
            $matches = [];
            /** @var \Swift_Mime_SimpleMessage $message */
            foreach ($this->getSentMessages($this->mailer) as $message) {
                $text = utf8_decode(html_entity_decode($message->getBody()));
                // replace non-breaking spaces with plain spaces to be able to search
                $text = str_replace(chr(160), chr(32), $text);

                if (preg_match($pattern, $text, $matches) && isset($matches['url'])) {
                    return htmlspecialchars_decode($matches['url']);
                }
            }

            return null;
        });

        return $url;
    }

    /**
     * @Given /^(?:|I )send email template "(?P<templateName>(?:[^"]|\\")*)" to "(?P<username>(?:[^"]|\\")*)"$/
     */
    public function sendEmailTemplateToUser(string $templateName, string $username): void
    {
        $recipient = $this->managerRegistry->getRepository(User::class)->findOneBy(['username' => $username]);

        $failedRecipients = [];
        $this->emailTemplateManager->sendTemplateEmail(
            From::emailAddress('no-reply@example.com'),
            [$recipient],
            new EmailTemplateCriteria($templateName),
            [],
            $failedRecipients
        );

        // Doctrine is caching email templates and after change template data not perform that changes in behat thread
        $this->managerRegistry->resetManager();
    }

    /**
     * @param array $expectedHeaders
     * @return array
     */
    private function getExpectedHeadersWithMetadata(array $expectedHeaders)
    {
        $metadata = [];
        foreach ($expectedHeaders as &$header) {
            $metadataPos = strpos($header, '{{');
            if ($metadataPos > 0) {
                $headerMetadata = substr($header, $metadataPos);
                $headerMetadata = trim(str_replace(['{{', '}}'], ['{', '}'], $headerMetadata));
                $headerMetadata = json_decode($headerMetadata, true);
                $header = trim(substr($header, 0, $metadataPos));
                $metadata[$header] = $headerMetadata;
            }
        }
        unset($header);

        return [$expectedHeaders, $metadata];
    }

    private function normalizeData(array $data, array $metadata): array
    {
        foreach ($metadata as $header => $metadataRow) {
            if (array_key_exists($header, $data)) {
                $cellValue = $data[$header];

                if ($metadataRow && array_key_exists('type', $metadataRow) && $metadataRow['type'] === 'array') {
                    $separator = $metadataRow['separator'] ?? ',';
                    $cellValue = explode($separator, $cellValue);
                    $cellValue = array_map('trim', $cellValue);
                    sort($cellValue);

                    $data[$header] = $cellValue;
                }
            }
        }

        return $data;
    }

    private function getSentMessages(DirectMailerDecorator $mailer): array
    {
        $this->messageQueueProcessor->waitWhileProcessingMessages();

        return $this->spin(static function () use ($mailer) {
            return $mailer->getSentMessages() ?: null;
        }) ?? [];
    }
}
