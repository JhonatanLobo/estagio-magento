<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\Export;

use ArrayIterator;
use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;

class ConvertToXml
{
    private WriteInterface $directory;
    private Filter $filter;
    private ProductRepositoryInterface $productRepository;
    private ExcelFactory $excelFactory;
    private array $productNameCache = [];

    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        ProductRepositoryInterface $productRepository,
        ExcelFactory $excelFactory
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->productRepository = $productRepository;
        $this->excelFactory = $excelFactory;
    }

    public function getXmlFile(): array
    {
        $component = $this->filter->getComponent();
        $name = md5(microtime());
        $file = 'export/' . $name . '.xml';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $headers = [
            'ID',
            'ID do Produto',
            'Nome do Produto',
            'Autor',
            'Comentário',
            'Nota',
            'Status',
            'Criado em'
        ];

        $dataRows = [$headers];

        $searchResult = $dataProvider->getSearchResult();
        $totalCount = (int) $searchResult->getTotalCount();
        $pageSize = 200;
        $searchResult->setPageSize($pageSize);
        $page = 1;

        while ($totalCount > 0) {
            $searchResult->setCurPage($page);
            $items = $searchResult->getItems();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $productId = (int) $item->getData('product_id');
                $productName = $this->resolveProductName($productId);
                $statusLabel = (bool) $item->getData('aprovado') ? 'Sim' : 'Não';
                $formattedDate = $this->formatDateToBr((string) $item->getData('created_at'));

                $dataRows[] = [
                    (string) $item->getData('avaliacao_id'),
                    (string) $productId,
                    $productName,
                    (string) $item->getData('autor'),
                    (string) $item->getData('comentario'),
                    (string) $item->getData('nota'),
                    $statusLabel,
                    $formattedDate
                ];
            }

            $page++;
            $totalCount -= $pageSize;
            $searchResult->clear();
        }

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator' => new ArrayIterator($dataRows),
            'rowCallback' => [$this, 'getRowData']
        ]);

        $excel->write($stream, 'avaliacoes');

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }

    public function getRowData(array $row): array
    {
        return $row;
    }

    private function resolveProductName(int $productId): string
    {
        if (!isset($this->productNameCache[$productId])) {
            try {
                $product = $this->productRepository->getById($productId);
                $this->productNameCache[$productId] = (string) $product->getName();
            } catch (NoSuchEntityException $e) {
                $this->productNameCache[$productId] = sprintf('Produto #%d (Não Encontrado)', $productId);
            } catch (Exception $e) {
                $this->productNameCache[$productId] = sprintf('Produto #%d', $productId);
            }
        }

        return $this->productNameCache[$productId];
    }

    private function formatDateToBr(string $dateString): string
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('d/m/Y H:i:s');
        } catch (Exception $e) {
            return $dateString;
        }
    }
}