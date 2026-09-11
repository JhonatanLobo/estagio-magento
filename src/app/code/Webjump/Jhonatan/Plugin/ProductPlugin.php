<?php
declare(strict_types=1);

namespace Webjump\Jhonatan\Plugin;

use Magento\Catalog\Model\Product;

class ProductPlugin
{
    private const SUFFIX = ' [Webjump Homologado]';

    /**
     * @param Product $subject
     * @param string|null $result
     * @return string|null
     */
    public function afterGetName(Product $subject, ?string $result): ?string
    {
        if ($result === null || $result === '') {
            return $result;
        }

        if (str_contains($result, self::SUFFIX)) {
            return $result;
        }

        return $result . self::SUFFIX;
    }
}