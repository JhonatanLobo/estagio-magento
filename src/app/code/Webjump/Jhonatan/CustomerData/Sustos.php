<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Sustos implements SectionSourceInterface
{
    private CustomerSession $customerSession;

    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    public function getSectionData(): array
    {
        $total = (int) $this->customerSession->getData('sustos_count');

        return [
            'total' => $total,
            'mensagem' => $total === 1
                ? (string) __('Você já foi assustado 1 vez nesta sessão!')
                : (string) __('Você já foi assustado %1 vezes nesta sessão!', $total)
        ];
    }
}
