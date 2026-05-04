<?php
/**
 * Scommerce Mage - Scommerce_CacheWarmer
 * @category   Scommerce
 * @package    CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Model;

use Magento\Framework\Model\AbstractModel;

class Cachewarmer extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Scommerce\CacheWarmer\Model\ResourceModel\Cachewarmer');
    }
}
