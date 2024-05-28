<?php

namespace Fatchip\Computop\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class IdealService implements ArrayInterface
{
    const SERVICE_DIRECT = 'direct';
    const SERVICE_PPRO = 'ppro';

    /**
     * Return existing address check types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SERVICE_DIRECT,
                'label' => __('iDEAL Direkt'),
            ],
            [
                'value' => self::SERVICE_PPRO,
                'label' => __('via PPRO')
            ],
        ];
    }
}
