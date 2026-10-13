<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Observer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CartAddSustoObserver implements ObserverInterface
{
    private CustomerSession $customerSession;

    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    public function execute(Observer $observer): void
    {
        $atual = (int) $this->customerSession->getData('sustos_count');
        $this->customerSession->setData('sustos_count', $atual + 1);
    }
}
