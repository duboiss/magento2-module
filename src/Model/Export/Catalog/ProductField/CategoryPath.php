<?php

declare(strict_types=1);

namespace Omikron\Factfinder\Model\Export\Catalog\ProductField;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Omikron\Factfinder\Api\Export\FieldInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\Product;

class CategoryPath implements FieldInterface
{
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    /** @var string */
    private $fieldName;

    public function __construct(CategoryRepositoryInterface $categoryRepository, string $fieldName = 'CategoryPath')
    {
        $this->categoryRepository = $categoryRepository;
        $this->fieldName          = $fieldName;
    }

    public function getName(): string
    {
        return $this->fieldName;
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    public function getValue(AbstractModel $product): string
    {
        $paths = array_map(function (int $categoryId) use ($product): array {
            return $this->getPath($categoryId, $product->getStore());
        }, $product->getCategoryIds());

        return implode('|', array_map(function (array $path): string {
            return implode('/', array_map('urlencode', $path));
        }, array_filter($paths)));
    }

    /**
     * @param int   $categoryId
     * @param Store $store
     *
     * @return string[]
     */
    private function getPath(int $categoryId, Store $store): array
    {
        try {
            $storeId = (int) $store->getId();
            return array_map(function (int $id) use ($storeId): string {
                return trim($this->getCategory($id, $storeId)->getName());
            }, $this->getPathIds($this->getCategory($categoryId, $storeId), $store));
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    private function getPathIds(CategoryInterface $category, Store $store): array
    {
        $path = explode('/', (string) $category->getPath());
        $root = (int) ($path[1] ?? -1);
        return $category->getIsActive() && $store->getRootCategoryId() == $root ? array_slice($path, 2) : [];
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     *
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    private function getCategory(int $categoryId, int $storeId): CategoryInterface
    {
        return $this->categoryRepository->get($categoryId, $storeId);
    }
}
