<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;

class SaveMensagemAssombradaPlugin
{
    private CartRepositoryInterface $quoteRepository;

    public function __construct(CartRepositoryInterface $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        $shippingAddress = $addressInformation->getShippingAddress();

        if (!$shippingAddress) {
            return [$cartId, $addressInformation];
        }

        $extensionAttributes = $shippingAddress->getExtensionAttributes();

        if (!$extensionAttributes) {
            return [$cartId, $addressInformation];
        }

        $mensagem = $extensionAttributes->getMensagemAssombrada();

        if ($mensagem === null || $mensagem === '') {
            return [$cartId, $addressInformation];
        }

        try {
            $quote = $this->quoteRepository->getActive($cartId);
            $quoteShippingAddress = $quote->getShippingAddress();

            if ($quoteShippingAddress) {
                $quoteShippingAddress->setData('mensagem_assombrada', (string) $mensagem);
                $this->quoteRepository->save($quote);
            }
        } catch (\Exception $e) {
            // Silencioso para nao bloquear checkout
        }

        return [$cartId, $addressInformation];
    }
}
