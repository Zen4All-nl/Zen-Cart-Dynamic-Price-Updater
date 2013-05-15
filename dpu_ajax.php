<?php
/**
* Dynamic Price Updater V2.1
* (c) D Parry (Chrome) 2009 (admin@chrome.me.uk)
* This module is released under the GNU/GPL licence... Really... Go look it up
*/

require_once('includes/application_top.php');

$stat = (empty($_POST['stat']) ? (empty($_GET['stat']) ? 'main' : $_GET['stat']) : $_POST['stat']);

$dpu = new DPU();
switch ($stat) {
  case 'main':
  default:
    $dpu->getDetails();
    break;
  case 'multi':
    $dpu->getMulti();
    break;
}