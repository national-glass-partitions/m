<?php
/**
 * Cachewarmer Resource Collection
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Model\ResourceModel\Cachewarmer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Scommerce\CacheWarmer\Model\Cachewarmer', 'Scommerce\CacheWarmer\Model\ResourceModel\Cachewarmer');
    }
}
