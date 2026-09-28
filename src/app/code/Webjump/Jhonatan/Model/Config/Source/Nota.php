<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Nota implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('1 Estrela')],
            ['value' => '2', 'label' => __('2 Estrelas')],
            ['value' => '3', 'label' => __('3 Estrelas')],
            ['value' => '4', 'label' => __('4 Estrelas')],
            ['value' => '5', 'label' => __('5 Estrelas')]
        ];
    }
}