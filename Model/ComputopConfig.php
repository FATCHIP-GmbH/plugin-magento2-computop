<?php

namespace Fatchip\Computop\Model;

/**
 * Collection of constant values
 */
abstract class ComputopConfig
{
    /* Module version */
    const MODULE_VERSION = '0.0.6';

    /* Payment method codes */
    const METHOD_CREDITCARD = 'computop_creditcard';
    const METHOD_DIRECTDEBIT = 'computop_directdebit';
    const METHOD_PAYPAL = 'computop_paypal';
    const METHOD_KLARNA = 'computop_klarna';
    const METHOD_GIROPAY = 'computop_giropay';
    const METHOD_IDEAL = 'computop_ideal';



    const STATUS_CODE_SUCCESS = '00000000';

    const STATUS_AUTHORIZED = 'AUTHORIZED';

    const STATUS_OK = 'OK';
}
