<?php
namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddSampleCategories implements DataPatchInterface
{
    protected const CATEGORIES = ['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'];

	/**
     * @var CategorySetup
     */
    protected CategorySetup $categorySetup;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * AddSampleCategories constructor.
     * @param CategorySetup $categorySetup
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategorySetup $categorySetup,
        StoreManagerInterface $storeManager,
		CategoryCollectionFactory $categoryCollectionFactory
	) {
		$this->categorySetup = $categorySetup;
        $this->storeManager = $storeManager;
		$this->categoryCollectionFactory = $categoryCollectionFactory;
	}

    /**
     * @return void
     */
    public function apply(): void
	{
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $rootCategory = $this->categoryCollectionFactory->create()->addAttributeToFilter('entity_id', ['eq' => $rootCategoryId])->getFirstItem();
        $rootCategoryPath = $rootCategory->getPath();

        foreach (self::CATEGORIES as $urlKey => $categoryName) {
            $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('url_key', ['eq' => $urlKey]);
            $newCategory = $collection->getFirstItem();

            if ($rootCategoryId && !$newCategory->getId()) {
                $newCategory = $this->categorySetup->createCategory(
                    [
                        'data' => [
                            'parent_id' => $rootCategoryId,
                            'path' => $rootCategoryPath,
                            'name' => $categoryName,
                            'is_active' => true,
                            'include_in_menu' => true,
                            'url_key' => $urlKey
                        ],
                    ]
                );
                $newCategory->save();
            }
        }
	}

	/**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
