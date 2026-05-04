<?php
/**
 * Grid Ui Component Action
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Scommerce\CacheWarmer\Helper\Data;

class Action extends Column
{
    /** Url path for regenerate */
    const ROW_REGENERATE_URL = 'cachewarmer/index/regenerate/';
    
    /** Url path for delete */
    const URL_PATH_DELETE = 'cachewarmer/index/delete';
    
    /** @var UrlInterface */
    protected $_urlBuilder;

    /**
     * @var string
     */
    private $_regenerateUrl;
    
    /**
     * @var helper
     */
    protected $_helper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param type $editUrl
     * @param Data  $helper
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Data  $helper,
        array $components = [],
        array $data = [],
        $regenerateUrl = self::ROW_REGENERATE_URL
    ) {
       
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_urlBuilder = $urlBuilder;
        $this->_regenerateUrl = $regenerateUrl;
        $this->_helper = $helper;
        
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['entity_id'])) { 
                    if($this->_helper->isRegenerateCacheManually()){
                    $item[$name]['regenerate'] = [
                        'href' => $this->_urlBuilder->getUrl(
                                self::ROW_REGENERATE_URL, [
                                    'entity_id' => $item['entity_id']
                                ]
                        ),
                        'label' => __('Regenerate')
                    ];
                    }
                    $item[$name]['delete'] = [
                        'href' => $this->_urlBuilder->getUrl(
                                self::URL_PATH_DELETE, [
                                    'entity_id' => $item['entity_id']
                                ]
                        ),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete '.$item['page_type'].' Cache'),
                            'message' => __('Are you sure you wan\'t to delete '.$item['page_type'].' cache record?')
                        ]
                    ];
                   
                }
            }
        }

        return $dataSource;
    }

    
    
    }
