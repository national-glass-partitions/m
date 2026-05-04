<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\Info\Block\Adminhtml;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Helper\Js;
use MageWorx\Info\Block\Adminhtml\SystemInfo\Section\SectionInterface;

class SystemInfo extends Fieldset
{
    protected Field $fieldRenderer;

    /**
     * @var SectionInterface[]
     */
    protected array $sectionPool;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        array $data = [],
        array $sectionPool = []
    ) {
        $this->sectionPool = $sectionPool;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    public function render(AbstractElement $element): string
    {
        $header = $this->_getHeaderHtml($element);

        if ($this->_authorization->isAllowed('MageWorx_Info::config_systeminfo_tab')) {
            $elements = $this->processGetElements($element);
        } else {
            $elements = $this->getSectionTitle($element, 'permission_denied', 'Permission Denied');
        }

        $footer = $this->_getFooterHtml($element);

        return $header . $elements . $footer;
    }

    private function processGetElements(AbstractElement $element): string
    {
        $elements = '';

        foreach ($this->getSectionPool() as $sectionCode => $section) {
            if (!$section instanceof SectionInterface) {
                continue;
            }
            $elements .= $this->getSectionTitle($element, $sectionCode, $section->getTitle());
            $elements .= $this->getSectionData(
                $element,
                $sectionCode . '_data',
                $this->getDataAsTable($section->getSectionData())
            );
        }

        return $elements;
    }

    private function getSectionData(AbstractElement $fieldset, string $fieldName, string $value): string
    {
        return $this->getFieldHtml($fieldset, $fieldName, '', $value);
    }

    private function getSectionTitle(AbstractElement $fieldset, string $fieldName, string $label): string
    {
        return $this->getFieldHtml($fieldset, $fieldName, $label, '');
    }

    private function getDataAsTable(array $data): string
    {
        $html = '<table>';
        foreach ($data as $key => $value) {
            $html .=
                '<tr>' .
                '<td>' . $key . '</td>' .
                '<td>' . $value . '</td>' .
                '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    private function getFieldRenderer(): BlockInterface
    {
        if (empty($this->fieldRenderer)) {
            $this->fieldRenderer = $this->_layout->createBlock(
                Field::class
            );
        }

        return $this->fieldRenderer;
    }

    protected function getFieldHtml(
        AbstractElement $fieldset,
        string $fieldName,
        string $label = '',
        string $value = ''
    ): string {
        $config = [
            'label'              => __($label),
            'after_element_html' => $value
        ];
        $field  = $fieldset->addField($fieldName, 'label', $config)->setRenderer($this->getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * @return SectionInterface[]
     */
    protected function getSectionPool(): array
    {
        return $this->sectionPool;
    }
}
