<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\ResourceModel\Avaliacao\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao as AvaliacaoResourceModel;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        string $mainTable = 'webjump_avaliacao',
        string $resourceModel = AvaliacaoResourceModel::class,
        ?string $identifierName = 'avaliacao_id',
        ?string $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }
}