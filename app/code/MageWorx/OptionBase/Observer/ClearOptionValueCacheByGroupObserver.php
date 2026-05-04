<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionBase\Model\GeneralConfigReader;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionBase\Model\ClearOptionValueCollectionCache;

class ClearOptionValueCacheByGroupObserver implements ObserverInterface
{
    protected ClearOptionValueCollectionCache $cacheCleaner;
    protected GeneralConfigReader $configReader;

    public function __construct(ClearOptionValueCollectionCache $cacheCleaner, GeneralConfigReader $configReader)
    {
        $this->cacheCleaner = $cacheCleaner;
        $this->configReader = $configReader;
    }

    public function execute(Observer $observer)
    {
        if ($this->configReader->useOptionValueCollectionCache()) {
            /** @var Group $group */
            $group = $observer->getEvent()->getGroup();

            if ($group && !empty($group->getAffectedProductIds())) {
                $this->cacheCleaner->execute($group->getAffectedProductIds());
            }
        }
    }
}
