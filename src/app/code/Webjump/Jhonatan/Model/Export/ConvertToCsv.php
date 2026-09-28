<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\Export;

use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;

class ConvertToCsv
{
    private WriteInterface $directory;
    private Filter $filter;
    private ProductRepositoryInterface $productRepository;
    private array $productNameCache = [];

    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        ProductRepositoryInterface $productRepository
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->productRepository = $productRepository;
    }

    public function getCsvFile(): array
    {
        $component = $this->filter->getComponent();
        $name = md5(microtime());
        $file = 'export/' . $name . '.csv';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        // Adiciona BOM UTF-8 para garantir correta acentuação no Excel/Calc
        $stream->write("\xEF\xBB\xBF");

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
        $stream->writeCsv($headers);

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

                $row = [
                    (string) $item->getData('avaliacao_id'),
                    (string) $productId,
                    $productName,
                    (string) $item->getData('autor'),
                    (string) $item->getData('comentario'),
                    (string) $item->getData('nota'),
                    $statusLabel,
                    $formattedDate
                ];

                $stream->writeCsv($row);
            }

            $page++;
            $totalCount -= $pageSize;
            $searchResult->clear();
        }

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
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