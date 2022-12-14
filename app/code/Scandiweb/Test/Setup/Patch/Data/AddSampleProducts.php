<?php
namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

class AddSampleProducts implements DataPatchInterface
{
    protected const PRODUCTS = [
        'cool-shoes' => ['price' => '59.79', 'quantity' => 80, 'name' => 'Cool shoes', 'categories' => ['Women', 'Kids']],
        'cool-shirt' => ['price' => '89.99', 'quantity' => 65, 'name' => 'Cool shirt', 'categories' => ['Men']],
        'cool-jacket' => ['price' => '259.79', 'quantity' => 15, 'name' => 'Cool jacket', 'categories' => ['Men', 'Kids']],
        'cool-sunglasses' => ['price' => '129.79', 'quantity' => 45, 'name' => 'Cool sunglasses', 'categories' => ['Kids']],
        'cool-skirt' => ['price' => '125.49', 'quantity' => 65, 'name' => 'Cool skirt', 'categories' => ['Women']],
        'cool-hat' => ['price' => '49.79', 'quantity' => 70, 'name' => 'Cool hat', 'categories' => ['Women', 'Men', 'Kids']],
        'cool-shorts' => ['price' => '85.19', 'quantity' => 55, 'name' => 'Cool shorts', 'categories' => ['Men', 'Kids']],
        'cool-sneakers' => ['price' => '359.79', 'quantity' => 40, 'name' => 'Cool sneakers', 'categories' => ['Women', 'Men', 'Kids']]
    ];

	/**Scandiweb\Test\Setup\Patch\Data\AddSampleCategories
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

	/**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

	/**
     * @var State
     */
    protected State $appState;

	/**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;

	/**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

	/**
     * @var CategoryLinkManagementInterface
     */
    protected CategoryLinkManagementInterface $categoryLink;

	/**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * AddSampleProducts constructor.
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param EavSetup $eavSetup
     * @param CategoryLinkManagementInterface $categoryLink
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
		ProductInterfaceFactory $productInterfaceFactory,
		ProductRepositoryInterface $productRepository,
		State $appState,
		StoreManagerInterface $storeManager,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
		EavSetup $eavSetup,
		CategoryLinkManagementInterface $categoryLink,
		CategoryCollectionFactory $categoryCollectionFactory
	) {
		$this->appState = $appState;
		$this->productInterfaceFactory = $productInterfaceFactory;
		$this->productRepository = $productRepository;
		$this->eavSetup = $eavSetup;
		$this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
		$this->categoryLink = $categoryLink;
		$this->categoryCollectionFactory = $categoryCollectionFactory;
	}

    /**
     * @return void
     */
	public function apply(): void
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        foreach (self::PRODUCTS as $sku => $data) {
            $product = $this->productInterfaceFactory->create();

            if ($product->getIdBySku($sku)) {
                return;
            }

            $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
            $websiteIDs = [$this->storeManager->getStore()->getWebsiteId()];

            $product->setTypeId(Type::TYPE_SIMPLE)
                ->setWebsiteIds($websiteIDs)
                ->setAttributeSetId($attributeSetId)
                ->setName($data['name'])
                ->setSku($sku)
                ->setUrlKey($sku)
                ->setPrice($data['price'])
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED)
                ->setStockData(['use_config_manage_stock' => 1, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
            $product = $this->productRepository->save($product);

            // As I investigated all the methods below except create() return void, hence giving an error when chained together
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSourceCode('default');
            $sourceItem->setQuantity($data['quantity']);
            $sourceItem->setSku($product->getSku());
            $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);

            $this->sourceItemsSaveInterface->execute([$sourceItem]);


            $categoryIds = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('name', ['in' => $data['categories']])
                ->getAllIds();

            $this->categoryLink->assignProductToCategories($product->getSku(), $categoryIds);
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
