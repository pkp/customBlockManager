<?php

/**
 * @file plugins/generic/customBlockManager/controllers/grid/form/CustomBlockForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomBlockForm
 * @ingroup controllers_grid_customBlockManager
 *
 * Form for press managers to create and modify sidebar blocks
 *
 */

import('lib.pkp.classes.form.Form');

class CustomBlockForm extends Form {
	/** @var int Context (press / journal) ID */
	var $contextId;

	/** @var CustomBlockPlugin Custom block plugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $template string the path to the form template file
	 * @param $contextId int
	 * @param $plugin CustomBlockPlugin
	 */
	function __construct($template, $contextId, $plugin = null) {
		parent::__construct($template);

		$this->contextId = $contextId;
		$this->plugin = $plugin;

		// Add form checks
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidator($this, 'blockDisplayName', 'required', 'plugins.generic.customBlock.nameRequired'));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$contextId = $this->contextId;
		$plugin = $this->plugin;

		$blockName = null;
		$blockContent = null;
		if ($plugin) {
			$blockName = $plugin->getName();
			$blockDisplayName = $plugin->getSetting($contextId, 'blockDisplayName');
			$blockContent = $plugin->getSetting($contextId, 'blockContent');
		}
		$this->setData('blockContent', $blockContent);
		$this->setData('blockName', $blockName);
		$this->setData('blockDisplayName', $blockDisplayName);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('blockName', 'blockDisplayName', 'blockContent'));
	}

	/**
	 * Save form values into the database
	 */
	function execute() {
		$plugin = $this->plugin;
		$contextId = $this->contextId;
		$blockName = $this->getData('blockName');
		if (!$plugin) {
			// Create a new custom block plugin
			import('plugins.generic.customBlockManager.CustomBlockPlugin');
			$customBlockManagerPlugin = PluginRegistry::getPlugin('generic', CUSTOMBLOCKMANAGER_PLUGIN_NAME);
			$blockName = $customBlockManagerPlugin->createUniqueName();
			$plugin = new CustomBlockPlugin($blockName, $customBlockManagerPlugin, $contextId);
			// Default the block to being enabled
			$plugin->setEnabled(true);

			// Default the block to the left sidebar
			$plugin->setBlockContext(BLOCK_CONTEXT_SIDEBAR);

			// Add the custom block to the list of the custom block plugins in the
			// custom block manager plugin
			$blocks = $customBlockManagerPlugin->getSetting($contextId, 'blocks');
			if (!isset($blocks)) $blocks = array();

			$blocks[] = $blockName;
			$customBlockManagerPlugin->updateSetting($contextId, 'blocks', $blocks);
		}

		// update custom block plugin content
		$plugin->updateSetting($contextId, 'blockContent', $this->getData('blockContent'));
		$plugin->updateSetting($contextId, 'blockDisplayName', $this->getData('blockDisplayName'));
	}
}

