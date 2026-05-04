<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\Info\Block\Adminhtml\SystemInfo\Section;

/**
 * Section visible in admin panel (path Store-> Configuration -> MageWorx -> Info -> ...)
 */
interface SectionInterface
{
    /**
     * Get section title, visible in admin panel
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get section params (rows)
     *
     * @return array
     */
    public function getSectionData(): array;
}
