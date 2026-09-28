<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Webjump\Jhonatan\Model\Export\ConvertToXml;

class GridToXml extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Jhonatan::avaliacao_export';

    private ConvertToXml $converter;
    private FileFactory $fileFactory;

    public function __construct(
        Context $context,
        ConvertToXml $converter,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;
    }

    public function execute(): ResponseInterface
    {
        return $this->fileFactory->create(
            'avaliacoes.xml',
            $this->converter->getXmlFile(),
            'var'
        );
    }
}