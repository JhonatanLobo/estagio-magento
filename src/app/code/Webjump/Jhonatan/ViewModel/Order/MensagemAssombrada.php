<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\ViewModel\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;

class MensagemAssombrada implements ArgumentInterface
{
    private Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof Order ? $order : null;
    }

    public function getMensagem(): ?string
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }

        $mensagem = $order->getData('mensagem_assombrada');

        return ($mensagem !== null && $mensagem !== '') ? (string) $mensagem : null;
    }

    public function hasMensagem(): bool
    {
        return $this->getMensagem() !== null;
    }
}
