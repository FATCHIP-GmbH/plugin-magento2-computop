<?php

namespace Fatchip\Computop\Helper;

use Fatchip\Computop\Model\ComputopConfig;

class Payment extends Base
{
    /**
     * List of all currently available Computop payment methods
     *
     * @var array
     */
    protected $availablePayments = [
        ComputopConfig::METHOD_CREDITCARD,
        ComputopConfig::METHOD_GIROPAY,
    ];

    /**
     * Return all available payment types
     *
     * @return array
     */
    public function getAvailablePaymentTypes()
    {
        return $this->availablePayments;
    }

    /**
     * Generates random transaction id for TransID parameter
     * Taken from library-computop generateTransID() method
     *
     * @param  int $digitCount
     * @return string
     */
    public function getTransactionID($digitCount = 12)
    {
        mt_srand((double)microtime() * 1000000);

        $transID = (string)mt_rand();
        // y: 2 digits for year
        // m: 2 digits for month
        // d: 2 digits for day of month
        // H: 2 digits for hour
        // i: 2 digits for minute
        // s: 2 digits for second
        $transID .= date('ymdHis');
        // $transID = md5($transID);
        return substr($transID, 0, $digitCount);
    }
}
