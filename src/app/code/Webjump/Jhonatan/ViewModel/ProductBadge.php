<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ProductBadge implements ArgumentInterface
{
    private Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');
        return $product instanceof Product ? $product : null;
    }

    public function isSustentavel(): bool
    {
        $product = $this->getProduct();
        if (!$product) {
            return false;
        }

        return (bool) $product->getData('selo_sustentavel');
    }

    public function getBadgeLabel(): string
    {
        return 'Produto Sustentável';
    }

    public function getBadgeTooltip(): string
    {
        return 'Item produzido sob diretrizes ecológicas homologadas e baixo impacto ambiental.';
    }

    public function getIconSvgPath(): string
    {
        return 'M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12';
    }
}