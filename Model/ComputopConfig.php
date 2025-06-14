<?php

namespace Fatchip\Computop\Model;

/**
 * Collection of constant values
 */
abstract class ComputopConfig
{
    /* Module version */
    const MODULE_VERSION = '1.2.1';
    const MODULE_NAME = 'Fatchip_Computop';

    /* Payment method codes */
    const METHOD_CREDITCARD = 'computop_creditcard';
    const METHOD_DIRECTDEBIT = 'computop_directdebit';
    const METHOD_PAYPAL = 'computop_paypal';
    const METHOD_KLARNA = 'computop_klarna';
    const METHOD_IDEAL = 'computop_ideal';
    const METHOD_EASYCREDIT = 'computop_easycredit';
    const METHOD_AMAZONPAY = 'computop_amazonpay';

    const STATUS_CODE_SUCCESS = '00000000';

    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_OK = 'OK';
    const STATUS_PENDING = 'PENDING';

    const QUOTE_REFNR_PREFIX = 'tmp_';
}
