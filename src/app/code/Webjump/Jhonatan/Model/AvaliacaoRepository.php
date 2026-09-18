<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\Jhonatan\Api\AvaliacaoRepositoryInterface;
use Webjump\Jhonatan\Api\Data\AvaliacaoInterface;
use Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface;
use Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterfaceFactory;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao as AvaliacaoResourceModel;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao\CollectionFactory as AvaliacaoCollectionFactory;

class AvaliacaoRepository implements AvaliacaoRepositoryInterface
{
    private AvaliacaoResourceModel $resourceModel;
    private AvaliacaoFactory $avaliacaoFactory;
    private AvaliacaoCollectionFactory $collectionFactory;
    private AvaliacaoSearchResultsInterfaceFactory $searchResultsFactory;
    private CollectionProcessorInterface $collectionProcessor;

    public function __construct(
        AvaliacaoResourceModel $resourceModel,
        AvaliacaoFactory $avaliacaoFactory,
        AvaliacaoCollectionFactory $collectionFactory,
        AvaliacaoSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resourceModel = $resourceModel;
        $this->avaliacaoFactory = $avaliacaoFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function save(AvaliacaoInterface $avaliacao): AvaliacaoInterface
    {
        try {
            /** @var \Magento\Framework\Model\AbstractModel $avaliacao */
            $this->resourceModel->save($avaliacao);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __('Nao foi possivel salvar a avaliacao: %1', $exception->getMessage()),
                $exception
            );
        }
        return $avaliacao;
    }

    public function getById(int $avaliacaoId): AvaliacaoInterface
    {
        $avaliacao = $this->avaliacaoFactory->create();
        $this->resourceModel->load($avaliacao, $avaliacaoId);
        if (!$avaliacao->getId()) {
            throw new NoSuchEntityException(__('Avaliacao com o ID "%1" nao foi encontrada.', $avaliacaoId));
        }
        return $avaliacao;
    }

    public function delete(AvaliacaoInterface $avaliacao): bool
    {
        try {
            /** @var \Magento\Framework\Model\AbstractModel $avaliacao */
            $this->resourceModel->delete($avaliacao);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __('Nao foi possivel excluir a avaliacao: %1', $exception->getMessage()),
                $exception
            );
        }
        return true;
    }

    public function deleteById(int $avaliacaoId): bool
    {
        return $this->delete($this->getById($avaliacaoId));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): AvaliacaoSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var AvaliacaoSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}