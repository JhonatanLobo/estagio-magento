<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Avaliacao;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao\CollectionFactory;

class MassApprove extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private AvaliacaoRepositoryInterface $avaliacaoRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        AvaliacaoRepositoryInterface $avaliacaoRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->avaliacaoRepository = $avaliacaoRepository;
    }

    public function execute(): ResultInterface
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $itemsApproved = 0;

            foreach ($collection as $avaliacao) {
                $avaliacao->setAprovado(true);
                $this->avaliacaoRepository->save($avaliacao);
                $itemsApproved++;
            }

            $this->messageManager->addSuccessMessage(
                __('Total de %1 avaliação(ões) aprovada(s) com sucesso.', $itemsApproved)
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Ocorreu um erro ao aprovar as avaliações selecionadas.')
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index');
    }
}