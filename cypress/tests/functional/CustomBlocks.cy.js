/**
 * @file cypress/tests/functional/CustomBlocks.cy.js
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Custom Block Manager plugin tests', function() {
	it('Creates and exercises a custom block', function() {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('nav').contains('Settings').click();
		// Ensure submenu item click despite animation
		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();

		// Find and enable the plugin
		cy.get('input[id^="select-cell-customblockmanagerplugin-enabled"]').click();
		cy.get('div:contains(\'The plugin "Custom Block Manager" has been enabled.\')');
		cy.waitJQuery();

		cy.get('tr[id*="customblockmanagerplugin"] a.show_extras').click();
		cy.get('a[id*="customblockmanagerplugin-settings"]').click();

		// Create a new custom block.
		cy.get('a:contains("Add Block")').click();
		cy.wait(2000); // Avoid occasional failure due to form init taking time
		cy.get('form[id^="customBlockForm"] input[id^="blockTitle-en-"]').type('Test Custom Block');
		cy.get('textarea[name="blockContent[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Here is my custom block.');
		});
		cy.get('form[id="customBlockForm"] button[id^="submitFormButton-"]').click({force: true});
		cy.waitJQuery();
		cy.wait(500); // Make sure the form has closed
		cy.get('[role="dialog"] button:contains(\'Close\')').click();

		cy.reload();
		cy.waitJQuery();

		cy.get('button[id="appearance-button"]').click();
		cy.get('#appearance-setup-button').click();
		cy.get('#appearance-setup span:contains("test-custom-block"):first').click();
		cy.get('#appearance-setup button:contains("Save")').click();
		cy.waitJQuery();

		cy.visit('/index.php/publicknowledge');
		cy.get('div:contains("Here is my custom block.")');
	});
})
