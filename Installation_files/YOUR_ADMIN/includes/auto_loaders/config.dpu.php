<?php

declare(strict_types=1);
// Dynamic Price Updater 5.0

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (IS_ADMIN_FLAG === true) {
    $autoLoadConfig[999][] = [
        'autoType' => 'init_script',
        'loadFile' => 'init_dpu_config.php'
    ];
} else {
    trigger_error(__FILE__ . ' loaded from catalog side, verify upload of files.', E_USER_WARNING);
    @unlink(__FILE__);
}
