<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Import\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface as Logger;
use MageWorx\OptionFeatures\Model\Attribute\OptionValue\Image as ImageAttribute;
use MageWorx\OptionImportExport\Helper\Data as ImportHelper;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class Image extends AbstractImportValidator implements RowValidatorInterface
{
    protected ImageAttribute $imageAttribute;
    protected ImportHelper $importHelper;
    protected ImportProductRegistry $importProductRegistry;
    protected Filesystem $filesystem;
    protected Filesystem\Directory\WriteInterface $mediaDirectory;
    protected Logger $logger;

    /**
     * @param ImageAttribute $imageAttribute
     * @param ImportHelper $importHelper
     * @param Logger $logger
     * @param Filesystem $filesystem
     * @param ImportProductRegistry $importProductRegistry
     */
    public function __construct(
        ImageAttribute $imageAttribute,
        Logger $logger,
        ImportHelper $importHelper,
        Filesystem $filesystem,
        ImportProductRegistry $importProductRegistry
    ) {
        $this->importProductRegistry = $importProductRegistry;
        $this->imageAttribute        = $imageAttribute;
        $this->logger                = $logger;
        $this->importHelper          = $importHelper;
        $this->mediaDirectory        = $filesystem->getDirectoryWrite('media');
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function checkFileExists($path)
    {
        try {
            $this->mediaDirectory->renameFile($path, $path);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate value
     *
     * @param array $value
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        $valid = true;

        $fieldName = 'custom_option_row_' . $this->imageAttribute->getName();
        if (!$this->importProductRegistry->getIsImportValidation() || empty($value[$fieldName])) {
            return $valid;
        }

        $step1 = explode('|', $value[$fieldName]);
        foreach ($step1 as $step1Item) {
            $step2 = explode(',', $step1Item);
            foreach ($step2 as $step2Item) {
                $step3Item = explode('=', $step2Item);
                if ($step3Item[0] !== 'value') {
                    continue;
                }
                $step3Item[1] = $this->imageAttribute->decodeSymbols($step3Item[1]);
                $filePath     = 'mageworx/optionfeatures/product/option/value' . $step3Item[1];
                if (!$this->checkFileExists($filePath) && !$this->importHelper->isIgnoreMissingImages()) {
                    $this->_addMessages(
                        [
                            "Please, transfer missing images to Magento 2 MageWorx Advanced Product Options media folder (pub/media/mageworx/) first or turn on 'Ignore missing images' setting in module configuration. You can find list of missing MageWorx image files in 'var/log/system.log'"
                        ]
                    );
                    $valid = false;
                    $this->logger->warning(__('Missing MageWorx image file') . ': pub/media/' . $filePath);
                }
            }
        }

        return $valid;
    }
}
