<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockManagerPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockManagerPlugin
 *
 * Plugin to let managers add and delete custom sidebar blocks
 *
 */

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;
use PKP\plugins\PluginRegistry;

class CustomBlockManagerPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.customBlockManager.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.customBlockManager.description');
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            // If the system isn't installed, or is performing an upgrade, don't
            // register hooks. This will prevent DB access attempts before the
            // schema is installed.
            if (Application::isUnderMaintenance()) {
                return true;
            }

            if ($this->getEnabled($mainContextId)) {
                $this->import('CustomBlockPlugin');

                // Ensure that there is a context (journal or press)
                if ($request = Application::get()->getRequest()) {
                    if ($mainContextId) {
                        $contextId = $mainContextId;
                    } else {
                        $context = $request->getContext();
                        $contextId = $context ? $context->getId() : \PKP\core\PKPApplication::CONTEXT_SITE;
                    }

                    // Load the custom blocks we have created
                    $blocks = $this->getSetting($contextId, 'blocks');
                    if (!is_array($blocks)) {
                        $blocks = [];
                    }

                    // Loop through each custom block and register it
                    $i = 0;
                    foreach ($blocks as $block) {
                        PluginRegistry::register(
                            'blocks',
                            new CustomBlockPlugin($block, $this),
                            $this->getPluginPath()
                        );
                    }
                }

                // This hook is used to register the components this plugin implements to
                // permit administration of custom block plugins.
                HookRegistry::register('LoadComponentHandler', [$this, 'setupGridHandler']);
            }
            return true;
        }
        return false;
    }

    /**
     * Permit requests to the custom block grid handler
     *
     * @param string $hookName The name of the hook being invoked
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        if ($component == 'plugins.generic.customBlockManager.controllers.grid.CustomBlockGridHandler') {
            define('CUSTOMBLOCKMANAGER_PLUGIN_NAME', $this->getName());
            return true;
        }
        return false;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'manage',
                            null,
                            [
                                'plugin' => $this->getName(),
                                'category' => $this->getCategory(),
                                'action' => 'index'
                            ]
                        ),
                        $this->getDisplayName()
                    ),
                    __('plugins.generic.customBlockManager.manage'),
                    null
                )
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        return $templateMgr->fetchAjax(
            'customBlockGridUrlGridContainer',
            $dispatcher->url(
                $request,
                Application::ROUTE_COMPONENT,
                null,
                'plugins.generic.customBlockManager.controllers.grid.CustomBlockGridHandler',
                'fetchGrid'
            )
        );
    }

    /**
     * This plugin can be used site-wide or in a specific context. The
     * isSitePlugin check is used to grant access to different users, so this
     * plugin must return true only if the user is currently in the site-wide
     * context.
     *
     * @see PluginGridRow::_canEdit()
     *
     * @return bool
     */
    public function isSitePlugin()
    {
        return !Application::get()->getRequest()->getContext();
    }
}
