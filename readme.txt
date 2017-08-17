Dynamic Price Updater v3.0.7
-=-=-=-=-=-=-=-=-=-=-=-=-=-

3.0.7, What changed:
-  Corrected issue that price of product without attributes was not incorporated/considered because
      the dpu javascript code basically prevented overwriting the price until corrections were made
      in version 3.0.6 that would automatically rewrite the current price display based on what
      dpu determined based on the product.  (This additional feature was added to support display of
      the appropriate information when returning to the product from the shopping cart.)
-  Because the price is automatically calculated based on arriving at a product, if a product is
      priced by attributes and not all of the "mandatory" attributes are selected then the price
      was only showing the price point of those that were selected.  The code now calculates the 
      lowest available price based on the selections already made.  The displayed text has not been
      edited to account for this "feature" yet, but could be modified to support the condition.

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
-  Previous process for installation included a delete
   of previous settings.  This is now performed using
   insert ignore style sql and as necessary to update
   settings with a focus of configuration sort order.
- Rewrote the data collection about a product to improve ability to compare to the active cart.
-  If using the class function of insertProducts,
   the resulting prid is an integer only and does not
   include the possibility of addressing attributes
   based on the change(s) made here.  This is equivalent
   to a standard product being listed in the product listing
   screen being added to the cart where attributes are
   "typically" not selectable.
- Modified quantity reporting to provide quantity already
   in cart for selected attributes if the quantity is
   greater than zero.
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


This is (IMHO) an improved version of DPU 2

Installation / Upgrade
------------
NOTE: If you have a version of Updater installed earlier than 3.0 please remove the files first

1. Rename the YOUR_ADMIN folder to the name of your secret foldername.
2. Rename the YOUR_TEMPLATE folders to the name of your custom template folder name.
3. upload the files included in the "Installation files" folder (they are in the correct folder structure). For reference the file paths are:

  ajax.php
  YOUR_ADMIN/includes/auto_loaders/config.dpu.php
  YOUR_ADMIN/includes/init_includes/init_dpu_config.php
  YOUR_ADMIN/includes/installers/dpu/ (all files in the folder)
  YOUR_ADMIN/includes/languages/english/extra_definitions/dynamic_price_updater.php
  images/ajax-loader.gif
  includes/auto_loaders/config.dynamic_price_updater.php
  includes/classes/dynamic_price_updater.php
  includes/languages/english/extra_definitions/dynamic_price_updater.php
  includes/modules/pages/product_info/jscript_ajax_updater.php
  includes/modules/pages/product_music_info/jscript_ajax_updater.php
  includes/modules/sideboxes/YOUR_TEMPLATE/dynamic_price_updater_sidebox.php
  includes/templates/YOUR_TEMPLATE/sideboxes/tpl_dynamic_price_updater_sidebox.php

  dpu_ajax.php has been removed from the fileset as far as necessary code. After all files are in the place provided it may be removed from the server.

4. Log in to your webshop's admin panel, and the module will install automatically. There are no seperate sql files needed.
5. Installation is now complete.
6. By default DPU is disabled. Go to configuration=>Dynamic Price Updater set the status to true to enable DPU

NOTE: If you have a version of Updater installed earlier than 3.0 please remove the files first


Settings
--------

The module is now set through the admin, more instructions to be added later

As of Version 3.0.6 the settings include the following configuration options
Dynamic Price Updater Status 	true - Allow disabling DPU in whole
Dynamic Price Updater Version 	3.0.6 - Radio button style presentation of installed version
Dynamic Price Updater Version Check? 	true - Allow DPU to check for latest ZC issued version?
Where to display the price 	productPrices - Class in which DPU prices are to be shown/updated
Define used to set a variable for this script 	cart_quantity - Value of name field for form that contains attributes and quantities.
Where to display the weight 	productWeight - id object that is to display the product weight.
show a small loading graphic 	true - While data is being retrieved from the system, show a loading picture.
Show currency symbols 	true - If desired to see the symbols ($) associated with the customer's chosen currency then choose true.
Show product quantity 	false - This is the quantity of the specifically selected/entered combination of attributes that are in the cart.
Where to display the second price cartAdd - This is the id of the location to display the calculated price as a second location.

Support
-------

As always support is located on the Zen Cart forums at:

http://www.zen-cart.com/forum/showthread.php?t=70577

Credits
-------

Versions 3.0.5 and 3.0.6 brought to you by mc12345678: http://mc12345678.com

This update (V3.0.4 base content) : Erik Kerkhoven (Design75) http://zen4all.nl brought by mc12345678: http://mc12345678.com

Original author : Dan Parry (Chrome) http://chrome.me.uk

Thanks to Jay (4jDesigns on the forum) for finding the 1% tax issue

Thanks to Thomas Achache for giving me the inspiration for the mechanism

Thanks to web28 for the idea of preventing loading under certain circumstances

Thanks to Matt (lankeeyankee) for testing and correcting my inevitable mistakes

Copyright
---------
Parts of copyright
2009 Dan Parry (Chrome)
2013 Erik Kerkhoven (Design75) Zen4All.nl
2017 mc12345678 http://mc12345678.com