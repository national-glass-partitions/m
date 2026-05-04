<?php
/**
 * Scommerce Mage - Scommerce_CacheWarmer
 *
 * @category   Scommerce
 * @package    CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Scommerce\CacheWarmer\Model\ResourceModel\Cachewarmer;
use Magento\Framework\UrlInterface;

class FlushAllCacheObserver implements ObserverInterface
{
    /**
     * Cachewarmer Resource Model
     *
     * @var Cachewarmer
     */
    protected $_cacheWarmer;

    /**
     * UrlInterface
     *
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var array
     */
    protected $_excludedUrls;

    /**
     * @param Cachewarmer $cacheWarmer
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Cachewarmer $cacheWarmer,
        UrlInterface $urlInterface
    ) {
        $this->_cacheWarmer = $cacheWarmer;
        $this->_urlInterface = $urlInterface;
        $this->_excludedUrls = array('catalog/category','cms/page','catalog/product');
    }

    /**
     * Flush Cache Warmer Records
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        try{
            if (!$this->includeURL($this->_urlInterface->getCurrentUrl(),$this->_excludedUrls)){
                $connection = $this->_cacheWarmer->getConnection();
                $tableName = $this->_cacheWarmer->getTable('cachewarmer');
                $connection->truncateTable($tableName);
            }
        }
        catch(\Exception $e){
        }
    }

    /**
     * @param $url
     * @param $arrayString
     * @return bool
     */
    private function includeURL($url, $arrayString)
    {
        foreach ($arrayString as $urlPath) {
            if (!strpos($url, $urlPath) === false) {
                return true;
            }
        }
        return false;
    }
}