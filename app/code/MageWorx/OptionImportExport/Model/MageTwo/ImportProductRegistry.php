<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\MageTwo;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as ValueAttributes;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionTemplates\Model\GroupFactory;
use MageWorx\OptionTemplates\Model\Group\OptionFactory as GroupOptionFactory;
use MageWorx\OptionImportExport\Helper\Data as Helper;
use MageWorx\OptionFeatures\Helper\Image as ImageHelper;
use MageWorx\OptionTemplates\Model\OptionSaver;
use MageWorx\OptionTemplates\Model\ResourceModel\Product as ProductResourceModel;

class ImportProductRegistry
{
    /**
     * @var bool
     */
    protected $isOptionImport = false;

    /**
     * @var bool
     */
    protected $isImportValidation = false;

    /**
     * @var string
     */
    protected $importEntityType = '';

    /**
     * @return bool
     */
    public function getIsImportValidation()
    {
        return $this->isImportValidation;
    }

    /**
     * @param bool $isImportValidation
     */
    public function setIsImportValidation($isImportValidation)
    {
        $this->isImportValidation = $isImportValidation;
    }

    /**
     * @return bool
     */
    public function getIsOptionImport()
    {
        return $this->isOptionImport;
    }

    /**
     * @param bool $isOptionImport
     */
    public function setIsOptionImport($isOptionImport)
    {
        $this->isOptionImport = $isOptionImport;
    }

    /**
     * @return bool
     */
    public function isProductAPOImport()
    {
        return $this->importEntityType === 'catalog_product_with_apo';
    }

    /**
     * @param string $importEntityType
     */
    public function setImportEntityType($importEntityType)
    {
        $this->importEntityType = $importEntityType;
    }
}
