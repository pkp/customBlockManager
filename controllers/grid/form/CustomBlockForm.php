<?php

/**
 * @file plugins/generic/customBlockManager/controllers/grid/form/CustomBlockForm.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomBlockForm
 *
 * @ingroup controllers_grid_customBlockManager
 *
 * Form for press managers to create and modify sidebar blocks
 *
 */

namespace APP\plugins\generic\customBlockManager\controllers\grid\form;

use APP\plugins\generic\customBlockManager\CustomBlockPlugin;
use APP\plugins\generic\customBlockManager\CustomBlockManagerPlugin;
use APP\template\TemplateManager;
use Illuminate\Support\Str;
use PKP\facades\Locale;
use PKP\form\Form;
use PKP\plugins\PluginRegistry;
use PKP\core\PKPApplication;

class CustomBlockForm extends Form
{
    /** @var ?int Context (press / journal) ID */
    public ?int $contextId;

    public ?CustomBlockPlugin $plugin;

    public ?CustomBlockManagerPlugin $customBlockManagerPlugin;
    /**
     * Constructor
     *
     * @param string $template the path to the form template file
     */
    public function __construct(string $template, ?int $contextId, ?CustomBlockPlugin $plugin, ?CustomBlockManagerPlugin $customBlockManagerPlugin)
    {
        parent::__construct($template);

        $this->contextId = $contextId;
        $this->plugin = $plugin;
        $this->customBlockManagerPlugin = $customBlockManagerPlugin;

        // Add form checks
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'blockTitle', 'required', 'plugins.generic.customBlock.nameRequired'));
    }

    /**
     * Initialize form data from current group group.
     */
    public function initData()
    {
        $contextId = $this->contextId;
        $plugin = $this->plugin;

        $request = PKPApplication::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

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
    public function readInputData()
    {
        $this->readUserVars(['blockTitle', 'blockContent', 'showName']);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->plugin;
        $contextId = $this->contextId;
        if (!$plugin) {
            $locale = Locale::getLocale();

            // Add the custom block to the list of the custom block plugins in the
            // custom block manager plugin
            $blocks = $this->customBlockManagerPlugin->getSetting($contextId, 'blocks') ?? [];

            $blockName = preg_replace('[^a-z0-9\-\_.]', '', Str::of($this->getData('blockTitle')[$locale])->lower()->kebab());
            if (in_array($blockName, $blocks)) {
                $blockName = uniqid($blockName);
            }
            $blocks[] = (string) $blockName;
            $this->customBlockManagerPlugin->updateSetting($contextId, 'blocks', $blocks);

            // Create a new custom block plugin
            $plugin = new CustomBlockPlugin($blockName, $this->customBlockManagerPlugin);
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
