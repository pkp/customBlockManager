<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockPlugin
 *
 * A generic sidebar block that can be customized through the CustomBlockManagerPlugin
 *
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class CustomBlockPlugin extends BlockPlugin {
	/** @var string Name of this block plugin */
	var $blockName;

	/** @var string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 * @param $blockName string Name of this block plugin.
	 * @param $parentPluginName string Name of block plugin management plugin.
	 */
	function __construct($blockName, $parentPluginName) {
		$this->blockName = $blockName;
		$this->parentPluginName = $parentPluginName;
		parent::__construct();
	}

	/**
	 * Get the management plugin
	 * @return CustomBlockManagerPlugin
	 */
	function getManagerPlugin() {
		return PluginRegistry::getPlugin('generic', $this->parentPluginName);
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return $this->blockName;
	}

	/**
	 * @copydoc Plugin::getPluginPath()
	 */
	function getPluginPath() {
		$plugin = $this->getManagerPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		$plugin = $this->getManagerPlugin();
		return $plugin->getTemplatePath($inCore);
	}

	/**
	 * @copydoc Plugin::getHideManagement()
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * @copydoc LazyLoadPlugin::getEnabled()
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled();
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return $this->blockName . ' ' . __('plugins.generic.customBlock.nameSuffix');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.customBlock.description');
	}

	/**
	 * @copydoc BlockPlugin::getContents()
	 */
	function getContents(&$templateMgr, $request = null) {
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : 0;

		// Get the block contents.
		$customBlockContent = $this->getSetting($contextId, 'blockContent');
		$currentLocale = AppLocale::getLocale();
		$contextPrimaryLocale = $context ? $context->getPrimaryLocale() : null;

		$divCustomBlockId = 'customblock-'.preg_replace('/\W+/', '-', $this->getName());
		$templateMgr->assign('customBlockId', $divCustomBlockId);

		$content = $customBlockContent[$currentLocale] ? $customBlockContent[$currentLocale] : $customBlockContent[$contextPrimaryLocale];

		$templateMgr->assign('customBlockContent', $content);
		return parent::getContents($templateMgr, $request);

	}

	/**
	 * @copydoc BlockPlugin::getBlockContext()
	 */
	function getBlockContext() {
		if (!Config::getVar('general', 'installed')) return BLOCK_CONTEXT_SIDEBAR;
		return parent::getBlockContext();
	}

	/**
	 * @copydoc BlockPlugin::getSeq()
	 */
	function getSeq() {
		if (!Config::getVar('general', 'installed')) return 1;
		return parent::getSeq();
	}
}

?>
