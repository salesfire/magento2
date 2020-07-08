<?php

namespace Salesfire\Salesfire\Cron;

/**
 * Salesfire Feed
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.6
 */
class Feed
{
    private $_helperData;
    private $_storeManager;
    private $_productCollectionFactory;
    private $_filesystem;
    private $_file;
    private $_escaper;
    private $_taxHelper;
    private $_stockItem;

    private $_writer;
    private $_logger;

    private $mediaPath;

    public function __construct(
        \Salesfire\Salesfire\Helper\Data $helperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Catalog\Helper\Data $taxHelper,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItem
    ) {
        $this->_helperData                = $helperData;
        $this->_storeManager              = $storeManager;
        $this->_productCollectionFactory  = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_filesystem                = $filesystem;
        $this->_file                      = $file;
        $this->_escaper                   = $escaper;
        $this->_taxHelper                 = $taxHelper;
        $this->_stockItem                 = $stockItem;

        $this->_writer = new \Zend\Log\Writer\Stream(BP . '/var/log/salesfire.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($this->_writer);

        $this->mediaPath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . 'catalog';

        if (! $this->_file->isExists($this->mediaPath)) {
            $this->_logger->info('Creating media catalog directory for feeds: ' . $this->mediaPath);
            $this->_file->createDirectory($this->mediaPath, 777);

            if (! $this->_file->isExists($this->mediaPath)) {
                $this->_logger->error('Unable to create media catalog directory: ' . $this->mediaPath);
            }
        }
    }

    public function getMediaPath()
    {
        return $this->mediaPath . '/';
    }

    public function printLine($siteId, $text, $tab=0)
    {
        $this->_file->filePutContents($this->getMediaPath() . $siteId . '.temp.xml', str_repeat("\t", $tab) . $text . "\n", FILE_APPEND);
    }

    public function printLines($siteId, $text)
    {
        foreach ($text as $line) {
            $this->printLine($siteId, $line[0], isset($line[1]) ? $line[1] : 0);
        }
    }

    public function escapeString($text)
    {
        return html_entity_decode(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode($text))));
    }

    public function execute()
    {
        $storeCollection = $this->_storeManager->getStores();

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $stock_state = $object_manager->get('\Magento\CatalogInventory\Api\StockStateInterface');

        foreach ($storeCollection as $store)
        {
            $storeId = $store->getId();
            $this->_logger->info('======================================');
            $this->_logger->info('Salesfire Feed - Store #' . $storeId);
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

            $this->printLine($siteId, '<?xml version="1.0" encoding="utf-8" ?>', 0);
            $this->printLine($siteId, '<productfeed site="'.$this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'" date-generated="'.gmdate('c').'" version="' . $this->_helperData->getVersion() . '">', 0);

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
                            $text[] = ['<description><![CDATA['.$this->escapeString(substr($this->_escaper->escapeHtml(strip_tags($description)), 0, 2000)).']]></description>', 3];
                        }

                        $text[] = ['<link>' . $category->getUrl(true) . '</link>', 3];

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
                        $this->_logger->error('Error with category: ' . $product->getId());
                        $this->_logger->error('- File: ' . $e->getFile() . ' - ' . $e->getLine());
                        $this->_logger->error('- Message: ' . $e->getMessage());
                    }
                }
                $this->printLine($siteId, '</categories>', 1);

                $this->_logger->info('- Category export completed');
            }
            $categories = null;

            $page = 1;
            do {
                $products = $this->getVisibleProducts($storeId, $page);
                $count = count($products);

                if ($page == 1 && $count) {
                    $this->_logger->info('- Exporting products');
                    $this->printLine($siteId, '<products>', 1);
                }

                foreach ($products as $product) {
                    try {
                        $text = [];

                        $text[] = ['<product id="product_'.$product->getId().'">', 2];

                        $text[] = ['<id>' . $product->getId() . '</id>', 3];

                        $text[] = ['<title><![CDATA[' . $this->escapeString($product->getName()) . ']]></title>', 3];

                        $text[] = ['<description><![CDATA[' . $this->escapeString(substr($this->_escaper->escapeHtml(strip_tags($product->getDescription())), 0, 5000)) . ']]></description>', 3];

                        $price = $product->getFinalPrice();
                        $saleprice = $product->getSpecialPrice();

                        $text[] = ['<price currency="' . $currency . '">' . $price . '</price>', 3];

                        $text[] = ['<sale_price currency="' . $currency . '">' . ($saleprice ? $saleprice : $price) . '</sale_price>', 3];

                        $text[] = ['<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 3];

                        $text[] = ['<link>' . $product->getProductUrl(true) . '</link>', 3];

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
                            $brand = $this->getAttributeValue($storeId, $product, $age_group_code);
                            if ($brand) {
                                $text[] = ['<brand><![CDATA[' . $this->escapeString($brand) . ']]></brand>', 3];
                            } else {
                                $text[] = ['<brand><![CDATA[' . $this->escapeString($default_brand) . ']]></brand>', 3];
                            }
                        } else if (! empty($default_brand)) {
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
                                    $text[] = ['<variant>', 4];

                                    $text[] = ['<id>' . $childProduct->getId() . '</id>', 5];

                                    $attributes_to_show = array_merge($product_attributes, $attribute_codes);

                                    if (! empty($attributes_to_show)) {
                                        $attributes = [];

                                        foreach($attributes_to_show as $attribute) {
                                            if (empty($attribute) || in_array($attribute, array('id', 'mpn', 'stock', 'link', 'image', $age_group_code, $gender_code, $brand_code, $colour_code))) {
                                                continue;
                                            }

                                            $attribute_text = $this->getAttributeValue($storeId, $childProduct, $attribute);
                                            if ($attribute_text) {
                                                $attributes[$attribute] = $attribute_text;
                                            }
                                        }

                                        if (! empty($attributes)) {
                                            $text[] = ['<attributes>', 5];

                                            foreach($attributes as $attribute => $text) {
                                                $text[] = ['<'.$attribute.'><![CDATA['.$this->escapeString($text).']]></'.$attribute.'>', 6];
                                            }

                                            $text[] = ['</attributes>', 5];
                                        }
                                    }

                                    if (! empty($colour_code)) {
                                        $colour = $this->getAttributeValue($storeId, $childProduct, $colour_code);
                                        if ($colour) {
                                            $text[] = ['<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5];
                                        }
                                    }

                                    $text[] = ['<mpn><![CDATA['.$this->escapeString($childProduct->getSku()).']]></mpn>', 5];

                                    $stock = null;
                                    try {
                                        $stock_item = $this->_stockItem->get($childProduct->getId());
                                        $stock = ($stock_item && $stock_item->getIsInStock()) ? ($stock_item->getQty() > 0 ? (int) $stock_item->getQty() : 1) : 0;
                                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                                        $stock = $stock_state->getStockQty($childProduct->getId());
                                    }

                                    $text[] = ['<stock>' . ($stock ? $stock : 0) .'</stock>', 5];

                                    $text[] = ['<link>' . $product->getProductUrl(true) . '</link>', 5];

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

                                foreach($attribute_codes as $attribute) {
                                    if (empty($attribute) || in_array($attribute, array('id', 'mpn', 'stock', 'link', 'image', $age_group_code, $gender_code, $brand_code, $colour_code))) {
                                        continue;
                                    }

                                    $attribute_text = $this->getAttributeValue($storeId, $product, $attribute);
                                    if ($attribute_text) {
                                        $attributes[$attribute] = $attribute_text;
                                    }
                                }

                                if (! empty($colour_code)) {
                                    $colour = $this->getAttributeValue($storeId, $product, $colour_code);
                                    if ($colour) {
                                        $text[] = ['<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5];
                                    }
                                }

                                if (! empty($attributes)) {
                                    $text[] = ['<attributes>', 5];

                                    foreach($attributes as $attribute => $text) {
                                        $text[] = ['<'.$attribute.'><![CDATA['.$this->escapeString($text).']]></'.$attribute.'>', 6];
                                    }

                                    $text[] = ['</attributes>', 5];
                                }
                            }

                            $text[] = ['<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 5];

                            $stock = null;
                            try {
                                $stock_item = $this->_stockItem->get($product->getId());
                                $stock = ($stock_item && $stock_item->getIsInStock()) ? ($stock_item->getQty() > 0 ? (int) $stock_item->getQty() : 1) : 0;
                            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                                $stock = $stock_state->getStockQty($product->getId());
                            }

                            $text[] = ['<stock>' . ($stock ? $stock : 0) .'</stock>', 5];

                            $text[] = ['<link>' . $product->getProductUrl(true) . '</link>', 5];

                            $image = $this->getProductImage($siteId, $mediaUrl, $product, $product);
                            if (! empty($image)) {
                                $text[] = ['<image>' . $image  . '</image>', 5];
                            }

                            $text[] = ['</variant>', 4];
                        }

                        $text[] = ['</variants>', 3];

                        $text[] = ['</product>', 2];

                        $this->printLines($siteId, $text);
                    } catch (\ Exception $e) {
                        $this->_logger->error('Error with product: ' . $product->getId());
                        $this->_logger->error('- File: ' . $e->getFile() . ' - ' . $e->getLine());
                        $this->_logger->error('- Message: ' . $e->getMessage());
                    }
                }

                $page++;
                $count = 1;
            } while ($count >= 100);

            if ($count || $page > 1) {
                $this->_logger->info('- Product export completed');
                $this->printLine($siteId, '</products>', 1);
            }

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
        }
    }

    public function getCategories($storeId)
    {
        $rootCategoryId = $this->_storeManager->getStore($storeId)->getRootCategoryId();
        $categories = $this->_categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addFieldToFilter('is_active', 1)
            ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
            ->addAttributeToSelect('*');

        return $categories;
    }

    public function getCategoryBreadcrumb($storeId, $category, $breadcrumb='')
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

    protected function getVisibleProducts($storeId, $curPage=1, $pageSize=100)
    {
        $collection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', array('neq' => 1))
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->addMinimalPrice()
            ->setPageSize($pageSize);

        if (!empty($curPage))
        {
            $collection->setCurPage($curPage);
        }

        $collection->clear();

        $collection->addMediaGalleryData();

        return $collection;
    }

    protected function getProductPrice($product, $currency, $bundlePriceModel)
    {
        switch($product->getTypeId())
        {
            case 'grouped':
                return $this->_attributeOptionsPrice($product, $product->getMinimalPriceattributeOptions);
            break;

            case 'bundle':
                return $bundlePriceModel->getTotalPrices($product, 'min', 1);
            break;

            default:
                return $this->_taxHelper->getTaxPrice($product, $product->getPrice(), true);
        }
    }

    protected function getProductSalePrice($product, $currency, $bundlePriceModel)
    {
        switch($product->getTypeId())
        {
            case 'grouped':
                return $this->_taxHelper->getTaxPrice($product, $product->getMinimalPrice(), true);
            break;

            case 'bundle':
                return $bundlePriceModel->getTotalPrices($product, 'min', 1);
            break;

            default:
                if ($product->getSpecialPrice()) {
                    return $this->_taxHelper->getTaxPrice($product, $product->getSpecialPrice(), true);
                }
                return $this->_taxHelper->getTaxPrice($product, $product->getFinalPrice(), true);
        }
    }

    protected function getAttributeValue($storeId, $product, $attribute) {
        $attribute_obj = $product->getResource()->getAttribute($attribute);

        if(! empty($attribute_obj)) {
            $attribute_text = $attribute_obj->setStoreId($storeId)->getFrontend()->getValue($product);

            if (! empty($attribute_text) && $attribute_text != 'no_selection') {
                return $attribute_text;
            }
        }

        return null;
    }

    protected function getProductImage($siteId, $mediaUrl, $product, $childProduct) {
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
}
