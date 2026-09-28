<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Block\Adminhtml\Avaliacao\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];
        $avaliacaoId = $this->getAvaliacaoId();

        if ($avaliacaoId) {
            $data = [
                'label' => __('Excluir Avaliação'),
                'class' => 'delete',
                'on_click' => sprintf(
                    'deleteConfirm("%s", "%s")',
                    __('Tem certeza de que deseja excluir esta avaliação?'),
                    $this->getUrl('*/*/delete', ['avaliacao_id' => $avaliacaoId])
                ),
                'sort_order' => 20
            ];
        }

        return $data;
    }
}