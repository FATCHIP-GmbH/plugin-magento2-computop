<?php

namespace Fatchip\Computop\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

class Handler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        // do things
    }
}
