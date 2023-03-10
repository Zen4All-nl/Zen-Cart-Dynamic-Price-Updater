<?php

declare(strict_types=1);
/**
 * Dynamic Price Updater V5.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75) / mc12345678 / torvista
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: 2023 Mar 10
 */

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
