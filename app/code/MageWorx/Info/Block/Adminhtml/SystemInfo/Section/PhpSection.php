<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\Info\Block\Adminhtml\SystemInfo\Section;

class PhpSection implements SectionInterface
{
    public function getTitle(): string
    {
        return 'PHP Information';
    }

    public function getSectionData(): array
    {
        return [
            'Memory limit'       => ini_get('memory_limit'),
            'Max input vars'     => ini_get('max_input_vars'),
            'Post max size'      => ini_get('post_max_size'),
            'Max execution time' => ini_get('max_execution_time'),
            'PHP version'        => phpversion(),
            'Path to php.ini'    => php_ini_loaded_file()
        ];
    }
}
