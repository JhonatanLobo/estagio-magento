<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface AvaliacaoSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Webjump\Jhonatan\Api\Data\AvaliacaoInterface[]
     */
    public function getItems();

    /**
     * @param \Webjump\Jhonatan\Api\Data\AvaliacaoInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}