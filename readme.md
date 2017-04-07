Dynamic Price Updater v3.0.4
-=-=-=-=-=-=-=-=-=-=-=-=-=-

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
NOTE: If you have an earlier version of Updater installed please remove the files first

1. Rename the YOUR_ADMIN folder to the name of your secret foldername.
2. Rename the YOUR_TEMPLATE folders to the name of your custom template folder name.
3. upload the files included in the "Installation files" folder (they are in the correct folder structure). For reference the file paths are:

  ajax.php
  YOUR_ADMIN/includes/auto_loaders/config.dpu.php
  YOUR_ADMIN/includes/init_includes/init_dpu_config.php
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

NOTE: If you have an earlier version of Updater installed please remove the files first


Settings
--------

The module is now set through the admin, more instructions to be added later

Support
-------

As always support is located on the Zen Cart forums at:

http://www.zen-cart.com/forum/showthread.php?t=70577

Credits
-------
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