<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Avaliacao;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';

    private PageFactory $resultPageFactory;
    private AvaliacaoRepositoryInterface $avaliacaoRepository;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AvaliacaoRepositoryInterface $avaliacaoRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->avaliacaoRepository = $avaliacaoRepository;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('avaliacao_id');

        if ($id) {
            try {
                $this->avaliacaoRepository->getById((int) $id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('Esta avaliação não existe mais no sistema.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/index');
            }
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webjump_Jhonatan::avaliacao');
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __('Editar Avaliação #%1', $id) : __('Nova Avaliação')
        );

        return $resultPage;
    }
}