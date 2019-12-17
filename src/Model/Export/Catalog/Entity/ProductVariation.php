<?php

declare(strict_types=1);

namespace Omikron\Factfinder\Model\Export\Catalog\Entity;

use Magento\Catalog\Model\Product;
use Omikron\Factfinder\Api\Export\ExportEntityInterface;
use Omikron\Factfinder\Model\Formatter\NumberFormatter;

class ProductVariation implements ExportEntityInterface
{
    /** @var Product */
    private $product;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var array */
    private $data;

    public function __construct(Product $product, NumberFormatter $numberFormatter, array $data = [])
    {
        $this->product         = $product;
        $this->numberFormatter = $numberFormatter;
        $this->data            = $data;
    }

    public function getId(): int
    {
        return (int) $this->product->getId();
    }

    public function toArray(): array
    {
        return array_merge($this->data, [
            'ProductNumber' => (string) $this->product->getSku(),
            'Price'         => $this->numberFormatter->format((float) $this->product->getFinalPrice()),
            'Availability'  => (int) $this->product->isAvailable(),
            'HasVariants'   => 1,
            'MagentoId'     => $this->getId(),
        ]);
    }
}
