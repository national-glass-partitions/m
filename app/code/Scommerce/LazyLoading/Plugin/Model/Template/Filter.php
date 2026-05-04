<?php

/**
 * Scommerce LazyLoading  plugin file for images rendering
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\LazyLoading\Plugin\Model\Template;

/**
 * Class Filter
 * @package Scommerce_LazyLoading
 */
class Filter
{

    /**
     * @var \Scommerce\LazyLoading\Helper\Data
     */
    public $helper;

    /**
     *
     * @param \Scommerce\LazyLoading\Helper\Data $dataHelper
     */
    public function __construct(
        \Scommerce\LazyLoading\Helper\Data $dataHelper
    ) {
        $this->helper = $dataHelper;
    }
    
    /**
     * Filter the string as template
     *
     * @param \Magento\Cms\Model\Template\Filter $filter
     * @param mixed $result
     * @return mixed
     */
    public function afterFilter(
        \Magento\Cms\Model\Template\Filter $filter,
        $result
    ) {
        if ($this->helper->isEnabled()) {
            $result = $this->filterImages($result);
        }
        return $result;
    }

    /**
     * Filter images with placeholders in the content
     *
     * @param  string $content
     * @return string
     */
    public function filterImages($content)
    {

        if (!$this->helper->checkExcludePages()) {
            return $content;
        }
        $matches = $search = $replace = [];
        preg_match_all('/<img[\s\r\n]+.*?>/is', $content, $matches);

        $lazyClasses = 'lazy swatch-option-loading ';

        foreach ($matches[0] as $imgHTML) {
            if ($this->helper->applyLazyLoad()) {
                if ($this->helper->checkImagesTag($imgHTML)) {
                    continue;
                } else {
                    if (preg_match('/class=["\']/i', $imgHTML)) {
                        $replaceHTML = preg_replace('/class=(["\'])(.*?)["\']/is', 'class=$1' . $lazyClasses . ' $2$1', $imgHTML);
                    } else {
                        $replaceHTML = preg_replace('/<img/is', '<img class="' . $lazyClasses . '"', $imgHTML);
                    }

                    $search[] = $imgHTML;
                    $replace[] = $replaceHTML;
                }
            }
        }

        $content = str_replace($search, $replace, $content);

        return $content;
    }
}
