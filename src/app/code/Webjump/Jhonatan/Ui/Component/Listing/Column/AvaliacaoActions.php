<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class AvaliacaoActions extends Column
{
    private UrlInterface $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['avaliacao_id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                'webjump_avaliacao/avaliacao/edit',
                                ['avaliacao_id' => $item['avaliacao_id']]
                            ),
                            'label' => __('Editar')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'webjump_avaliacao/avaliacao/delete',
                                ['avaliacao_id' => $item['avaliacao_id']]
                            ),
                            'label' => __('Excluir'),
                            'confirm' => [
                                'title' => __('Excluir Avaliação #%1', $item['avaliacao_id']),
                                'message' => __('Tem certeza de que deseja excluir esta avaliação?')
                            ],
                            'post' => true
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}