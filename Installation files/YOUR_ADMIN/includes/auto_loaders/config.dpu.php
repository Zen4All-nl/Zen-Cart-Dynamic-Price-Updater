<?php
// Dynamic Price Updater

if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
} 

if (IS_ADMIN_FLAG === true) {
  $autoLoadConfig[999][] = array(
    'autoType' => 'init_script',
    'loadFile' => 'init_dpu_config.php'
  );
} else {
  @unlink(__FILE__);
}

// uncomment the following line to perform a uninstall
// $uninstall = 'uninstall';