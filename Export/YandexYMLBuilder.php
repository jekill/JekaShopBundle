<?php

namespace Jeka\ShopBundle\Export;

use Jeka\YandexYMLBundle\YandexYML\Document;
use Jeka\ShopBundle\Document\CounterManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Jeka\YandexYMLBundle\YandexYML\Offer\Model;
use Jeka\YandexYMLBundle\YandexYML\Offer\AbstractOffer;
use Application\Vespolina\ProductBundle\Document\Product;
use Jeka\CategoryBundle\Document\Category;
use Symfony\Component\HttpFoundation\Response;
use Jeka\CategoryBundle\Document\CategoryManager;
use Jeka\ShopBundle\Document\ProductManager;
use Jeka\YandexYMLBundle\YandexYML\Category as YmlCategory;

class YandexYMLBuilder
{

    /**
     * YandexYML Document
     * @var Document
     */
    private $ymlDocument;
    private $productManager;
    private $categoryManager;
    private $excludeCategories = array();
    /** @var Product */
    private $deliveryProduct;
    private $router;
    private $counterManager;

    public function __construct(
        Document $ymlDocument,
        ProductManager $productManager,
        CategoryManager $categoryManager,
        Router $router,
        CounterManager $counterManager,
        array $excludeCategories = array()
    ) {
        $this->ymlDocument       = $ymlDocument;
        $this->productManager    = $productManager;
        $this->categoryManager   = $categoryManager;
        $this->router            = $router;
        $this->excludeCategories = $excludeCategories;
        $this->deliveryProduct   = $this->productManager->findBySlug('courier-delivery');
        $this->counterManager    = $counterManager;
    }


    public function build()
    {
        $categories     = $this->categoryManager->getTreeList();
        $prodCategories = array();
        /** @var $c Category*/
        foreach ($categories as $c) {
            if (!$c->getNumber()) {
                $number = $this->counterManager->createNextValueFor('category');
                $c->setNumber($number);
                $this->categoryManager->updateCategory($c,false);
            }
            if ($c->getSlug() == 'root' || in_array($c->getSlug(), $this->excludeCategories)) {
                continue;
            }
            $this->ymlDocument->addCategory($this->convertCategory2YMLCategory($c));
            $prodCategories[] = $c;
        }

        $prodQuery = $this->productManager->createQueryFindProductsByCategories($prodCategories);
        $products  = $prodQuery->getQuery()->execute();

        /** @var $p Product */
        foreach ($products as $p) {
            $ymlProduct = $this->convertProduct2YMLOffer($p);
            $this->ymlDocument->addOffer($ymlProduct);
        }

        $this->ymlDocument->generateYML();
        $this->categoryManager->flushManager();
    }

    /**
     * @param $category
     * @return
     */
    public function convertCategory2YMLCategory(Category $category)
    {
        $parentId    = $category->getParent() && $category->getParent()->getSlug() != 'root' ? $category->getParent()->getId() : null;
        $ymlCategory = new YmlCategory($category->getNumber(), $category->getName(), $parentId);

        return $ymlCategory;
    }

    public function getResponse()
    {
        $response = new Response($this->ymlDocument->saveYML(), 200, array(
            'Content-type'=> 'text/xml; charset=utf-8'
        ));

        return $response;
    }

    /**
     * @return AbstractOffer
     */
    private function convertProduct2YMLOffer(Product $product)
    {
        $offer = new Model($product->getId(), !$product->getDisabled());

        $offer->categoryId   = $product->getFirstCategory()->getId();
        $offer->currencyId   = 'RUR';
        $offer->delivery     = true;
        $offer->description  = $product->getDescription();
        $offer->downloadable = false;
        $offer->price        = $product->getPrice();

        if (in_array(date('N'), array(6, 7))) {
            $offer->local_delivery_cost = 0;
        } else {
            $offer->local_delivery_cost = $this->deliveryProduct->getPrice();
        }
        $offer->name = $product->getName();
        if ($product->getPreviewImage()) {
            $offer->picture = $this->ymlDocument->getShopUrl() . $product->getPreviewImage()->getSrc();
        }

        if ($product->getFeature('Бренд')) {
            $offer->vendor = $product->getFeature('Бренд')->getName();
        }

        if ($product->getFeature('Страна производитель')) {
            $offer->country_of_origin = $product->getFeature('Страна производитель')->getName();
        }

        if ($product->getFeature('Артикул')) {
            $offer->vendorCode = $product->getFeature('Артикул')->getName();
        }

        $offer->url = $this->router->generate(
            'shop_product',
            array(
                'category_slug'=> $product->getFirstCategory()->getSlug(),
                'product_slug' => $product->getSlug()
            ),
            true
        );

        return $offer;
    }
}