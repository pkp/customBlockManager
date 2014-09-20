<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockManagerPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockManagerPlugin
 *
 * Plugin to let managers add and delete custom sidebar blocks
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CustomBlockManagerPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.customBlockManager.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.customBlockManager.description');
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			// If the system isn't installed, or is performing an upgrade, don't
			// register hooks. This will prevent DB access attempts before the
			// schema is installed.
			if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;

			if ($this->getEnabled()) {
				// This hook is used to step in when block plugins are registered to add
				// each custom block that has been created with this plugin.
				HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));

				// This hook is used to register the components this plugin implements to
				// permit administration of custom block plugins.
				HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		$request =& $this->getRequest();

		switch ($category) {
			case 'blocks': // The system is registering block plugins
				$this->import('CustomBlockPlugin');

				// Ensure that there is a context (journal or press)
				$context = $request->getContext();
				if (!$context) return false;

				// Load the custom blocks we have created
				$blocks = $this->getSetting($context->getId(), 'blocks');
				if (!is_array($blocks)) break;

				// Loop through each custom block and register it
				$i=0;
				foreach ($blocks as $block) {
					$blockPlugin = new CustomBlockPlugin($block, $this->getName());

					// Default the block to being enabled (for newly created blocks)
					if ($blockPlugin->getEnabled() !== false) {
						$blockPlugin->setEnabled(true);
					}
					// Default the block to the right sidebar (for newly created blocks)
					if (!is_numeric($blockPlugin->getBlockContext())) {
						$blockPlugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
					}

					// Add the plugin to the list of registered plugins
					$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath() . $i] =& $blockPlugin;

					$i++;
					unset($blockPlugin);
				}
				break;
		}
		return false;
	}

	/**
	 * Permit requests to the custom block grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.customBlockManager.controllers.grid.CustomBlockGridHandler') {
			define('CUSTOMBLOCKMANAGER_PLUGIN_NAME', $this->getName());
			return true;
		}
		return false;
	}

	/**
	 * @copydoc GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('manageCustomBlocks', __('plugins.generic.customBlockManager.manage'));
		}
		return $verbs;
	}

	/**
	 * @copydoc Plugin::getManagementVerbLinkAction()
	 */
	function getManagementVerbLinkAction($request, $verb) {
		$router = $request->getRouter();

		list($verbName, $verbLocalized) = $verb;

		if ($verbName === 'manageCustomBlocks') {
			// Generate a link action for the "manage" action
			import('lib.pkp.classes.linkAction.request.AjaxLegacyPluginModal');
			$actionRequest = new AjaxLegacyPluginModal(
					$router->url($request, null, null, 'plugin', null, array('verb' => 'manageCustomBlocks', 'plugin' => $this->getName(), 'category' => 'generic')),
					$this->getDisplayName()
			);
			return new LinkAction($verbName, $actionRequest, $verbLocalized, null);
		}

		return null;
	}

	/**
	 * @copydoc GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		switch ($verb) {
			case 'manageCustomBlocks':
				$request =& $this->getRequest();
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				import('lib.pkp.classes.form.Form');
				$form = new Form($this->getTemplatePath() . 'customBlockManager.tpl');
				$pluginModalContent = $form->fetch($request);
				return true;
			default:
				return parent::manage($verb, $args, $message, $messageParams);
		}
	}
}

?>
