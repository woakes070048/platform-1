<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableHeader;
use WebDriver\Exception\ElementNotVisible;
use WebDriver\Exception\NoSuchElement;

/**
 * @method GridRow getRowByNumber($rowNumber) @see Table::getRowByNumber($rowNumber)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Grid extends Table implements GridInterface
{
    const TABLE_HEADER_ELEMENT = 'GridHeader';
    const TABLE_ROW_STRICT_ELEMENT = 'GridRowStrict';
    const TABLE_ROW_ELEMENT = 'GridRow';
    const GRID_TABLE_ELEMENT = 'GridTable';
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in grid";
    const ERROR_NO_ROW_CONTENT = 'Grid has no record with "%s" content';

    #[\Override]
    public function getRows()
    {
        /** @var Table $table */
        $table = $this->getElement($this->getMappedChildElementName(static::GRID_TABLE_ELEMENT));
        $elementName = $this->getMappedChildElementName(static::TABLE_ROW_STRICT_ELEMENT);

        return $table->getRowElements($elementName);
    }

    #[\Override]
    public function getRowByContent($content, $failIfNotFound = true)
    {
        /** @var Table $table */
        $table = $this->getElement($this->getMappedChildElementName(self::GRID_TABLE_ELEMENT));

        return $table->getRowByContentElement(
            $content,
            $this->getMappedChildElementName(static::TABLE_ROW_ELEMENT),
            $failIfNotFound
        );
    }

    /**
     * @return TableHeader
     */
    #[\Override]
    public function getHeader()
    {
        /** @var Table $table */
        $table = $this->getElement($this->getMappedChildElementName(static::GRID_TABLE_ELEMENT));

        return $table->getHeaderElement(static::TABLE_HEADER_ELEMENT);
    }

    #[\Override]
    public function getMappedChildElementName($name)
    {
        if (!isset($this->options['mapping'][$name])) {
            return $name;
        }

        return $this->options['mapping'][$name];
    }

    #[\Override]
    public function getMassActionLink($title)
    {
        return $this->elementFactory->createElement($this->getMappedChildElementName('GridMassActionMenu'))
            ->findLink($title);
    }

    #[\Override]
    public function hasMassActionLink($title): bool
    {
        try {
            $massActionsButton = $this->getMassActionButton();
            $massActionsButton->press();
        } catch (NoSuchElement | ElementNotVisible $e) {
            return false;
        }

        $hasLink = $this->getMassActionLink($title) !== null;
        // Hide mass actions menu to not break following steps
        $massActionsButton->press();

        return $hasLink;
    }

    #[\Override]
    public function clickMassActionLink($title)
    {
        $massActionsButton = $this->getMassActionButton();
        $massActionsButton->press();

        $massActionLink = $this->getMassActionLink($title);
        self::assertNotNull($massActionLink, 'Mass action link not found on the page');
        self::assertTrue($massActionLink->isVisible(), 'Mass action link is not visible');

        $massActionLink->click();
    }

    #[\Override]
    public function clickSelectAllMassActionLink($title)
    {
        $massActionsButton = $this->getMassActionButton();
        $massActionsButton->press();

        $massActionLink = $this->getMassActionLink($title);
        self::assertNotNull($massActionLink, 'Mass action link not found on the page');
        self::assertTrue($massActionLink->isVisible(), 'Mass action link is not visible');

        $massActionLink->click();
    }

    #[\Override]
    public function clickViewList()
    {
        $list = $this->getViewList();

        self::assertTrue($list->isValid(), 'Grid view list not found on the page');
        $list->press();
    }

    #[\Override]
    public function checkFirstRecords($number, $cellNumber = 0)
    {
        $rows = $this->getRows();

        self::assertGreaterThanOrEqual(
            $number,
            count($rows),
            sprintf('Can\'t check %s records, because grid has only %s records', $number, count($rows))
        );

        for ($i = 0; $i < $number; $i++) {
            $rows[$i]->checkMassActionCheckbox($cellNumber);
        }
    }

    #[\Override]
    public function uncheckFirstRecords($number, $cellNumber = 0)
    {
        $rows = $this->getRows();

        self::assertGreaterThanOrEqual(
            $number,
            count($rows),
            sprintf('Can\'t uncheck %s records, because grid has only %s records', $number, count($rows))
        );

        for ($i = 0; $i < $number; $i++) {
            $rows[$i]->uncheckMassActionCheckbox($cellNumber);
        }
    }

    /**
     * @param string $content
     * @return bool
     */
    public function isRecordUnchecked($content)
    {
        return !$this->getRowByContent($content)->isMassActionChecked();
    }

    #[\Override]
    public function checkRecord($content)
    {
        $this->getRowByContent($content)->checkMassActionCheckbox();
    }

    public function canCheckRecord($content)
    {
        $this->getRowByContent($content)->hasMassActionCheckbox();
    }

    #[\Override]
    public function uncheckRecord($content)
    {
        $this->getRowByContent($content)->uncheckMassActionCheckbox();
    }

    #[\Override]
    public function getMassActionButton()
    {
        $massActionsButton = $this->elementFactory->createElement(
            $this->getMappedChildElementName('MassActionButton'),
            $this
        );

        if (!$massActionsButton || !$massActionsButton->isVisible()) {
            throw ElementNotVisible::factory(
                ElementNotVisible::ELEMENT_NOT_VISIBLE,
                'Mass Action dropdown is not present or not visible on page'
            );
        }

        return $massActionsButton;
    }

    #[\Override]
    public function massCheck($title)
    {
        $massActionHeadCheckboxElementName = $this->getMappedChildElementName('MassActionHeadCheckbox');

        $this->elementFactory->createElement($massActionHeadCheckboxElementName, $this)->click();
        $this->elementFactory->createElement('GridMassCheckMenu')->clickLink($title);
    }

    #[\Override]
    public function selectPageSize($number)
    {
        $pageSizeElement = $this->elementFactory->createElement('PageSize');
        $pageSizeElement->find('css', '.btn')->click();
        $pageSizeElement->clickLink($number);
    }

    #[\Override]
    public function clickActionLink($content, $action, $failIfNotFound = true)
    {
        /** @var GridRow $row */
        $row = $this->getRowByContent($content, $failIfNotFound);
        if ($row) {
            $link = $row->getActionLink($action, $failIfNotFound);
            if ($link) {
                $link->click();
            }
        }
    }

    #[\Override]
    public function getViewList()
    {
        return $this->getElement($this->getMappedChildElementName('GridViewList'));
    }
}
