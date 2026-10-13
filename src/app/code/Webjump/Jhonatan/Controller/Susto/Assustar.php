<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Susto;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Assustar implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    private JsonFactory $jsonFactory;
    private CustomerSession $customerSession;

    public function __construct(
        JsonFactory $jsonFactory,
        CustomerSession $customerSession
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        $atual = (int) $this->customerSession->getData('sustos_count');
        $novoTotal = $atual + 1;
        $this->customerSession->setData('sustos_count', $novoTotal);

        return $result->setData([
            'success' => true,
            'total' => $novoTotal
        ]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
