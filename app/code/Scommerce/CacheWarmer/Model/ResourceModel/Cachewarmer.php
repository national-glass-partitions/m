<?php
/**
 * Scommerce Mage - Scommerce_CacheWarmer
 * @category   Scommerce
 * @package    CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Cachewarmer Resource Model
 */
class Cachewarmer extends AbstractDb
{
    
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('cachewarmer', 'entity_id');
    }
}
