# Dynamic Price Updater v5 for Zen Cart

## Current Status March 2023
This useful plugin has been somewhat neglected in recent years, I assume as it has been working for most people. I made many changes during that time which, due to multiple forks, were difficult to implement back into the plugin.  
This I'm currently attempting to rectify with this fork.  
The development branch "158" should be working while I gradually (or not) review/scrap/introduce my modifications to make it both php8.2+ compliant, more easily understandable and into a state fit for peer review/to go into the core codebase.  

The readme below may be out of sync with the codebase/is also a work in progress, so as always, ALL testing should be done on a development installation and NEVER a production site.

At the moment I would not suggest you try this out yet, due to the multiple changes that will be introduced. But if you have an existing bug or problem, then yes please try to replicate it with this code and a vanilla ZC158 codebase before creating an Issue at this repository

https://github.com/dbltoe/Zen-Cart-Dynamic-Price-Updater

torvista

## Compatibility
Compatible with Zen Cart v.1.5.8+ and php 8.1+.

## Introduction
Dynamic Price Updater (DPU) automatically updates the displayed price on the product page when an attribute selection is changed or the cart quantity is modified.

## Installation / Upgrade
NOTE: If your installed version of DPU is older than 3.0 please remove all those DPU files first and check shop is operating correctly before proceeding with this installation.

1. Rename the YOUR_ADMIN folder to match YOUR Admin folder name.
2. Rename the YOUR_TEMPLATE folders to the name of your custom template folder names.
3. Upload the files included in the "Installation_files" folder (they are in the correct folder structure).
 
 For reference the file paths are:
  - YOUR_ADMIN/includes/auto_loaders/config.dpu.php
  - YOUR_ADMIN/includes/init_includes/init_dpu_config.php
  - YOUR_ADMIN/includes/installers/dpu/ (all files in the folder)
  - YOUR_ADMIN/includes/languages/english/extra_definitions/dynamic_price_updater.php
  - images/ajax-loader.gif
  - includes/auto_loaders/config.dynamic_price_updater.php
  - includes/classes/ajax/zcDPU_Ajax.php
  - includes/classes/dynamic_price_updater.php
  - includes/languages/english/extra_definitions/dynamic_price_updater.php
  - includes/modules/pages/product_info/on_load_dpu.js
  - includes/modules/pages/product_music_info/on_load_dpu.js
  - includes/modules/sideboxes/YOUR_TEMPLATE/dynamic_price_updater_sidebox.php
  - includes/templates/YOUR_TEMPLATE/jscript/jscript_dynamic_price_updater.php
  - includes/templates/YOUR_TEMPLATE/sideboxes/tpl_dynamic_price_updater_sidebox.php

4. Log in to Admin and the module will install automatically.
5. By default DPU is disabled. Go to Configuration=>Dynamic Price Updater and set the status to true to enable DPU.
6. Optional: to allow DPU to update the display of product quantity (stock) available (in particular if using Stock By Attributes):  
   6.a. edit includes/templates/YOUR_TEMPLATE/templates/tpl_product_info_display.php with a plain text editor.  
   6.b. find: 
$products_quantity .  
   6.c. and surround it by a span tag (replace with below) so that it will look like:
`<span id="productDetailsList_product_info_quantity">' . $products_quantity . '</span>` 
   6.d. perform the same for each PRODUCT_TYPE file such as product_music_info, etc...

## Uninstall
1) Remove all the DPU files.
2) Copy and Paste the following lines into Admin->Tools->Install SQl Patches and Run/Send

SELECT @DPUgID := configuration_group_id  
FROM configuration_group where configuration_group_title = 'Dynamic Price Updater Config';  
DELETE FROM configuration WHERE configuration_group_id = @DPUgID;
DELETE FROM admin_pages WHERE page_key = 'configDynamicPriceUpdater';  
DELETE FROM configuration_group WHERE configuration_group_id = @DPUgID;

