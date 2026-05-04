<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

class ActionMode
{
    const ACTION_IMPORT = 'import';

    protected string $actionMode = '';

    /**
     * Set current action mode
     *
     * @param string $actionMode
     * @return void
     */
    public function setActionMode($actionMode)
    {
        $this->actionMode = $actionMode;
    }

    /**
     * Get current action mode
     *
     * @return string
     */
    public function getActionMode()
    {
        return $this->actionMode;
    }
}
