<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Model\System\Message;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Notification\MessageInterface;

class IsValidTablesDataSystemMessage implements MessageInterface
{
    protected ScopeConfigInterface $scopeConfig;

    /**
     * IsValidTablesDataSystemMessage constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Message identity
     */
    const MESSAGE_IDENTITY = 'mageworx_optioncleaner_system_message';

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): string
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed(): bool
    {
        return !$this->scopeConfig->getValue(
            'mageworx_apo/optionhealthchecker/is_valid',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getText(): string
    {
        $messageDetails = '';

        $messageDetails .= __('Run the ');
        $messageDetails .= ' mageworx:apo:analyze-data ';
        $messageDetails .= __('command from the console to make sure that all table data is up to date.');

        return $messageDetails;
    }

    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_NOTICE;
    }
}
