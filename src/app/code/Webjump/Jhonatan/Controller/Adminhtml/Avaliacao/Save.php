<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Avaliacao;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;
use Webjump\Jhonatan\Model\AvaliacaoFactory;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao';

    private DataPersistorInterface $dataPersistor;
    private AvaliacaoRepositoryInterface $avaliacaoRepository;
    private AvaliacaoFactory $avaliacaoFactory;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        AvaliacaoRepositoryInterface $avaliacaoRepository,
        AvaliacaoFactory $avaliacaoFactory,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->avaliacaoRepository = $avaliacaoRepository;
        $this->avaliacaoFactory = $avaliacaoFactory;
        $this->productRepository = $productRepository;
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id = !empty($data['avaliacao_id']) ? (int) $data['avaliacao_id'] : null;

            try {
                $productId = (int) ($data['product_id'] ?? 0);

                // Validação defensiva: checa se o produto existe no catálogo
                try {
                    $this->productRepository->getById($productId);
                } catch (NoSuchEntityException $e) {
                    throw new LocalizedException(
                        __('O produto com ID "%1" não foi encontrado no catálogo.', $productId)
                    );
                }

                if ($id) {
                    $avaliacao = $this->avaliacaoRepository->getById($id);
                } else {
                    $avaliacao = $this->avaliacaoFactory->create();
                }

                $avaliacao->setProductId($productId);
                $avaliacao->setAutor((string) $data['autor']);
                $avaliacao->setComentario((string) ($data['comentario'] ?? ''));
                $avaliacao->setNota((int) $data['nota']);
                $avaliacao->setAprovado((bool) $data['aprovado']);

                $this->avaliacaoRepository->save($avaliacao);

                $this->messageManager->addSuccessMessage(__('Avaliação gravada com sucesso.'));
                $this->dataPersistor->clear('webjump_avaliacao');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['avaliacao_id' => $avaliacao->getAvaliacaoId()]);
                }

                return $resultRedirect->setPath('*/*/index');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Ocorreu um erro ao gravar os dados da avaliação.')
                );
            }

            // Preserva os dados preenchidos para não perder a digitação
            $this->dataPersistor->set('webjump_avaliacao', $data);
            return $resultRedirect->setPath('*/*/edit', ['avaliacao_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}