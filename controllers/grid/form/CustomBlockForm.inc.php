<?php

/**
 * @file plugins/generic/customBlockManager/controllers/grid/form/CustomBlockForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		$this->addCheck(new FormValidator($this, 'blockTitle', 'required', 'plugins.generic.customBlock.nameRequired'));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$contextId = $this->contextId;
		$plugin = $this->plugin;

		$templateMgr = TemplateManager::getManager();

		$existingBlockName = null;
		$blockTitle = null;
		$blockContent = null;
		$showName = null;
		if ($plugin) {
			$blockTitle = $plugin->getSetting($contextId, 'blockTitle');
			$blockContent = $plugin->getSetting($contextId, 'blockContent');
			$showName = $plugin->getSetting($contextId, 'showName');
			$existingBlockName = $plugin->_blockName;
		}
		$this->setData('blockContent', $blockContent);
		$this->setData('blockTitle', $blockTitle);
		$this->setData('showName', $showName);
		$this->setData('existingBlockName', $existingBlockName);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('blockTitle', 'blockContent', 'showName'));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$plugin = $this->plugin;
		$contextId = $this->contextId;
		if (!$plugin) {
			$locale = AppLocale::getLocale();

			// Add the custom block to the list of the custom block plugins in the
			// custom block manager plugin
			$customBlockManagerPlugin = PluginRegistry::getPlugin('generic', CUSTOMBLOCKMANAGER_PLUGIN_NAME);
			$blocks = $customBlockManagerPlugin->getSetting($contextId, 'blocks');
			if (!isset($blocks)) $blocks = [];


			$blockName = \Stringy\Stringy::create($this->getData('blockTitle')[$locale])->toLowerCase()->dasherize()->regexReplace('[^a-z0-9\-\_.]', '');
			if (in_array($blockName, $blocks)) {
				$blockName = uniqid($blockName);
			}
			$blocks[] = (string) $blockName;
			$customBlockManagerPlugin->updateSetting($contextId, 'blocks', $blocks);

			// Create a new custom block plugin
			import('plugins.generic.customBlockManager.CustomBlockPlugin');
			$plugin = new CustomBlockPlugin($blockName, $customBlockManagerPlugin);
			// Default the block to being enabled
			$plugin->setEnabled(true);
		}

		// update custom block plugin content
		$plugin->updateSetting($contextId, 'blockTitle', $this->getData('blockTitle'));
		$plugin->updateSetting($contextId, 'blockContent', $this->getData('blockContent'));
		$plugin->updateSetting($contextId, 'showName', $this->getData('showName'));

		parent::execute(...$functionArgs);
	}
}

