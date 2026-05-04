<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin\Adminhtml\Product\Edit\Tab\Options\Type\Select;

use \MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\Container;

/**
 * Class AfterToHtml.
 * This plugin adds collected options html to base Magento options template.
 *
 * @package MageWorx\OptionBase\Plugin\Adminhtml\Product\Edit\Tab\Options\Type\Select
 */
class AfterToHtml
{
    /**
     * Options container
     *
     * @var Container
     */
    protected Container $container;

    /**
     * AfterToHtml constructor.
     *
     * @param Container $container
     */
    public function __construct(
        Container $container
    ) {

        $this->container = $container;
    }

    /**
     * @param $subject \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select
     * @param $result array|string
     * @return array|string
     */
    public function afterToHtml($subject, $result)
    {
        $result = explode('</script>', $result);
        $rowTemplate = $result[1];

        $rowTemplate = str_replace('</tr>', $this->container->toHtml() . '</tr>', $rowTemplate);

        $result[1] = $rowTemplate;
        $result = implode('</script>', $result);

        return $result;
    }
}