## Settings
- Dynamic Price Updater Status 	true - Allow disabling DPU globally.
- Dynamic Price Updater Version 	3.2.0 - Radio button style presentation of installed version
- Dynamic Price Updater Version Check? 	true - Allow DPU to check for latest ZC issued version?
- Where to display the price 	productPrices - Class in which DPU prices are to be shown/updated
- Define used to set a variable for this script 	cart_quantity - - Value of name field for form that contains attributes and quantities.
- Where to display the weight 	productWeight - id object that is to display the product weight.
- show a small loading graphic 	true - While data is being retrieved from the system, show a loading picture.
- Show currency symbols 	true - If desired to see the symbols ($) associated with the customer's chosen currency then choose true.
- Show product quantity 	false - This is the quantity of the specifically selected/entered combination of attributes that are in the cart.
- Where to display the second price cartAdd - This is the id of the location to display the calculated price as a second location.
- Show sidebox currency symbols 	true - Show currency symbols in the sidebox (when displayed).
- Show alternate text for partial selection 	start_at_least 	 - When selections are being made that affect the price of the product, what alternate text if any should be shown to the customer.  For example if when no selections have been made, the ZC starting at text may be displayed.  When one selection of many has been made, then the text may be changed to at least this amount indicating that there are selections to be made that could increase the price.  Then once all selections have been made as expected/required the text is or should change to something like Your Price:.
- Show or update the display of out-of-stock 	quantity_replace 	Allows display of the current stock status of a product while the customer remains on the product information page and offers control about the ajax update when the product is identified as out-of-stock.
- Modify minimum attribute display price 	all 	On what should the minimum display price be based for product with attributes? Only product that are priced by attribute or for all product that have attributes?
- The id tag for product_quantity 	productDetailsList_product_info_quantity 	This is the ID where your product quantity is displayed.

## Support
Plugin Support: http://www.zen-cart.com/forum/showthread.php?t=70577

Reporting Bugs: https://github.com/dbltoe/Zen-Cart-Dynamic-Price-Updater/issues  

## Changelog:
5.0:
- bugfixes: #16 test on ZC158a Hewlett Packard - by attributes with Special% no SALE: shows only loading graphic
- addition of much debugging output for Javascript console and class processing
- formatting of files, general IDE-recommended changes, strict comparisons...
- relocated javascript to template jscript folder
- support for php 8.2: added variable declarations
- changed Installation_files/includes/classes/ajax/zcDPU_Ajax.php line 176 to change total_before_discounts to show_total_before_discounts.
- Dropped support for Zen Cart versions<1.5.8

4.0.0 Beta3:
- moving the class code from ```includes/classes/dynamic_price_updater.php``` to ```includes/classes/ajax/zcDPU_Ajax.php```
- Removing the ancient code, using JSON or XML, and replaced it with the build in native Zen Cart Ajax functionality
- Drop support for ZC versions <1.5.6
- PHP version >= 5.6
- Updated optional jquery version to 3.4.1
- fixed missing cart_quantity from ```$_POST```
- Removed ajax.php, as this is included from ZC1.5.6
- Added updates form [Torvista](https://github.com/torvista), [mc12345678](https://github.com/mc12345678), and [Zen4all](https://github.com/Zen4All)

3.2.0:
- Identified this version as 3.2.0 because 1) this update provided significant new functionality and modified/provided
    new database features and 2) 3.1.0 had previously been identified in the github path/history and did not want to
    cause undo confusion.
- Commented out code that performed no operation/action.
- Added an update to the customer's latest page when using an ajax style call to improve display of
    information on the who's online admin page.
