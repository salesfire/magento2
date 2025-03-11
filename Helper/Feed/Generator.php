<?php

namespace Salesfire\Salesfire\Helper\Feed;

/**
 * Salesfire Feed
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.5.5
 */
class Generator
{
    private $_helperData;
    private $_moduleManager;
    private $_objectManager;
    private $_storeManager;
    private $_productCollectionFactory;
    private $_categoryCollectionFactory;
    private $_filesystem;
    private $_file;
    private $_escaper;
    private $_taxHelper;
    private $_catalogData;
    private $_stockItem;

    private $_logger;

    private $mediaPath;

    private $urlFinder;

    protected $configurable;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    private $frontendUrl;

    public function __construct(
        \Salesfire\Salesfire\Helper\Data $helperData,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItem,
        \Salesfire\Salesfire\Helper\Logger\Logger $logger,
        \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Url $frontendUrl
    ) {
        $this->_helperData                = $helperData;
        $this->_moduleManager             = $moduleManager;
        $this->_objectManager             = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_storeManager              = $storeManager;
        $this->_productCollectionFactory  = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_filesystem                = $filesystem;
        $this->_file                      = $file;
        $this->_escaper                   = $escaper;
        $this->_taxHelper                 = $taxHelper;
        $this->_catalogData               = $catalogData;
        $this->_stockItem                 = $stockItem;
        $this->urlFinder                  = $urlFinder;
        $this->configurable               = $configurable;
        $this->frontendUrl                = $frontendUrl;

        $this->_logger = $logger;

        $this->mediaPath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . 'catalog';

        if (! $this->_file->isExists($this->mediaPath)) {
            $this->_logger->info('Creating media catalog directory for feeds: ' . $this->mediaPath);
            $this->_file->createDirectory($this->mediaPath, 777);

            if (! $this->_file->isExists($this->mediaPath)) {
                $this->_logger->error('Unable to create media catalog directory: ' . $this->mediaPath);
            }
        }

        $this->scopeConfig = $context->getScopeConfig();
    }

    public function getMediaPath()
    {
        return $this->mediaPath . '/';
    }

    public function printLine($siteId, $text, $tab = 0)
    {
        $regex = '/[^\x09\x0A\x0D\x20-\xD7FF\xE000-\xFFFD\x10000-x10FFFF]/';

        $this->_file->filePutContents($this->getMediaPath() . $siteId . '.temp.xml', str_repeat("\t", $tab) . preg_replace($regex, '', $text) . "\n", FILE_APPEND);
    }

    public function printLines($siteId, $text)
    {
        foreach ($text as $line) {
            $this->printLine($siteId, $line[0], isset($line[1]) ? $line[1] : 0);
        }
    }

    public function escapeString($text)
    {
        return html_entity_decode(trim($text));
    }

