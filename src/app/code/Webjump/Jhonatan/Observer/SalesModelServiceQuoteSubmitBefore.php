<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if (!$quote || !$order) {
            return;
        }

        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress) {
            return;
        }

        $mensagem = $shippingAddress->getData('mensagem_assombrada');

        if ($mensagem !== null && $mensagem !== '') {
            $order->setData('mensagem_assombrada', (string) $mensagem);
        }
    }
}
