<?php
namespace Fatchip\Computop\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CcAcquirer implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '',          'label' => __('Default')],
            ['value' => 'PayPalCC',  'label' => __('PayPalCC')],
        ];
    }
}
