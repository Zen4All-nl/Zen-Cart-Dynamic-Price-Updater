Dynamic Price Updater v3.0
-=-=-=-=-=-=-=-=-=-=-=-=-=-

This is (IMHO) an improved version of DPU 2
- Moving all the defines to language files, or the database
- Making DPU multilingual
- updating code to acommodate proper database calls, Zen Cart compliant.
- Added a real sidebox, only the content is dynamically build
- Added an admin configuration, to make changes to the configurable defines of DPU

Installation / Upgrade
------------
NOTE: If you have an earlier version of Updater installed please remove the files first

1. Rename the YOUR_ADMIN folder to the name of your secret foldername.
2. Rename the YOUR_TEMPLATE folders to the name of your cutom template folder name.
3. upload the files included (they are in the correct folder structure). For reference the file paths are:

  dpu_ajax.php
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
  includes/YOUR_TEMPLATE/sideboxes/tpl_dynamic_price_updater_sidebox.php

4. Log in to your webshop's admin panel, and the module will install automatically. There are no seperate sql files needed.
5. Installation is now complete.

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
This update (V3.0) : Erik Kerkhoven (Design75) http://zen4all.nl

Original author : Dan Parry (Chrome) http://chrpme.me.uk

Thanks to Jay (4jDesigns on the forum) for finding the 1% tax issue

Thanks to Thomas Achache to for giving me the inspiration for the mechanism

Thanks to web28 for the idea of preventing loading under certain circumstances

Thanks to Matt (lankeeyankee) for testing and correcting my inevitable mistakes

Copyright
---------
Parts of copyright
2009 Dan Parry (Chrome)
2013 Erik Kerkhoven (Design75) Zen4All.nl