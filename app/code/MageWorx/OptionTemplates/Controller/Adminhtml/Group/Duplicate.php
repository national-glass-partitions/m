<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use MageWorx\OptionTemplates\Model\Group\Copier;
use MageWorx\OptionTemplates\Model\Group;
use Magento\Ui\Component\MassAction\Filter;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory;

class Duplicate extends MassAction
{
    /**
     * @var string
     */
    protected $successMessage = 'A total of %1 record(s) have been duplicated';
    /**
     * @var string
     */
    protected $errorMessage = 'An error occurred while duplicating record(s).';

    /**
     * @var Builder
     */
    protected $groupBuilder;

    /**
     * @var Copier
     */
    protected $groupCopier;
    /**
     *
     * @var Filter
     */
    protected $filter;

    /**
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Copier $groupCopier
     * @param Builder $groupBuilder
     * @param Context $context
     */
    public function __construct(
        Filter $filter,
        CollectionFactory $collectionFactory,
        Copier $groupCopier,
        Builder $groupBuilder,
        Context $context
    ) {
        $this->groupCopier = $groupCopier;
        parent::__construct($filter, $collectionFactory, $groupBuilder, $context);
    }

    /**
     * @param Group $group
     * @return $this
     */
    protected function doTheAction(Group $group)
    {
        $this->groupCopier->copy($group);
        return $this;
    }
}
