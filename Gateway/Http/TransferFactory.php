<?php

namespace Fatchip\Computop\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        TransferBuilder $transferBuilder
    ) {
        $this->transferBuilder = $transferBuilder;
    }

    protected function redirectTest($url, $request)
    {
        $url .= "?".http_build_query($request);
        // this method is just for testing!
        #header("Location: ".$url);
        #die();
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        if (!empty($request['uri'])) {
            $this->transferBuilder->setUri($request['uri']);
            $url = $request['uri'];
            unset($request['uri']);
            $this->redirectTest($url, $request);
        }



        $this->transferBuilder->setBody($request);
        $this->transferBuilder->setMethod('POST');
        #$this->transferBuilder->setHeaders(['foo' => 'bar']); // not extra headers needed for now I think

        return $this->transferBuilder->build();
    }
}
