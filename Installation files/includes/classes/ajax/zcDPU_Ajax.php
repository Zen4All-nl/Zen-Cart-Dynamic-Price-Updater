<?php

class zcDPU_Ajax extends base
{
    function dpu_update()
    {
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
    }
}