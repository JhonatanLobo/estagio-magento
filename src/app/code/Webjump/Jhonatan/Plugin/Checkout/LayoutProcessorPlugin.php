<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Plugin\Checkout;

class LayoutProcessorPlugin
{
    public function afterProcess($subject, array $resultado): array
    {
        $caminho = &$resultado['components']['checkout']['children']
            ['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']
            ['children'];

        $caminho['mensagem_assombrada'] = [
            'component'  => 'Magento_Ui/js/form/element/textarea',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/textarea',
                'tooltip'     => [
                    'description' => __('Escreva uma mensagem que ira junto com o seu pedido.')
                ],
            ],
            'dataScope'  => 'shippingAddress.extension_attributes.mensagem_assombrada',
            'label'      => __('Mensagem assombrada no pacote'),
            'provider'   => 'checkoutProvider',
            'sortOrder'  => 250,
            'validation' => ['max_text_length' => 200],
        ];

        return $resultado;
    }
}
