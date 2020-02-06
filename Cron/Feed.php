<?php

namespace Salesfire\Salesfire\Cron;

/**
 * Salesfire Feed
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.2
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

        $this->mediaPath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath() . 'catalog/';

    }

    public function printLine($siteId, $text, $tab=0)
    {
        $test = $this->_file->filePutContents($this->mediaPath . $siteId . '.temp.xml', str_repeat("\t", $tab) . $text . "\n", FILE_APPEND);

    }

    public function escapeString($text)
    {
        return html_entity_decode(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode($text))));
    }

    public function execute()
    {
        $storeCollection = $this->_storeManager->getStores();
        foreach ($storeCollection as $store)
        {
            $storeId = $store->getId();
            $this->_storeManager->setCurrentStore($storeId);

            if (! $this->_helperData->isAvailable($storeId)) {
                continue;
            }

            if (! $this->_helperData->isFeedEnabled($storeId)) {
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

            $this->printLine($siteId, '<?xml version="1.0" encoding="utf-8" ?>', 0);
            $this->printLine($siteId, '<productfeed site="'.$this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'" date-generated="'.gmdate('c').'">', 0);

            $mediaUrl = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            $categories = $this->getCategories($storeId);

            if (! empty($categories)) {
                $this->printLine($siteId, '<categories>', 1);
                foreach ($categories as $category) {
                    $parent = $category->getParentCategory()->setStoreId($storeId);
                    if ($category->getLevel() <= 1) {
                        continue;
                    }

                    $this->printLine($siteId, '<category id="category_' . $category->getId() . '"' . ($parent && $parent->getLevel() > 1 ? ' parent="category_'.$parent->getId(). '"' : '') . '>', 2);

                    $this->printLine($siteId, '<id>' . $this->escapeString($category->getId()) . '</id>', 3);

                    $this->printLine($siteId, '<name><![CDATA['.$this->escapeString($category->getName()).']]></name>', 3);

                    $this->printLine($siteId, '<breadcrumb><![CDATA['.$this->escapeString($this->getCategoryBreadcrumb($storeId, $category)).']]></breadcrumb>', 3);

                    $description = $category->getDescription();
                    if (! empty($description)) {
                        $this->printLine($siteId, '<description><![CDATA['.$this->escapeString($description).']]></description>', 3);
                    }

                    $this->printLine($siteId, '<link>' . $category->getUrl(true) . '</link>', 3);

                    $keywords = $category->getMetaKeywords();
                    if (! empty($keywords)) {
                        $this->printLine($siteId, '<keywords>', 3);
                        foreach (explode(',', $keywords) as $keyword) {
                            $this->printLine($siteId, '<keyword><![CDATA['.$this->escapeString($keyword).']]></keyword>', 4);
                        }
                        $this->printLine($siteId, '</keywords>', 3);
                    }

                    $this->printLine($siteId, '</category>', 2);
                }
                $this->printLine($siteId, '</categories>', 1);
            }

            $page = 1;
            do {
                $products = $this->getVisibleProducts($storeId, $page);
                $count = count($products);

                if ($page == 1 && $count) {
                    $this->printLine($siteId, '<products>', 1);
                }

                foreach ($products as $product) {
                    $this->printLine($siteId, '<product id="product_'.$product->getId().'">', 2);

                    $this->printLine($siteId, '<id>' . $product->getId() . '</id>', 3);

                    $this->printLine($siteId, '<title><![CDATA[' . $this->escapeString($product->getName()) . ']]></title>', 3);

                    $this->printLine($siteId, '<description><![CDATA[' . $this->escapeString(substr($this->_escaper->escapeHtml(strip_tags($product->getDescription())), 0, 5000)) . ']]></description>', 3);

                    $this->printLine($siteId, '<price currency="' . $currency . '">' . $product->getFinalPrice() . '</price>', 3);

                    $this->printLine($siteId, '<sale_price currency="' . $currency . '">' . ($product->getSpecialPrice() ? $product->getSpecialPrice() : $product->getFinalPrice()) . '</sale_price>', 3);

                    $this->printLine($siteId, '<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 3);

                    $this->printLine($siteId, '<link>' . $product->getProductUrl(true) . '</link>', 3);

                    if (! empty($gender_code)) {
                        $gender = $this->getAttributeValue($storeId, $product, $gender_code);
                        if ($gender) {
                            $this->printLine($siteId, '<gender><![CDATA['.$this->escapeString($gender).']]></gender>', 3);
                        }
                    }

                    if (! empty($age_group_code)) {
                        $age_group = $this->getAttributeValue($storeId, $product, $age_group_code);
                        if ($age_group) {
                            $this->printLine($siteId, '<age_group><![CDATA['.$this->escapeString($age_group).']]></age_group>', 3);
                        }
                    }

                    if (! empty($brand_code)) {
                        $brand = $this->getAttributeValue($storeId, $product, $age_group_code);
                        if ($brand) {
                            $this->printLine($siteId, '<brand>' . $this->escapeString($brand) . '</brand>', 3);
                        }
                    } else if (! empty($default_brand)) {
                        $this->printLine($siteId, '<brand>' . $this->escapeString($default_brand) . '</brand>', 3);
                    }

                    $categories = $product->getCategoryIds();
                    if (! empty($categories)) {
                        $this->printLine($siteId, '<categories>', 3);
                        foreach ($categories as $categoryId) {
                            $this->printLine($siteId, '<category id="category_'.$categoryId.'" />', 4);
                        }
                        $this->printLine($siteId, '</categories>', 3);
                    }

                    $keywords = $product->getMetaKeywords();
                    if (! empty($keywords)) {
                        $this->printLine($siteId, '<keywords>', 3);
                        foreach (explode(',', $keywords) as $keyword) {
                            $this->printLine($siteId, '<keyword><![CDATA['.$this->escapeString($keyword).']]></keyword>', 4);
                        }
                        $this->printLine($siteId, '</keywords>', 3);
                    }

                    $this->printLine($siteId, '<variants>', 3);

                    if ($product->getTypeId() === 'configurable') {
                        $product_attributes = [];
                        $product_options = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                        foreach ($product_options as $option) {
                            $product_attributes[] = $option['attribute_code'];
                        }

                        $childProducts = $product->getTypeInstance()->getUsedProducts($product);

                        if (count($childProducts) > 0) {
                            foreach ($childProducts as $childProduct) {
                                $this->printLine($siteId, '<variant>', 4);

                                $this->printLine($siteId, '<id>' . $childProduct->getId() . '</id>', 5);

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
                                        $this->printLine($siteId, '<attributes>', 5);

                                        foreach($attributes as $attribute => $text) {
                                            $this->printLine($siteId, '<'.$attribute.'><![CDATA['.$this->escapeString($text).']]></'.$attribute.'>', 6);
                                        }

                                        $this->printLine($siteId, '</attributes>', 5);
                                    }
                                }

                                if (! empty($colour_code)) {
                                    $colour = $this->getAttributeValue($storeId, $childProduct, $colour_code);
                                    if ($colour) {
                                        $this->printLine($siteId, '<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5);
                                    }
                                }

                                $this->printLine($siteId, '<mpn><![CDATA['.$this->escapeString($childProduct->getSku()).']]></mpn>', 5);

                                $stock_item = $this->_stockItem->get($childProduct->getId());
                                $this->printLine($siteId, '<stock>'.($stock_item && $stock_item->getIsInStock() ? ($stock_item->getQty() > 0 ? (int) $stock_item->getQty() : 1) : 0).'</stock>', 5);

                                $this->printLine($siteId, '<link>' . $product->getProductUrl(true) . '</link>', 5);

                                $image = $childProduct->getImage();
                                if (! empty($image)) {
                                    $this->printLine($siteId, '<image>' . $mediaUrl . 'catalog/product' . $image . '</image>', 5);
                                }

                                $this->printLine($siteId, '</variant>', 4);
                            }
                        }
                    } else {
                        $this->printLine($siteId, '<variant>', 4);

                        $this->printLine($siteId, '<id>' . $product->getId() . '</id>', 5);

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
                                    $this->printLine($siteId, '<colour><![CDATA['.$this->escapeString($colour).']]></colour>', 5);
                                }
                            }

                            if (! empty($attributes)) {
                                $this->printLine($siteId, '<attributes>', 5);

                                foreach($attributes as $attribute => $text) {
                                    $this->printLine($siteId, '<'.$attribute.'><![CDATA['.$this->escapeString($text).']]></'.$attribute.'>', 6);
                                }

                                $this->printLine($siteId, '</attributes>', 5);
                            }
                        }

                        $this->printLine($siteId, '<mpn><![CDATA['.$this->escapeString($product->getSku()).']]></mpn>', 5);

                        $stock_item = $this->_stockItem->get($product->getId());
                        $this->printLine($siteId, '<stock>'.($stock_item && $stock_item->getIsInStock() ? ($stock_item->getMinQty() > 0 ? (int) $stock_item->getQty() : 1) : 0).'</stock>', 5);

                        $this->printLine($siteId, '<link>' . $product->getProductUrl(true) . '</link>', 5);

                        $image = $product->getImage();
                        if (! empty($image)) {
                            $this->printLine($siteId, '<image>' . $mediaUrl . 'catalog/product' . $image . '</image>', 5);
                        }

                        $this->printLine($siteId, '</variant>', 4);
                    }

                    $this->printLine($siteId, '</variants>', 3);

                    $this->printLine($siteId, '</product>', 2);
                }

                $page++;
            } while ($count >= 100);

            if ($count || $page > 1) {
                $this->printLine($siteId, '</products>', 1);
            }

            $this->printLine($siteId, '</productfeed>', 0);

            if ($this->_file->isExists($this->mediaPath . $siteId . '.xml')) {
                $this->_file->deleteFile($this->mediaPath . $siteId . '.xml');
            }

            if ($this->_file->isExists($this->mediaPath . $siteId . '.temp.xml')) {
                $this->_file->rename($this->mediaPath . $siteId . '.temp.xml', $this->mediaPath . $siteId . '.xml');
            }

            if ($this->_file->isExists($this->mediaPath . $siteId . '.temp.xml')) {
                $this->_file->deleteFile($this->mediaPath . $siteId . '.temp.xml');
            }
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

            if (! empty($attribute_text)) {
                return $attribute_text;
            }
        }

        return null;
    }
}