- Added a method to update price text to represent when none and less than all attributes have been selected.
- Added DPU_ATTRIBUTES_MULTI_PRICE_TEXT to be able to control which of the pretext(s) to allow to show.
- Added a variable to be able to update/display the text before the first price when price includes a special and/or a sale.
- Added ability to update what is known as the normalprice which is the price/value that is crossed out when there is a sale or other special.
- Added stock quantity update capability which incorporates the possibility of operating with ZC 1.5.x and control how the information is replaces other content.  Guidance for display modifications provided in readme.txt.
- Corrected use of float casting to use code published in ZC 1.5.6 to support strict server mode.
- Reworked code to better account and control sub-functional area execution.
- Removed single quotes from SQL queries that included casted integers to minimize further processing.
- Expanded attribute text switching to more than product priced by attribute such that should apply to products that have attributes that affect the base price.
- Corrected price display and determination to include pricing associated with text (word and/or letter pricing, etc...)
- Updated javascript side to use exactly equal to instead of loosely comparing two values (in a majority of examples).
- Switched javascript calls to use dot notation where able.
- Added a fallback attempt for collecting/setting JSON response if responseJSON is empty but a non-fault responseText is provided.
- Moved javascript variable declaration out of most function calls.
- Corrected an issue reported by mvstudio of where the expected text to be in front of the priced item would not be displayed if the imgLoc variable in the includes/modules/pages/PRODUCT_TYPE/jscript_dynamic_price_updater.php file was not set to "replace".  Essentially the result was that pspClass would be blank when sending to ajax.
- Added change detection and capture of the html element textarea and changed the text detection to an input event instead of a keyup event.  This allows capture/detection of pasted content without firing off multiple events for the same modification.  Additional testing may reveal the need to incorporate other event listeners. The process would be similar as shown for the number case.
- Updated ajax.php file to prevent known spiders from performing ajax requests.  This is from the ZC 1.5.6 version of the file.
- Moved the status notification from an alert to a console log when zcJS is not used and a 200 status result is not received.

3.0.8:
- Added a switch and code to support deactivating the use of currency symbols in the sidebox, similar to the display of the base product price.
- Corrected the use of the notifier to support using an observer to provide a prefix for displayed prices.
- Removed the specific comparison of an attribute being a checkbox for when processing attributes to identify the lowest price of the product.  Inclusion of the attribute into the base price is controlled through the attributes controller and should not be just blindly omitted within this code.
- Updated the installer including to incorporate the version checker provided in the current alpha release of ZC 1.5.6 and to use that code instead of the built in code and instead of pulling the recent file into the distribution of the plugin (and then have multiple such versions out and about.)
- Updated the installer to write a debug log if the installer files have been incorrectly placed in the catalog side of the installer.
- Updated the installer to expect an admin to be logged in, and the page not currently being the login page or as a result of selecting the logoff option.
- Updated code for initial expected changes for PHP 7.2.
- Corrected the encoding (BOM) of the file that provides the ajax processing.
- Corrected issue with price being displayed as zero when it should be otherwise displayed.
- Added the general page information (though not with zen_href_link) and support understanding what the customer is looking at at the point of the call.
- Updated the jscript code for the product_music to match the product_info code.
- Added template jscript code to attempt to load jquery if it has not previously included or loaded to load jquery 1.12.4

3.0.7:
-  Corrected issue that price of product without attributes was not incorporated/considered because the dpu javascript code basically prevented overwriting the price until corrections were made  in version 3.0.6 that would automatically rewrite the current price display based on what dpu determined based on the product.  (This additional feature was added to support display of the appropriate information when returning to the product from the shopping cart.)
-  Because the price is automatically calculated based on arriving at a product, if a product is priced by attributes and not all of the "mandatory" attributes are selected then the price was only showing the price point of those that were selected.  The code now calculates the lowest available price based on the selections already made.  The displayed text has not been edited to account for this "feature" yet but could be modified to support the condition.

3.0.6:
-  Modified/updated the class information to provide the sidebox data.
-  Correct additional javascript errors to prevent having to catch them.
-  Modified sidebox to not display/be active if the page's javascript for dpu is not active.
-  Added additional type casting to force data to the expected type.
-  Incorporate the tax calculation correction posted in the forum into the code.
-  Removed the use of default parameters because some browsers do/did not support it.
-  Add separate control for update checking of this plugin.
-  Updated installer version check to ZC 1.5.5e version.
-  Corrected installer code to validate new version exists.

