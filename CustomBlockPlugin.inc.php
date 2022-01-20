<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockPlugin
 *
 * A generic sidebar block that can be customized through the CustomBlockManagerPlugin
 *
 */

import('lib.pkp.classes.plugins.BlockPlugin');

use APP\core\Application;
use PKP\facades\Locale;
use PKP\plugins\BlockPlugin;

class CustomBlockPlugin extends BlockPlugin
{
    /** @var string Name of this block plugin */
    public $_blockName;

    /** @var CustomBlockManagerPlugin Parent plugin */
    public $_parentPlugin;

    /**
     * Constructor
     *
     * @param string $blockName Name of this block plugin.
     * @param CustomBlockManagerPlugin $parentPlugin Custom block plugin management plugin.
     */
    public function __construct($blockName, $parentPlugin)
    {
        $this->_blockName = $blockName;
        $this->_parentPlugin = $parentPlugin;
        parent::__construct();
    }

    /**
     * Get the management plugin
     *
     * @return CustomBlockManagerPlugin
     */
    public function getManagerPlugin()
    {
        return $this->_parentPlugin;
    }

    /**
     * @copydoc Plugin::getName()
     */
    public function getName()
    {
        return $this->_blockName;
    }

    /**
     * @copydoc Plugin::getPluginPath()
     */
    public function getPluginPath()
    {
        $plugin = $this->getManagerPlugin();
        return $plugin->getPluginPath();
    }

    /**
     * @copydoc Plugin::getTemplatePath()
     */
    public function getTemplatePath($inCore = false)
    {
        $plugin = $this->getManagerPlugin();
        return $plugin->getTemplatePath($inCore);
    }

    /**
     * @copydoc Plugin::getHideManagement()
     */
    public function getHideManagement()
    {
        return true;
    }

    /**
     * @copydoc LazyLoadPlugin::getEnabled()
     *
     * @param null|mixed $contextId
     */
    public function getEnabled($contextId = null)
    {
        if (!Application::isInstalled()) {
            return true;
        }
        return parent::getEnabled($contextId);
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->_blockName . ' ' . __('plugins.generic.customBlock.nameSuffix');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.customBlock.description');
    }

    /**
     * @copydoc BlockPlugin::getContents()
     *
     * @param null|mixed $request
     */
    public function getContents($templateMgr, $request = null)
    {
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;

        // Get the block contents.
        $customBlockTitle = $this->getSetting($contextId, 'blockTitle');
        $customBlockContent = $this->getSetting($contextId, 'blockContent');
        $currentLocale = Locale::getLocale();
        $contextPrimaryLocale = $context ? $context->getPrimaryLocale() : $request->getSite()->getPrimaryLocale();

        $divCustomBlockId = 'customblock-' . preg_replace('/\W+/', '-', $this->getName());
        $templateMgr->assign('customBlockId', $divCustomBlockId);

        $title = $customBlockTitle[$currentLocale] ? $customBlockTitle[$currentLocale] : $customBlockTitle[$contextPrimaryLocale];
        $content = $customBlockContent[$currentLocale] ? $customBlockContent[$currentLocale] : $customBlockContent[$contextPrimaryLocale];

        $templateMgr->assign('customBlockTitle', $title);
        $templateMgr->assign('customBlockContent', $content);
        $templateMgr->assign('showName', $this->getSetting($contextId, 'showName'));
        return parent::getContents($templateMgr, $request);
    }
}
