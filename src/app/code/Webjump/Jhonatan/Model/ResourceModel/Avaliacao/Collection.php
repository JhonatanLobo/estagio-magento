<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\ResourceModel\Avaliacao;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Webjump\Jhonatan\Model\Avaliacao as AvaliacaoModel;
use Webjump\Jhonatan\Model\ResourceModel\Avaliacao as AvaliacaoResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'avaliacao_id';

    protected function _construct(): void
    {
        $this->_init(AvaliacaoModel::class, AvaliacaoResourceModel::class);
    }
}