    public function execute()
    {
        $processed_site_ids = [];

        if ($this->_logger->truncate($this->_helperData->maxLogSize())) {
            $this->_logger->info('Truncated.');
        }

        foreach ($this->_helperData->getStoreViews() as $storeView) {
            $storeId = $storeView->id;
            $store = $this->_storeManager->getStore($storeId);

            $this->_logger->info('======================================');
            $this->_logger->info('Salesfire Feed - Store #' . ($storeId ?: 'Default'));
            $this->_logger->info('======================================');

            $this->_storeManager->setCurrentStore($storeId);

            if (! $this->_helperData->isAvailable($storeId)) {
                $this->_logger->info('Skipping as Salesfire disabled');
                continue;
            }

            if (! $this->_helperData->isFeedEnabled($storeId)) {
                $this->_logger->info('Skipping as Salesfire feed disabled');
                continue;
            }

            $siteId = $this->_helperData->getSiteId($storeId);

            if (in_array($siteId, $processed_site_ids)) {
                $this->_logger->info('Skipping as this Site ID has already been generated. You cannot have multiple feeds with the same Site ID.');
                continue;
            }

            $brand_code = $this->_helperData->getBrandCode($storeId);
            $gender_code = $this->_helperData->getGenderCode($storeId);
            $age_group_code = $this->_helperData->getAgeGroupCode($storeId);
            $colour_code = $this->_helperData->getColourCode($storeId);
            $attribute_codes = $this->_helperData->getAttributeCodes($storeId);
            $default_brand = $this->_helperData->getDefaultBrand($storeId);
            $currency = $store->getCurrentCurrencyCode();

            $this->_logger->info('Feed Settings');
            $this->_logger->info('- Site ID:          ' . $siteId);
            $this->_logger->info('- Brand Code:       ' . $brand_code);
            $this->_logger->info('- Gender Code:      ' . $gender_code);
            $this->_logger->info('- Age Group Code:   ' . $age_group_code);
            $this->_logger->info('- Colour Code:      ' . $colour_code);
            $this->_logger->info('- Default Brand:    ' . $default_brand);
            $this->_logger->info('- Additional Codes: ' . implode(', ', $attribute_codes));
            $this->_logger->info('- Store Currency:   ' . $currency);


            $this->_logger->info('');
            $this->_logger->info('Progress');

            if ($this->_file->isExists($this->getMediaPath() . $siteId . '.temp.xml')) {
                $this->_logger->info('- Removing existing temp file');
                $this->_file->deleteFile($this->getMediaPath() . $siteId . '.temp.xml');
            }

            $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            $this->printLine($siteId, '<?xml version="1.0" encoding="utf-8" ?>', 0);
            $this->printLine($siteId, '<productfeed site="'.$baseUrl.'" generator="salesfire/magento2" date-generated="'.gmdate('c').'" version="' . $this->_helperData->getVersion() . '">', 0);

            $mediaUrl = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            $categories = $this->getCategories($storeId);

            if (! empty($categories)) {
                $this->_logger->info('- Exporting catagories (' . count($categories). ')');

                $this->printLine($siteId, '<categories>', 1);
                foreach ($categories as $category) {
                    $parent = $category->getParentCategory()->setStoreId($storeId);
                    if ($category->getLevel() <= 1) {
                        continue;
                    }

                    try {
                        $text = [];

                        $text[] = ['<category id="category_' . $category->getId() . '"' . ($parent && $parent->getLevel() > 1 ? ' parent="category_'.$parent->getId(). '"' : '') . '>', 2];

                        $text[] = ['<id>' . $this->escapeString($category->getId()) . '</id>', 3];

                        $text[] = ['<name><![CDATA['.$this->escapeString($category->getName()).']]></name>', 3];

                        $text[] = ['<breadcrumb><![CDATA['.$this->escapeString($this->getCategoryBreadcrumb($storeId, $category)).']]></breadcrumb>', 3];

                        $description = $category->getDescription();
                        if (! empty($description)) {
                            $text[] = ['<description><![CDATA['.$this->escapeString(mb_substr($this->_escaper->escapeHtml(strip_tags($description)), 0, 2000)).']]></description>', 3];
                        }

                        $text[] = ['<link><![CDATA[' . $baseUrl . $category->getUrlPath() . ']]></link>', 3];

                        $keywords = $category->getMetaKeywords();
                        if (! empty($keywords)) {
                            $text[] = ['<keywords>', 3];
                            foreach (explode(',', $keywords) as $keyword) {
                                $text[] = ['<keyword><![CDATA['.$this->escapeString($keyword).']]></keyword>', 4];
                            }
                            $text[] = ['</keywords>', 3];
                        }

                        $text[] = ['</category>', 2];

                        $this->printLines($siteId, $text);
                    } catch (\Exception $e) {
                        $this->_logger->error('Error with category: ' . $category->getId());
                        $this->_logger->error('- File: ' . $e->getFile() . ' - ' . $e->getLine());
                        $this->_logger->error('- Message: ' . $e->getMessage());
                    }
                }
                $this->printLine($siteId, '</categories>', 1);

                $this->_logger->info('- Category export completed');
            }
            $categories = null;

            $this->_logger->info('- Exporting products');
            $this->printLine($siteId, '<products>', 1);

            try {
                $products = $this->getVisibleProducts($storeId, 100);

                $total_pages = $products->getLastPageNumber();

                for ($current_page = 1; $current_page <= $total_pages; $current_page++) {
                    $products->setCurPage($current_page);

                    foreach ($products as $product) {
                        try {
                            $text = [];

                            $text[] = ['<product id="product_'.$product->getId().'" type="' . htmlspecialchars($product->getTypeId()) . '">', 2];

                            $text[] = ['<id>' . $product->getId() . '</id>', 3];

                            $text[] = ['<title><![CDATA[' . $this->escapeString($product->getName()) . ']]></title>', 3];

                            $text[] = ['<description><![CDATA[' . $this->escapeString(mb_substr($this->_escaper->escapeHtml(strip_tags($product->getDescription() ?: '')), 0, 5000)) . ']]></description>', 3];

                            $price = $this->getProductPrice($product);
                            $saleprice = $this->getProductSalePrice($product);

                            $text[] = ['<price currency="' . $currency . '">' . $price . '</price>', 3];

                            $text[] = ['<sale_price currency="' . $currency . '">' . ($saleprice ? $saleprice : $price) . '</sale_price>', 3];

                            $text[] = ['<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 3];

                            $text[] = ['<link><![CDATA[' . $this->getProductUrl($product, $storeId, false) . ']]></link>', 3];

                            $image = $this->getProductImage($siteId, $mediaUrl, $product, $product);
                            if (! empty($image)) {
                                $text[] = ['<image>' . $image  . '</image>', 3];
                            }

                            if (! empty($gender_code)) {
                                $gender = $this->getAttributeValue($storeId, $product, $gender_code);
                                if ($gender) {
                                    $text[] = ['<gender><![CDATA['.$this->escapeString($gender).']]></gender>', 3];
                                }
                            }

                            if (! empty($age_group_code)) {
                                $age_group = $this->getAttributeValue($storeId, $product, $age_group_code);
                                if ($age_group) {
                                    $text[] = ['<age_group><![CDATA['.$this->escapeString($age_group).']]></age_group>', 3];
                                }
                            }

                            if (! empty($brand_code)) {
                                $brand = $this->getAttributeValue($storeId, $product, $brand_code);
                                if ($brand) {
                                    $text[] = ['<brand><![CDATA[' . $this->escapeString($brand) . ']]></brand>', 3];
                                } else {
                                    $text[] = ['<brand><![CDATA[' . $this->escapeString($default_brand) . ']]></brand>', 3];
                                }
                            } elseif (! empty($default_brand)) {
                                $text[] = ['<brand><![CDATA[' . $this->escapeString($default_brand) . ']]></brand>', 3];
                            }

                            $categories = $product->getCategoryIds();
                            if (! empty($categories)) {
                                $text[] = ['<categories>', 3];
                                foreach ($categories as $categoryId) {
                                    $text[] = ['<category id="category_'.$categoryId.'" />', 4];
                                }
                                $text[] = ['</categories>', 3];
                            }

                            $keywords = $product->getMetaKeywords();
                            if (! empty($keywords)) {
                                $text[] = ['<keywords>', 3];
                                foreach (explode(',', $keywords) as $keyword) {
                                    $text[] = ['<keyword><![CDATA['.$this->escapeString($keyword).']]></keyword>', 4];
                                }
                                $text[] = ['</keywords>', 3];
                            }

                            $text[] = ['<variants>', 3];

                            if ($product->getTypeId() === 'configurable') {
                                $product_attributes = [];
                                $product_options = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                                foreach ($product_options as $option) {
                                    $product_attributes[] = $option['attribute_code'];
                                }

                                $childProducts = $product->getTypeInstance()->getUsedProducts($product);

                                if (count($childProducts) > 0) {
                                    foreach ($childProducts as $childProduct) {
                                        if ($childProduct->getStatus() != 1) {
                                            continue;
                                        }

                                        $text[] = ['<variant>', 4];

                                        $text[] = ['<id>' . $childProduct->getId() . '</id>', 5];

                                        $attributes_to_show = array_merge($product_attributes, $attribute_codes);

                                        if (! empty($attributes_to_show)) {
                                            $attributes = [];

                                            foreach ($attributes_to_show as $attribute) {
                                                if (empty($attribute) || in_array($attribute, ['id', 'mpn', 'stock', 'link', 'image', $age_group_code, $gender_code, $brand_code, $colour_code])) {
                                                    continue;
                                                }

                                                $attribute_text = $this->getAttributeValue($storeId, $childProduct, $attribute);
                                                if ($attribute_text) {
                                                    $attributes[$attribute] = $attribute_text;
                                                }
                                            }

                                            if (! empty($attributes)) {
                                                $text[] = ['<attributes>', 5];

                                                foreach ($attributes as $attribute => $attribute_text) {
                                                    $text[] = ['<'.$attribute.'><![CDATA['.$this->escapeString($attribute_text).']]></'.$attribute.'>', 6];
                                                }

                                                $text[] = ['</attributes>', 5];
                                            }
                                        }

                                        if (! empty($colour_code)) {
                                            $colour = $this->getAttributeValue($storeId, $childProduct, $colour_code);

                                            if (! $colour) {
                                                $colour = $this->getAttributeValue($storeId, $product, $colour_code);
                                            }

                                            if ($colour) {
                                                $text[] = ['<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5];
                                            }
                                        }

                                        $text[] = ['<mpn><![CDATA['.$this->escapeString($childProduct->getSku()).']]></mpn>', 5];

                                        $text[] = ['<stock>' . $this->getStockQty($product, $childProduct) .'</stock>', 5];

                                        $text[] = ['<link><![CDATA[' . $this->getProductUrl($product, $storeId) . ']]></link>', 5];

                                        $image = $this->getProductImage($siteId, $mediaUrl, $product, $childProduct);
                                        if (! empty($image)) {
                                            $text[] = ['<image>' . $image  . '</image>', 5];
                                        }

                                        $text[] = ['</variant>', 4];
                                    }
                                }
                            } else {
                                $text[] = ['<variant>', 4];

                                $text[] = ['<id>' . $product->getId() . '</id>', 5];

                                if (! empty($attribute_codes)) {
                                    $attributes = [];

                                    foreach ($attribute_codes as $attribute) {
                                        if (empty($attribute) || in_array($attribute, ['id', 'mpn', 'stock', 'link', 'image', $age_group_code, $gender_code, $brand_code, $colour_code])) {
                                            continue;
                                        }

                                        $attribute_text = $this->getAttributeValue($storeId, $product, $attribute);
                                        if ($attribute_text) {
                                            $attributes[$attribute] = $attribute_text;
                                        }
                                    }

                                    if (! empty($attributes)) {
                                        $text[] = ['<attributes>', 5];

                                        foreach ($attributes as $attribute => $attribute_text) {
                                            $text[] = ['<'.$attribute.'><![CDATA['.$this->escapeString($attribute_text).']]></'.$attribute.'>', 6];
                                        }

                                        $text[] = ['</attributes>', 5];
                                    }
                                }

                                if (! empty($colour_code)) {
                                    $colour = $this->getAttributeValue($storeId, $product, $colour_code);
                                    if ($colour) {
                                        $text[] = ['<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5];
                                    }
                                }

                                $text[] = ['<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 5];

                                $text[] = ['<stock>' . $this->getStockQty($product) .'</stock>', 5];

                                $text[] = ['<link><![CDATA[' . $this->getProductUrl($product, $storeId, false) . ']]></link>', 5];

                                $image = $this->getProductImage($siteId, $mediaUrl, $product, $product);
                                if (! empty($image)) {
                                    $text[] = ['<image>' . $image  . '</image>', 5];
                                }

                                $text[] = ['</variant>', 4];
                            }

                            $text[] = ['</variants>', 3];

                            $text[] = ['</product>', 2];

                            $this->printLines($siteId, $text);
                        } catch (\Exception $e) {
                            $this->_logger->error('Error with product: ' . $product->getId());
                            $this->_logger->error('- File: ' . $e->getFile() . ' - ' . $e->getLine());
                            $this->_logger->error('- Message: ' . $e->getMessage());
                        }
                    }

                    $products->clear();
                }
            } catch (\Exception $e) {
                $this->_logger->error('- File: ' . $e->getFile() . ' - ' . $e->getLine());
                $this->_logger->error('- Message: ' . $e->getMessage());
            }

            $this->_logger->info('- Product export completed');
            $this->printLine($siteId, '</products>', 1);

            $this->printLine($siteId, '</productfeed>', 0);

            if ($this->_file->isExists($this->getMediaPath() . $siteId . '.xml')) {
                $this->_logger->info('- Removing existing export');
                $this->_file->deleteFile($this->getMediaPath() . $siteId . '.xml');
            }

            if ($this->_file->isExists($this->getMediaPath() . $siteId . '.temp.xml')) {
                $this->_logger->info('- Moving temp file in place');
                $this->_file->rename($this->getMediaPath() . $siteId . '.temp.xml', $this->getMediaPath() . $siteId . '.xml');
            }

            if ($this->_file->isExists($this->getMediaPath() . $siteId . '.temp.xml')) {
                $this->_logger->info('- Removing temp export as rename may have failed');
                $this->_file->deleteFile($this->getMediaPath() . $siteId . '.temp.xml');
            }

            $this->_logger->info('');
            $this->_logger->info('Export completed');

            $processed_site_ids[] = $siteId;
        }
    }

    public function getCategories($storeId)
    {
        $rootCategoryId = $this->_storeManager->getStore($storeId)->getRootCategoryId();
        $categories = $this->_categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addFieldToFilter('is_active', 1)
            ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"])
            ->addAttributeToSelect('*');

        return $categories;
    }

    public function getCategoryBreadcrumb($storeId, $category, $breadcrumb = '')
    {
        if (! empty($breadcrumb)) {
            $breadcrumb = ' > ' . $breadcrumb;
        }

        $breadcrumb = $category->getName() . $breadcrumb;

        $parent = $category->getParentCategory()->setStoreId($storeId);
        if ($parent && $parent->getLevel() > 1) {
            return $this->getCategoryBreadcrumb($storeId, $parent, $breadcrumb);
        }

        return $breadcrumb;
    }

    protected function getVisibleProducts($storeId, $chunk_size)
    {
        $collection = $this->_productCollectionFactory
            ->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ['neq' => 1])
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addMinimalPrice()
            ->addMediaGalleryData()
            ->setPageSize($chunk_size);

        return $collection;
    }

    protected function getProductPrice($product)
    {
        $price = null;

        switch ($product->getTypeId()) {
            case 'configurable':
                $usedProds = $product->getTypeInstance()->getUsedProducts($product);
                $price = $this->getUsedProductsMinPrice($product, $usedProds, 'regular_price');
                break;
            case 'grouped':
                $usedProds = $product->getTypeInstance()->getAssociatedProducts($product);
                $price = $this->getUsedProductsMinPrice($product, $usedProds, 'regular_price');
                break;
            case 'bundle':
                $price =  $product->getPriceInfo()->getPrice('regular_price');
                break;
            default:
                $price = $product->getPriceInfo()->getPrice('regular_price');
        }

        return $price ? $this->getPriceWithTax($product, $price) : null;
    }

    protected function getProductSalePrice($product)
    {
        $price = null;

        switch ($product->getTypeId()) {
            case 'configurable':
                $usedProds = $product->getTypeInstance()->getUsedProducts($product);
                $price = $this->getUsedProductsMinPrice($product, $usedProds, 'final_price');
                break;
            case 'grouped':
                $usedProds = $product->getTypeInstance()->getAssociatedProducts($product);
                $price = $this->getUsedProductsMinPrice($product, $usedProds, 'final_price');
                break;
            case 'bundle':
                $price = $product->getPriceInfo()->getPrice('final_price');
                break;
            default:
                $price = $product->getPriceInfo()->getPrice('final_price');
        }

        return $price ? $this->getPriceWithTax($product, $price) : null;
    }

    protected function getUsedProductsMinPrice($product, $usedProds, $type)
    {
        $min_price = null;
        $min_price_value = null;

        foreach ($usedProds as $child) {
            if ($child->getStatus() != 1) {
                continue;
            }

            if ($child->getId() != $product->getId()) {
                $price = $child->getPriceInfo()->getPrice($type);
                $price_value = $price->getAmount()->getValue();

                if ($min_price_value === null || $price_value < $min_price_value) {
                    $min_price = $price;
                    $min_price_value = $price_value;
                }
            }
        }

        return $min_price;
    }

    protected function getPriceWithTax($product, $price)
    {
        $should_include_tax = $this->_helperData->isTaxEnabled();
        $use_store_setting  = $this->_helperData->shouldUseStoreTaxSettings();
        $price_includes_tax = $this->_taxHelper->priceIncludesTax();

        if ($use_store_setting) {
            $price_display_type = $this->_taxHelper->getPriceDisplayType();

            if ($price_display_type == $this->_taxHelper->getConfig()::DISPLAY_TYPE_EXCLUDING_TAX) {
                $should_include_tax = false;
            } else {
                $should_include_tax = true;
            }
        }

        if ($should_include_tax) {
            // Note: getTaxPrice() doesn't add tax if the store is set to show price excluding tax
            return $price_includes_tax ?
                $price->getAmount()->getValue() :
                $this->_catalogData->getTaxPrice($product, $price->getAmount()->getBaseAmount(), true);
        }

        return $price->getAmount()->getBaseAmount();
    }

    protected function getAttributeValue($storeId, $product, $attribute)
    {
        $attribute_obj = $product->getResource()->getAttribute($attribute);

        if (! empty($attribute_obj)) {
            $attribute_text = $attribute_obj->setStoreId($storeId)->getFrontend()->getValue($product);

            if (! empty($attribute_text) && $attribute_text != 'no_selection') {
                return $attribute_text;
            }
        }

        return null;
    }

    protected function getStockQty($product, $childProduct = null)
    {
        if ($childProduct) {
            $parent_stock = $this->getStockQty($product);

            if ($parent_stock === 0) {
                return 0;
            }

            $product = $childProduct;
        }

        if ($this->_moduleManager->isEnabled('Magento_InventoryCatalogApi') && $this->_moduleManager->isEnabled('Magento_InventorySalesApi')) {
            $default_stock_provider = $this->_objectManager->create(\Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface::class);
            $stock_item_data = $this->_objectManager->create(\Magento\InventorySalesApi\Model\GetStockItemDataInterface::class);

            $default_stock_id = $default_stock_provider->getId();
            $stock_item = $stock_item_data->execute($product->getSku(), $default_stock_id);

            $is_salable = $stock_item[\Magento\InventorySalesApi\Model\GetStockItemDataInterface::IS_SALABLE] ?? false;
            $stock_qty = $stock_item[\Magento\InventorySalesApi\Model\GetStockItemDataInterface::QUANTITY] ?? 0;

            return $is_salable ? ($stock_qty > 0 ? (int) $stock_qty : 1) : 0;
        }

        if ($this->_moduleManager->isEnabled('Magento_CatalogInventory')) {
            $stock_registry = $this->_objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
            $stock_item = $stock_registry->getStockItem($product->getId());
            $stock_qty = $stock_item->getQty();
            $is_in_stock = $stock_item->getIsInStock();

            return $is_in_stock ? ($stock_qty > 0 ? (int) $stock_qty : 1) : 0;
        }

        $stock = null;

        try {
            $stock_item = $this->_stockItem->get($product->getId());
            $stock = ($stock_item && $stock_item->getIsInStock()) ? ($stock_item->getQty() > 0 ? (int) $stock_item->getQty() : 1) : 0;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $stock_state = $this->_objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
            $stock = $stock_state->getStockQty($product->getId());
        }

        return $stock ? $stock : 0;
    }

    protected function getProductImage($siteId, $mediaUrl, $product, $childProduct)
    {
        $image = $childProduct->getImage();
        if (empty($image) || $image == 'no_selection') {
            $image = $product->getImage();
        }

        if (empty($image) || $image == 'no_selection') {
            $image = $product->getMediaGalleryImages()->getFirstItem()->getUrl();
        } else {
            $image = $mediaUrl . 'catalog/product/' . ltrim($image, '/');
        }

        if (empty($image) || $image == 'no_selection') {
            return null;
        }

        return $image;
    }

    protected function getProductUrl($product, $storeId, $hasParent = true)
    {
        $route_path = '';

        $request_path = $product->getRequestPath();

        $route_params = [];

        $filter_data = [
            \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID => $product->getId(),
            \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
            \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::STORE_ID => $storeId,
        ];

        $rewrite = $this->urlFinder->findOneByData($filter_data);

        if ($rewrite) {
            $request_path = $rewrite->getRequestPath();
        }

        if (!empty($request_path)) {
            $route_params['_direct'] = $request_path;
        } else {
            $route_path = 'catalog/product/view';

            if (
                $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE
                    && count($parents = $this->configurable->getParentIdsByChild($product->getId())) > 0
                    && $hasParent
            ) {
                $route_params['id'] = $parents[0];
                $route_params['s'] = $product->getUrlKey();
            } else {
                $route_params['id'] = $product->getId();
                $route_params['s'] = $product->getUrlKey();
            }
        }

        $route_params['_scope'] = $storeId;
        $route_params['_nosid'] = true;
        $route_params['_type'] = \Magento\Framework\UrlInterface::URL_TYPE_LINK;

        if ($this->scopeConfig->getValue(\Magento\Store\Model\Store::XML_PATH_STORE_IN_URL) == 1) {
            $route_params['_scope_to_url'] = true;
        }

        return $this->frontendUrl->setScope($storeId)->getUrl(
            $route_path,
            $route_params
        );
    }
}
