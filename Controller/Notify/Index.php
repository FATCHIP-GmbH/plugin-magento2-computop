<?php

namespace Fatchip\Computop\Controller\Notify;

class Index extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        ///@TODO
        error_log(date("Y-m-d H:i:s - ")."REQUEST ".print_r($_REQUEST, true).PHP_EOL, 3, BP."/computop_notify.log");
        error_log(date("Y-m-d H:i:s - ")."SERVER ".print_r($_SERVER, true).PHP_EOL, 3, BP."/computop_notify.log");
    }
}
