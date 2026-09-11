<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Model\Product|null $product */
        $product = $observer->getEvent()->getProduct();

        if ($product) {
            $productId = (int) $product->getId();
            $productSku = (string) $product->getSku();
            $productName = (string) $product->getName();

            $logMessage = sprintf(
                '[Webjump_Jhonatan] Observer catalog_product_save_after disparado! Produto ID: %d | SKU: %s | Nome: "%s"',
                $productId,
                $productSku,
                $productName
            );

            $this->logger->info($logMessage);
        }
    }
}