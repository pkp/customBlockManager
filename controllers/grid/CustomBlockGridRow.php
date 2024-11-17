<?php

/**
 * @file plugins/generic/customBlockManager/controllers/grid/CustomBlockGridRow.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomBlockGridRow
 *
 * @ingroup controllers_grid_customBlockManager
 *
 * @brief Handle custom blocks grid row requests.
 */

namespace APP\plugins\generic\customBlockManager\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class CustomBlockGridRow extends GridRow
{
    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize()
     *
     * @param null|mixed $template
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $blockName = $this->getId();
        if (!empty($blockName)) {
            $router = $request->getRouter();

            // Create the "edit custom block" action
            $this->addAction(
                new LinkAction(
                    'editCustomBlock',
                    new AjaxModal(
                        $router->url($request, null, null, 'editCustomBlock', null, ['blockName' => $blockName]),
                        __('grid.action.edit'),
                        null,
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            // Create the "delete custom block" action
            $this->addAction(
                new LinkAction(
                    'deleteCustomBlock',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'deleteCustomBlock', null, ['blockName' => $blockName]),
                        'negative'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\customBlockManager\controllers\grid\CustomBlockGridRow', '\CustomBlockGridRow');
}
