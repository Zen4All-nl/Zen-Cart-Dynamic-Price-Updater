Dynamic Price Updater v3.0
-=-=-=-=-=-=-=-=-=-=-=-=-=-

This is an (IMHO) an improved version of DPU 2.
- Moving all the defines to language files, or the database
- Making DPU multilingual
- updating code to acoomodate proper database calls, Zen Cart compliant.
- Added a real sidebox, only the content is dynamically build
- Added an admin configuration, to make changes to the configurable defines of DPU

Installation
------------

Simply upload the files included (they are in the correct folder structure) and job done! For reference the file paths are:

dpu_ajax.php
includes/classes/dynamic_price_updater.php
includes/modules/pages/product_info/jscript_ajax_updater.php
includes/modules/sideboxes/YOUR_TEMPLATE/dynamic_price_updater_sidebox.php
includes/YOUR_TEMPLATE/sideboxes/tpl_dynamic_price_updater_sidebox.php

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