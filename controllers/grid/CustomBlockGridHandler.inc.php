<?php

/**
 * @file plugins/generic/customBlockManager/controllers/grid/CustomBlockGridHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomBlockGridHandler
 * @ingroup controllers_grid_customBlockManager
 *
 * @brief Handle custom block manager grid requests.
 */

use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\Role;

import('plugins.generic.customBlockManager.controllers.grid.CustomBlockGridRow');

class CustomBlockGridHandler extends GridHandler
{
    /** @var CustomBlockManagerPlugin The custom block manager plugin */
    public $plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['fetchGrid', 'fetchRow', 'addCustomBlock', 'editCustomBlock', 'updateCustomBlock', 'deleteCustomBlock']
        );
        $this->plugin = PluginRegistry::getPlugin('generic', CUSTOMBLOCKMANAGER_PLUGIN_NAME);
    }


    //
    // Overridden template methods
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if ($request->getContext()) {
            import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
            $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        } else {
            import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
            $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        }
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;

        // Set the grid title.
        $this->setTitle('plugins.generic.customBlockManager.customBlocks');
        // Set the grid instructions.
        $this->setEmptyRowText('plugins.generic.customBlockManager.noneCreated');

        // Get the blocks and add the data to the grid
        $customBlockManagerPlugin = $this->plugin;
        $blocks = $customBlockManagerPlugin->getSetting($contextId, 'blocks');
        $gridData = [];
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                $gridData[$block] = [
                    'title' => $block
                ];
            }
        }
        $this->setGridDataElements($gridData);

        // Add grid-level actions
        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'addCustomBlock',
                new AjaxModal(
                    $router->url($request, null, null, 'addCustomBlock'),
                    __('plugins.generic.customBlockManager.addBlock'),
                    'modal_add_item'
                ),
                __('plugins.generic.customBlockManager.addBlock'),
                'add_item'
            )
        );

        // Columns
        $this->addColumn(
            new GridColumn(
                'title',
                'plugins.generic.customBlockManager.blockName',
                null,
                'controllers/grid/gridCell.tpl'
            )
        );
    }

    //
    // Overridden methods from GridHandler
    //
    /**
     * @copydoc GridHandler::getRowInstance()
     */
    public function getRowInstance()
    {
        return new CustomBlockGridRow();
    }

    //
    // Public Grid Actions
    //
    /**
     * An action to add a new custom block
     *
     * @param array $args Arguments to the request
     * @param PKPRequest $request Request object
     */
    public function addCustomBlock($args, $request)
    {
        // Calling editCustomBlock with an empty ID will add
        // a new custom block.
        return $this->editCustomBlock($args, $request);
    }

    /**
     * An action to edit a custom block
     *
     * @param array $args Arguments to the request
     * @param PKPRequest $request Request object
     *
     * @return string Serialized JSON object
     */
    public function editCustomBlock($args, $request)
    {
        $blockName = $request->getUserVar('blockName');
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;
        $this->setupTemplate($request);

        $customBlockPlugin = null;
        // If this is the edit of the existing custom block plugin,
        if ($blockName) {
            // Create the custom block plugin
            import('plugins.generic.customBlockManager.CustomBlockPlugin');
            $customBlockPlugin = new CustomBlockPlugin($blockName, CUSTOMBLOCKMANAGER_PLUGIN_NAME);
        }

        // Create and present the edit form
        import('plugins.generic.customBlockManager.controllers.grid.form.CustomBlockForm');
        $customBlockManagerPlugin = $this->plugin;
        $template = $customBlockManagerPlugin->getTemplateResource('editCustomBlockForm.tpl');
        $customBlockForm = new CustomBlockForm($template, $contextId, $customBlockPlugin);
        $customBlockForm->initData();
        return new JSONMessage(true, $customBlockForm->fetch($request));
    }

    /**
     * Update a custom block
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return string Serialized JSON object
     */
    public function updateCustomBlock($args, $request)
    {
        $pluginName = $request->getUserVar('existingBlockName');
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;
        $this->setupTemplate($request);

        $customBlockPlugin = null;
        // If this was the edit of the existing custom block plugin
        if ($pluginName) {
            // Create the custom block plugin
            import('plugins.generic.customBlockManager.CustomBlockPlugin');
            $customBlockPlugin = new CustomBlockPlugin($pluginName, CUSTOMBLOCKMANAGER_PLUGIN_NAME);
        }

        // Create and populate the form
        import('plugins.generic.customBlockManager.controllers.grid.form.CustomBlockForm');
        $customBlockManagerPlugin = $this->plugin;
        $template = $customBlockManagerPlugin->getTemplateResource('editCustomBlockForm.tpl');
        $customBlockForm = new CustomBlockForm($template, $contextId, $customBlockPlugin);
        $customBlockForm->readInputData();

        // Check the results
        if ($customBlockForm->validate()) {
            // Save the results
            $customBlockForm->execute();
            return DAO::getDataChangedEvent();
        }
        // Present any errors
        return new JSONMessage(true, $customBlockForm->fetch($request));
    }

    /**
     * Delete a custom block
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return string Serialized JSON object
     */
    public function deleteCustomBlock($args, $request)
    {
        $blockName = $request->getUserVar('blockName');
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;

        // Delete all the entries for this block plugin
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        $pluginSettingsDao->deleteSetting($contextId, $blockName, 'enabled');
        $pluginSettingsDao->deleteSetting($contextId, $blockName, 'context');
        $pluginSettingsDao->deleteSetting($contextId, $blockName, 'seq');
        $pluginSettingsDao->deleteSetting($contextId, $blockName, 'blockContent');

        // Remove this block plugin from the list of the custom block plugins
        $customBlockManagerPlugin = $this->plugin;
        $blocks = $customBlockManagerPlugin->getSetting($contextId, 'blocks');
        $newBlocks = array_diff($blocks, [$blockName]);
        ksort($newBlocks);
        $customBlockManagerPlugin->updateSetting($contextId, 'blocks', $newBlocks);
        return DAO::getDataChangedEvent();
    }
}
