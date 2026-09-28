<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Avaliacao;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';

    private AvaliacaoRepositoryInterface $avaliacaoRepository;

    public function __construct(
        Context $context,
        AvaliacaoRepositoryInterface $avaliacaoRepository
    ) {
        parent::__construct($context);
        $this->avaliacaoRepository = $avaliacaoRepository;
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('avaliacao_id');

        if ($id) {
            try {
                $this->avaliacaoRepository->deleteById((int) $id);
                $this->messageManager->addSuccessMessage(__('A avaliação foi excluída com sucesso.'));
                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Ocorreu um erro ao tentar excluir a avaliação.')
                );
            }

            return $resultRedirect->setPath('*/*/edit', ['avaliacao_id' => $id]);
        }

        $this->messageManager->addErrorMessage(__('Identificador de avaliação não encontrado.'));
        return $resultRedirect->setPath('*/*/index');
    }
}