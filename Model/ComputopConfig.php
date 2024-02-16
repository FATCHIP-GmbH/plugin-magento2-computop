<?php

namespace Fatchip\Computop\Model;

/**
 * Collection of constant values
 */
abstract class ComputopConfig
{
    /* Module version */
    const MODULE_VERSION = '0.0.3';

    /* Payment method codes */
    const METHOD_CREDITCARD = 'computop_creditcard';
    const METHOD_DIRECTDEBIT = 'computop_directdebit';
    const METHOD_GIROPAY = 'computop_giropay';
}
