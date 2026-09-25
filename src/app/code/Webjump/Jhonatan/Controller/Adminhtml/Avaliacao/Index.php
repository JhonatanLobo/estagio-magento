<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Avaliacao;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';

    private PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webjump_Jhonatan::avaliacao');
        $resultPage->getConfig()->getTitle()->prepend(__('Avaliações de Produtos'));

        return $resultPage;
    }
}