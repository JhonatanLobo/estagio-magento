<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Avaliacao extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('webjump_avaliacao', 'avaliacao_id');
    }
}