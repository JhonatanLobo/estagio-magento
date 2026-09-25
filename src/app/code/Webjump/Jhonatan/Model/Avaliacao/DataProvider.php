<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\Avaliacao;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao\Collection;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    private DataPersistorInterface $dataPersistor;
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $avaliacao) {
            $this->loadedData[$avaliacao->getId()] = $avaliacao->getData();
        }

        $data = $this->dataPersistor->get('webjump_avaliacao');
        if (!empty($data)) {
            $avaliacao = $this->collection->getNewEmptyItem();
            $avaliacao->setData($data);
            $this->loadedData[$avaliacao->getId()] = $avaliacao->getData();
            $this->dataPersistor->clear('webjump_avaliacao');
        }

        return $this->loadedData;
    }
}