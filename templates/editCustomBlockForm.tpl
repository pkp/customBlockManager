{**
 * plugins/generic/customBlockManager/templates/editCustomBlockForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form for editing a custom sidebar block
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#customBlockForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="customBlockForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.customBlockManager.controllers.grid.CustomBlockGridHandler" op="updateCustomBlock" existingBlockName=$existingBlockName}">
	{csrf}
	{fbvFormArea id="customBlocksFormArea" class="border"}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.generic.customBlockManager.blockName" id="blockTitle" multilingual="true" value=$blockTitle}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.customBlock.content" for="blockContent"}
			{fbvElement type="textarea" multilingual=true name="blockContent" id="blockContent" value=$blockContent rich=true height=$fbvStyles.height.TALL}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.customBlock.showName" for="showName" list=true}
			{fbvElement type="checkbox" name="showName" id="showName" checked=$showName label="plugins.generic.customBlock.showName.description" value="1" translate="true"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
