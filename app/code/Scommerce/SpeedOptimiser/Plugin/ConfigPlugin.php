<?php
/**
 * Scommerce Mage - Plugin to intercept update the configuration values of the which are responsible for the speed optimiser
 *
 * @category   Scommerce
 * @package    Scommerce_SpeedOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\SpeedOptimiser\Plugin;

use Scommerce\SpeedOptimiser\Helper\Data;
use Magento\Config\Model\Config;
use Magento\Framework\View\Asset\Minification;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\Indexer\Product\Flat\State as ProductState;
use Magento\Framework\View\Asset\Config as AssetConfig;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class ConfigPlugin
 *
 * @package Speed_Optimizer
 */
class ConfigPlugin
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var WriterInterface
     */
    protected $_configWriter;

    /**
     * __construct
     *
     * @param Data $helper
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Data $helper,
        WriterInterface $configWriter
    )
    {
        $this->_helper = $helper;
        $this->_configWriter = $configWriter;
    }

    /**
     * Plugin afterSave
     *
     * @param Config $subject
     * @param mixed $result
     * @return mixed $result
     * @throws
     */
    public function afterSave(Config $subject, $result)
    {
        
        $sectionId = $subject->getSection();
        $groups = $subject->getGroups();
        switch ($sectionId){
            case "dev":
                

                if (isset($groups['template']['fields']['minify_html']['value'])) {
                    //compress html
                    $path = $this->_helper::HTML_SETTING;
                    $value = $groups['template']['fields']['minify_html']['value'];
                    $this->saveConfigData($path, $value);
                }


                if (isset($groups['css']['fields']['minify_files']['value'])) {
                    //minify css
                    $path = $this->_helper::CSS_MINIFICATION;
                    $value = $groups['css']['fields']['minify_files']['value'];
                    $this->saveConfigData($path, $value);
                }

                if (isset($groups['js']['fields']['minify_files']['value'])) {
                    //minify js
                    $path = $this->_helper::JAVASCRIPT_FILE_MINIFICATION;
                    $value = $groups['js']['fields']['minify_files']['value'];
                    $this->saveConfigData($path, $value);
                }

                if (isset($groups['js']['fields']['enable_js_bundling']['value'])) {
                    //bundling
                    $path = $this->_helper::JAVASCRIPT_BUNDLING;
                    $value = $groups['js']['fields']['enable_js_bundling']['value'];
                    $this->saveConfigData($path, $value);
                }

                if (isset($groups['css']['fields']['merge_css_files']['value'])) {
                    //merge css
                    $path = $this->_helper::MERGE_CSS;
                    $value = $groups['css']['fields']['merge_css_files']['value'];
                    $this->saveConfigData($path, $value);
                }

                if (isset($groups['js']['fields']['merge_files']['value'])) {
                    //merge js
                    $path = $this->_helper::MERGE_JAVASCRIPT;
                    $value = $groups['js']['fields']['merge_files']['value'];
                    $this->saveConfigData($path, $value);
                }

                break;
            case "catalog":
                if (isset($groups['frontend']['fields']['flat_catalog_category']['value'])) {
                    //category flat
                    $path = $this->_helper::FLAT_CATALOG_CATEGORY;
                    $value = $groups['frontend']['fields']['flat_catalog_category']['value'];
                    $this->saveConfigData($path, $value);
                }

                if (isset($groups['frontend']['fields']['flat_catalog_product']['value'])) {
                    //product flat
                    $path = $this->_helper::FLAT_CATALOG_PRODUCT;
                    $value = $groups['frontend']['fields']['flat_catalog_product']['value'];
                    $this->saveConfigData($path, $value);
                }

                break;
            case "speedoptimiser":
                
                //compress html
                $path  = AssetConfig::XML_PATH_MINIFICATION_HTML;
                $value = $this->_helper->getHtmlCompress();
                $this->saveConfigData($path,$value);
                
                //minify css
                $path = sprintf(Minification::XML_PATH_MINIFICATION_ENABLED, "css");
                $value = $this->_helper->getCssMinificationValue();
                $this->saveConfigData($path,$value);
                //minify js
                $path = sprintf(Minification::XML_PATH_MINIFICATION_ENABLED, "js");
                $value = $this->_helper->getJavascriptFileMinificationValue();
                $this->saveConfigData($path,$value);
                //bundling
                $path = AssetConfig::XML_PATH_JS_BUNDLING;
                $value = $this->_helper->getJavascriptBundlingValue();
                $this->saveConfigData($path,$value);
                //merge css
                $path = AssetConfig::XML_PATH_MERGE_CSS_FILES;
                $value = $this->_helper->getMergeCssValue();
                $this->saveConfigData($path,$value);
                //merge js
                $path = AssetConfig::XML_PATH_MERGE_JS_FILES;
                $value = $this->_helper->getMergeJavascriptValue();
                $this->saveConfigData($path,$value);
                //category flat
                $path = State::INDEXER_ENABLED_XML_PATH;
                $value = $this->_helper->getFlatCatalogCategoryValue();
                $this->saveConfigData($path,$value);
                //product flat
                $path = ProductState::INDEXER_ENABLED_XML_PATH;
                $value = $this->_helper->getFlatCatalogProductValue();
                $this->saveConfigData($path,$value);
                break;
        }

        return $result;
    }

    /** Saves the value in the config table based on path and value given
     * @param $path
     * @param $value
     */
    protected function saveConfigData($path, $value)
    {
        $this->_configWriter->save($path, $value);
    }
}