3.0.5:
- Improved installation/installer
-  Previous process for installation included a delete of previous settings.  This is now performed using insert ignore style sql and as necessary to update settings with a focus of configuration sort order.
- Rewrote the data collection about a product to improve ability to compare to the active cart.
-  If using the class function of insertProducts, the resulting prid is an integer only and does not include the possibility of addressing attributes based on the change(s) made here.  This is equivalent to a standard product being listed in the product listing screen being added to the cart where attributes are "typically" not selectable.
- Modified quantity reporting to provide quantity already in cart for selected attributes if the quantity is greater than zero.
- Incorporated forum correction for operation when DPU_SHOW_LOADING_IMAGE is set to false.
- Added JSON response information when retrieving results.
- Incorporated the use of Zen Cart's provided zcJS when it is available.
- Made the DPU class part of the overall system to better support additional observers as needed.
- Maintained the "default" XML response if JSON is not requested to align with potential historical alterations.
- Added the restoration of the price display if the transaction fails.
- Added jscript function to perform data/screen update/swap.

3.0.4 has a few bugfixes as well as general improvements for the overall operation.
- Added data sanitization to the database queries.
- Incorporated full database table names instead of providing some sort of prefix to the anticipated table name. 
- Added ZC 1.5.5e ajax.php file for use to take the place of dpu_ajax.php
- Added zcDPU_Ajax class to support AJAX calls.
- Incorporated currency number formatting based on the currency selected in the session instead of with a hard value of 2.
- Converted code to be PHP 7.2 compliant regarding known deprecations.
- Split out the on_load code to its own file.
- Applied parenthesis around actions to be taken for if statements.
- Correct a potential javascript error if secondPrice isn't being used.
- Added ability for image to replace the text to be updated such that there is no line height adjustment.
- Remove the eval function and instead call the function dynamically that is expected.
- Converted single quotes to double quotes.
- Added evaluation of number style fields as an additional option.
- Made monitoring of entered data by event as an added event instead of a replacement event to improve Javascript interactions.
- Removed unnecessary underscore from variables.
- Added semi-colons to end of functions for appropriate Javascript syntax.
- Removed the code from dpu_ajax.php as the code is otherwise incorporated to use the ZC ajax.php file.
- Added tax for display of price with currency symbols.
- Replaced intval() with (int) casting.
- Copied changes from standard product_info to 
- product_music_info.

3.0.3 has a minor bugfix, in jscript files
- Moving all the defines to language files, or the database
- Making DPU multilingual
- updating code to accommodate proper database calls, Zen Cart compliant.
- Added a real sidebox, only the content is dynamically built.
- Added an admin configuration, to make changes to the configurable defines of DPU.

## Credits
Version 3.2.0 brought to you by mc12345678: http://mc12345678.com with thankyous to:  
mvstudio for identifying issues with strict operation, as well as edge case operation.  
izar74 for a potential way to display out-of-stock information.  
diamond1 for identifying the need of these instructions to contain just a little more.  
Calljj for the idea of updating the "base" price while making attribute selections on product that are on special or on sale.  This allows seeing the true price reduction/difference for chosen options.

Versions 3.0.5 through 3.0.8 brought to you by mc12345678: http://mc12345678.com

V3.0.4 base content: Erik Kerkhoven (Design75) https://zen4all.nl   brought by mc12345678: http://mc12345678.com

Original author: Dan Parry (Chrome) http://chrome.me.uk

Thanks to Jay (4jDesigns on the forum) for finding the 1% tax issue.

Thanks to Thomas Achache for giving me the inspiration for the mechanism.

Thanks to web28 for the idea of preventing loading under certain circumstances.

Thanks to Matt (lankeeyankee) for testing and correcting my inevitable mistakes.

## Copyright
Parts of copyright  
2019 torvista https://github.com/torvista  
2017 mc12345678 https://mc12345678.com  
2013 Erik Kerkhoven (Design75)  https://zen4All.nl  
2009 Dan Parry (Chrome)

