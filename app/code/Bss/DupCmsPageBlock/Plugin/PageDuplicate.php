<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_DupCmsPageBlock
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\DupCmsPageBlock\Plugin;

class PageDuplicate
{
    const URL_PATH_DUPLICATE = 'dup/page/duplicate';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * PageDuplicate constructor.
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterPrepareDataSource($subject, $result)
    {
        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as & $item) {
                if (isset($item['page_id'])) {
                    $title = $this->escaper->escapeHtml($item['title']);
                    $item[$subject->getData('name')]['duplicate'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_DUPLICATE,
                            [
                                'page_id' => $item['page_id']
                            ]
                        ),
                        'label' => __('Duplicate'),
                        'confirm' => [
                            'title' => __('Duplicate %1', $title),
                            'message' => __('Are you sure you want to duplicate a %1 record?', $title)
                        ]
                    ];
                }
            }
        }
        return $result;
    }
}
