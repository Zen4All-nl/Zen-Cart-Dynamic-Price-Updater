<?php

class zcDPU_Ajax extends base
{
    function dpu_update()
    {
        $stat = (empty($_POST['stat']) ? (empty($_GET['stat']) ? 'main' : $_GET['stat']) : $_POST['stat']);
        $outputType = (isset($_POST['outputType']) && $_POST['outputType'] != '' ? $_POST['outputType'] : '');
      
        $dpu = new DPU();

        switch ($stat) {
            case 'main':
            default:
                if (zen_not_null($outputType)){
                  $dpu->getDetails($outputType);
                } else {
                  $dpu->getDetails();
                }
                break;
            case 'multi':
                $dpu->getMulti();
                break;
        }
    }
}