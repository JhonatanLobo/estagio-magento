<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\Jhonatan\Api\Data\AvaliacaoInterface;
use Webjump\Jhonatan\Api\Data\AvaliacaoSearchResultsInterface;

interface AvaliacaoRepositoryInterface
{
    /**
     * @param AvaliacaoInterface $avaliacao
     * @return AvaliacaoInterface
     * @throws CouldNotSaveException
     */
    public function save(AvaliacaoInterface $avaliacao): AvaliacaoInterface;

    /**
     * @param int $avaliacaoId
     * @return AvaliacaoInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $avaliacaoId): AvaliacaoInterface;

    /**
     * @param AvaliacaoInterface $avaliacao
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AvaliacaoInterface $avaliacao): bool;

    /**
     * @param int $avaliacaoId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $avaliacaoId): bool;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return AvaliacaoSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AvaliacaoSearchResultsInterface;
}