<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\Info\Block\Adminhtml\SystemInfo\Section;

use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class MagentoSection implements SectionInterface
{
    private TimezoneInterface $localeDate;
    private DirectoryList     $directoryList;
    private Reader            $reader;

    public function __construct(
        TimezoneInterface $localeDate,
        DirectoryList     $directoryList,
        Reader            $reader
    ) {
        $this->localeDate    = $localeDate;
        $this->directoryList = $directoryList;
        $this->reader        = $reader;
    }

    public function getTitle(): string
    {
        return 'Magento Information';
    }

    public function getSectionData(): array
    {
        return [
            'Server Time'  => $this->localeDate->formatDateTime('', \IntlDateFormatter::NONE),
            'Magento Mode' => $this->getMagentoMode(),
            'Magento Path' => $this->getVisibleMagentoPath()
        ];
    }

    private function getMagentoMode(): string
    {
        try {
            $env = $this->reader->load();
        } catch (\Exception $e) {
            return 'Unable to detect. Exception: %1' . $e->getMessage();
        }

        $mode = $env[State::PARAM_MODE] ?? '';

        return ucfirst($mode);
    }

    /**
     * Path to magento installation, partially hashed by *
     *
     * @return string
     */
    private function getVisibleMagentoPath(): string
    {
        $path = $this->directoryList->getRoot();

        return preg_replace('/(?<=\/.)[^\/]+(?=.[\/])/', '*', $path);
    }
}
