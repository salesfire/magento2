<?php
namespace Salesfire\Salesfire\Model\Source;

class Yesnoinherit implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 2, 'label' => __('Use Store Setting')],
            ['value' => 1, 'label' => __('Yes')],
            ['value' => 0, 'label' => __('No')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            2 => __('Use Store Setting'),
            1 => __('Yes'),
            0 => __('No')
        ];
    }
}
