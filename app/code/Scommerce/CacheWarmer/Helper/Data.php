<?php
/**
 * Scommerce cache warmer helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Helper;

use Scommerce\Core\Helper\Data as CoreHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Scommerce\CacheWarmer\Model\ResourceModel\Cachewarmer\CollectionFactory;
use Scommerce\CacheWarmer\Model\CachewarmerFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Scommerce\CacheWarmer\Logger\Logger;
use Magento\Framework\App\Cache\Frontend\Pool;
use Scommerce\OptimiserBase\Helper\Data as OptimiserBase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Module\Manager;

class Data extends OptimiserBase
{
    /**
     * variable to check if extension is enable or not
     *
     * @var bool
     */
    const ENABLED = 'cachewarmer/general/enabled';

    /**
     * variable to check if extension is enable or not
     *
     * @var bool
     */
    const REGENERATE_CACHE = 'cachewarmer/general/regenerate_cache_after_page_update';

    /**
     * variable to get include pages
     *
     * @var array
     */
    const INCLUDEPAGE = 'cachewarmer/general/select_pages';

    /**
     * variable to get exclude pages
     *
     * @var array
     */
    const EXCLUDEPAGE = 'cachewarmer/general/exclude_page';

    /**
     * variable to check if extension is regenerate cache manually enable or not
     *
     * @var bool
     */
    const REGENERATE_CACHE_MANUALLY = 'cachewarmer/general/can_regenerate_cache_manually';

    /**
     * variable to check if log enable or not
     *
     * @var bool
     */
    const GENERATE_LOG = 'cachewarmer/general/generate_log';

    /**
     * variable to get concurrent regeneration request
     *
     * @var int
     */
    const CONCURRENTREQUEST = 'cachewarmer/cronsetting/concurrent_regeneration_request';

    /**
     * Cache Warmer collection
     *
     * @var CollectionFactory
     */
    protected $_cachewarmer;

    /**
     * use to get collection of the the cache warmer data
     *
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * CacheFrontendPool
     *
     * @var CacheFrontendPool
     */
    protected $_cacheFrontendPool;

    /**
     * use to get local date
     *
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Use to get Log
     *
     * @var Logger
     */
    protected $_logger;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Attribute
     */
    protected $_eavAttribute;

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * Construct function for helper class
     *
     * @param Logger $logger
     * @param Context $context
     * @param CoreHelper $coreHelper
     * @param Pool $cacheFrontendPool
     * @param TimezoneInterface $localeDate
     * @param CachewarmerFactory $cacheFactory
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param Attribute $attribute
     * @param Manager $moduleManager
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        Context $context,
        CoreHelper $coreHelper,
        Pool $cacheFrontendPool,
        TimezoneInterface $localeDate,
        CachewarmerFactory $cacheFactory,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        Attribute $attribute,
        Manager $moduleManager
    ) {
        $this->_storeInfo = [];
        $this->_logger = $logger;
        $this->_localeDate = $localeDate;
        $this->_cachewarmer = $cacheFactory;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_storeManager = $storeManager;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_collectionFactory = $collectionFactory;
        $this->_eavAttribute = $attribute;
        $this->_moduleManager = $moduleManager;
        parent::__construct($context, $coreHelper);
    }

    /**
     * Check, if module active or not
     *
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        if (parent::isEnabled()) {
            return $this->isSetFlag(self::ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
        }
    }

    /**
     * Regenerate cache after page update
     * returns whether cache regenerate or not
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isRegenerateCache($storeId = null)
    {
        return $this->getValue(self::REGENERATE_CACHE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Regenerate log
     * returns whether generate log enable or not
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isGenerateLog($storeId = null)
    {
        return $this->getValue(self::GENERATE_LOG, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Exclude pages
     *
     * @return array
     */
    public function getIncludePages($storeId = null)
    {
        $includePages = $this->getValue(self::INCLUDEPAGE, ScopeInterface::SCOPE_STORE, $storeId);
        return $includePages !== null ? explode(',', $includePages) : [];
    }
    /**
     * Regenerate cache manually
     * returns whether cache manually regenerate or not
     *
     * @param int $storeId
     *
     * @return boolean
     */
    public function isRegenerateCacheManually($storeId = null)
    {
        return $this->getValue(self::REGENERATE_CACHE_MANUALLY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * returns Number of Concurrent Regeneration request
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getConcurrentRequest($storeId = null)
    {
        return $this->scopeConfig->getValue(self::CONCURRENTREQUEST, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Regenerate cache manualy
     * @param type $id
     * @return boolean
     */
    public function regenerateCache($id)
    {
        //Updated Date
        $todayDate = time();
        $updateCache = $this->_cachewarmer->create()->load($id);
        $url = $updateCache->getPageUrl();
        try {
            $chData = $this->checkUrl($url, $updateCache->getStoreId());
            $updateCache->setProcessedTime($chData['processed_time'])->save();
        } catch (\Exception $ex) {
            $this->_logger->info("Error in regenerating cache warmer: " . $ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Clear all cache and delete all warmer cache
     *
     * @return void
     */
    public function cacheClear()
    {
        /* Get all types of cache in system */
        $allTypes = array_keys($this->_cacheTypeList->getTypes());

        /* Clean cached data for specific cache type */
        foreach ($allTypes as $type) {
            $this->_cacheTypeList->cleanType($type);
        }

        /* Flushed the Entire cache storage from system, Works like Flush Cache Storage button click on System -> Cache Management */
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }

        /* Delete full cache warmer entries */
        $collection = $this->_collectionFactory->create();
        foreach ($collection as $item) {
            $item->delete();
        }
    }

    /**
     * Get all urls to be cached.
     *
     * @param string $entityType
     *
     * @return void
     */
    public function getUrls($entityType = '')
    {
        if ($entityType == '') {
            $this->cacheRun('cms-page');
            $this->cacheRun('category');
            $this->cacheRun('product');
        } else {
            $this->cacheRun($entityType);
        }
    }

    /**
     * checks to see if extension is enabled for product to include category path in admin or not
     *
     * @return boolean
     */
    public function getCategoryPathIncluded()
    {
        return $this->scopeConfig->isSetFlag(
            'catalog/seo/product_use_categories',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns if module exists or not
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isScommerceCatalogUrlModuleEnabled() {
        $enable = $this->_moduleManager->isEnabled('Scommerce_CatalogUrl');
        if ($enable) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $catalogUrlhelper = $objectManager->get('Scommerce\CatalogUrl\Helper\Data');
            return $catalogUrlhelper->isCatalogUrlActive();
        }
    }

    /**
     * @param $entityType
     * @param $attributeCode
     * @return int
     */
    public function getAttributeIdByCode($entityType, $attributeCode)
    {
        return $this->_eavAttribute->getIdByCode($entityType, $attributeCode);
    }

    /**
     * Generating caches for category, product and cms pages
     * @param $entityType
     * @param $entity_id
     * @return bool
     */
    public function cacheRun($entityType, $entity_id = '')
    {
        $pageLimit = $this->getConcurrentRequest();
        $urlRewriteTable = $this->_collectionFactory->create()->getTable('url_rewrite');

        $cacheWarmer = $this->_collectionFactory->create();

        if (strlen($entity_id)==0){
            $cacheWarmer->addFieldToFilter(
                'main_table.entity_id',
                array('null' => true)
            );
        }

        $cacheWarmer->getSelect()->joinRight(
            ['uw'=> $urlRewriteTable],
            'main_table.request_path = uw.request_path AND main_table.store_id = uw.store_id',
            ['request_path', 'entity_type', 'store_id', 'target_path', 'uw.entity_id as uw_entity_id']
        );
        $cacheWarmer->addFieldToFilter('uw.redirect_type', ['eq' => 0]);
        if ($entityType != '') {
            $cacheWarmer->addFieldToFilter('entity_type', ['eq' => $entityType]);
        }
        if (strlen($entity_id ) > 0) {
            $cacheWarmer->addFieldToFilter('uw.entity_id', ['eq' => $entity_id]);
        }
        switch ($entityType) {
            case "product":
                $catalogProductEntityInt = $this->_collectionFactory->create()->getTable('catalog_product_entity_int');
                $visibilityAttributeId = $this->getAttributeIdByCode('catalog_product','visibility');
                $statusAttributeId = $this->getAttributeIdByCode('catalog_product','status');

                //visibility check
                $cacheWarmer->getSelect()->join(
                    ['cpeivd' => $catalogProductEntityInt],
                    "uw.entity_id = cpeivd.entity_id AND cpeivd.store_id=0 AND cpeivd.attribute_id='" . $visibilityAttributeId . "'",
                    []
                );
                $cacheWarmer->getSelect()->joinLeft(
                    ['cpeiv' => $catalogProductEntityInt],
                    "uw.entity_id = cpeiv.entity_id AND uw.store_id = cpeiv.store_id AND cpeiv.attribute_id='" . $visibilityAttributeId . "'",
                    []
                );

                //status check
                $cacheWarmer->getSelect()->join(
                    ['cpeisd' => $catalogProductEntityInt],
                    "uw.entity_id = cpeisd.entity_id AND cpeisd.store_id=0 AND cpeisd.attribute_id='" . $statusAttributeId . "'",
                    []
                );

                $cacheWarmer->getSelect()->joinLeft(
                    ['cpeis' => $catalogProductEntityInt],
                    "uw.entity_id = cpeis.entity_id AND uw.store_id = cpeis.store_id AND cpeis.attribute_id='" . $statusAttributeId . "'",
                    []
                );

                if ($this->getCategoryPathIncluded()) {
                    if ($this->isScommerceCatalogUrlModuleEnabled()) {
                        $primaryCategoryAttributeId = $this->getAttributeIdByCode('catalog_product', 'product_primary_category');
                        //product_primary_category
                        $cacheWarmer->getSelect()->joinLeft(
                            ['cpeipcu' => $catalogProductEntityInt],
                            "uw.entity_id = cpeipcu.entity_id and cpeipcu.attribute_id='" . $primaryCategoryAttributeId . "'",
                            []
                        );
                        $cacheWarmer->getSelect()->where('instr(target_path,\'category\')>0 and (target_path like concat(\'%/category/\',cpeipcu.value) or cpeipcu.value is null)');
                    } else {
                        $cacheWarmer->getSelect()->where('instr(target_path,\'category\')>0');
                    }
                } else {
                    $cacheWarmer->getSelect()->where('instr(target_path,\'category\')=0');
                }

                //status and visibility exclusion
                $cacheWarmer->getSelect()->where("(IF(cpeiv.value_id > 0, cpeiv.value, cpeivd.value) !=" . \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE .
                    " or IF(cpeiv.value_id > 0, cpeiv.value, cpeivd.value) is null) and (IF(cpeis.value_id > 0, cpeis.value, cpeisd.value)  !=" .
                    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED . " 
                                            or IF(cpeis.value_id > 0, cpeis.value, cpeisd.value) is null)");

                break;
            case "cms-page":
                $cmsPageTable = $this->_collectionFactory->create()->getTable('cms_page');
                // check for active cms-pages
                $cacheWarmer->getSelect()->joinRight(
                    ['cmst'=> $cmsPageTable],
                    'uw.entity_id = cmst.page_id AND cmst.is_active = 1',
                    ['is_active']
                );
                break;
            case "category":
                $catalogCategoryEntityInt = $this->_collectionFactory->create()->getTable('catalog_category_entity_int');
                $statusAttributeId = $this->getAttributeIdByCode('catalog_category','is_active');
                //status check
                $cacheWarmer->getSelect()->joinLeft(
                    ['cceis' => $catalogCategoryEntityInt],"uw.entity_id = cceis.entity_id and cceis.attribute_id='" . $statusAttributeId . "'"
                    , []
                );
                $cacheWarmer->getSelect()->where('cceis.value =1');

                //rechecking status for specific entity id against each store
                if (strlen($entity_id)>0){
                    $cacheWarmer->getSelect()->where('uw.store_id not in (select store_id from '.
                        $catalogCategoryEntityInt.' where attribute_id='.$statusAttributeId.
                        ' AND entity_id= '.$entity_id. ' AND value=0)');
                }
                else{//rechecking and getting records which belong to either store=0 or exact store with status is active
                    $cacheWarmer->getSelect()->where('(uw.store_id=cceis.store_id or cceis.store_id=0)');
                }
                break;
        }

        $cacheWarmer->getSelect()->distinct(true)->limit($pageLimit)->order(array('uw.entity_id asc','uw.store_id desc'));
        try {
            foreach ($cacheWarmer as $rewriteUrl) {
                $pageIdentifier = $rewriteUrl->getRequestPath();
                $storeId = $rewriteUrl->getStoreId();
                $url = $this->getStoreData($storeId)['url'].$pageIdentifier;
                if (strtolower($pageIdentifier)=='home') {
                    $url=str_replace('home/','',$url);
                }
                $chData = $this->checkUrl($url, $storeId);
                $data = [
                    'page_type' => $rewriteUrl->getEntityType(),
                    'page_url' => $url,
                    'status' => 1,
                    'processed_time' => $chData['processed_time'],
                    'reference_id' => $rewriteUrl->getUwEntityId(),
                    'store_id' => $storeId,
                    'request_path' => $rewriteUrl->getRequestPath()
                ];
                $this->_cachewarmer->create()->setData($data)->save();
            }
        } catch (\Exception $ex) {
            $this->_logger->info("Error in generating cache: " . $ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Function to get store data
     *
     * @param int $storeId
     * @return array
     * @throws
     */
    public function getStoreData($storeId)
    {
        if (isset($this->_storeInfo[$storeId])) {
            return $this->_storeInfo[$storeId];
        } else {
            $store = $this->_storeManager->getStore($storeId);
            $this->_storeInfo[$storeId] = [
                'url' => $store->getBaseUrl(),
                'code' => $store->getCode()
            ];
        }
        return $this->_storeInfo[$storeId];
    }

    /**
     * Deleting records for CMS, category and product from cache warmer table
     * @param $entityType
     * @param $entityId
     */
    public function deleteCWRecords($entityType, $entityId)
    {
        $collection = $this->_collectionFactory->create();
        if (isset($entityId) && isset($entityType)) {
            $collection->addFieldToFilter('page_type', ['eq' => $entityType]);
            $collection->addFieldToFilter('reference_id', ['eq' => $entityId]);
            $collection->walk('delete');
        }
    }

    /**
     * Render the url.
     * @param string $url
     * @param int $storeId
     *
     * @return array $header
     */
    public function checkUrl($url, $storeId)
    {
        $ch = curl_init();
        $startTime = microtime(true);
        $storeCode = $this->getStoreData($storeId)['code'];
        $storeCookie = "store=".$storeCode;
        $user_agent = 'Mozilla/4.0 (compatible;)';
        $options = array(
            CURLOPT_CUSTOMREQUEST => "GET", //set request type post or get
            CURLOPT_POST => false, //set to GET
            CURLOPT_USERAGENT => $user_agent, //set user agent
            CURLOPT_COOKIE => $storeCookie, //set store code
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => true, // don't return headers
            CURLINFO_HEADER_OUT => true,
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT => 120, // timeout on response
            CURLOPT_MAXREDIRS => 4, // stop after 10 redirects
            CURLOPT_NOBODY =>1,
        );
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);

        $err = curl_errno($ch);
        $errMsg = curl_error($ch);
        $header = curl_getinfo($ch);
        $header['errNo'] = $err;
        $header['errMsg'] = $errMsg;
        $header['content'] = $content;
        $request_header_info = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response_header_info = substr($content, 0, $header_size);
        curl_close($ch);

        $endTime = microtime(true);
        $timeDiff = $endTime - $startTime;
        $header['processed_time'] = $timeDiff;

        if ($this->isGenerateLog()) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cachewarmer'.date('ymd').'.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($url .'=>'.$timeDiff);
            $logger->info($storeCookie);
            $logger->info($request_header_info);
            $logger->info($response_header_info);
        }

        return $header;
    }
}