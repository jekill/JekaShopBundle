<?php
namespace Jeka\ShopBundle\Document;

use \Jeka\CategoryBundle\Document\Category;
use Vespolina\ProductBundle\Document\ProductManager as BaseManager;

class ProductManager extends BaseManager
{

    function findBySlug($slug)
    {
        return $this->productRepo->findOneBy(array('slug' => $slug));
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    function createFindAllQuery()
    {
        return $this->productRepo->createQueryBuilder();
    }

    /**
     * @param array $categories
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function createQueryFindProductsByCategories($categories = array())
    {
        $ids = array();
        foreach ($categories as $c) {
            //print get_class($c)."<br/>";
            //$ids[] = $this->dm->createDBRef($c);
            $ids[] = new \MongoId($c->getId());
        }

        return $this->productRepo->createQueryBuilder()
            ->field('categories.$id')->in($ids);
    }

    public function createQueryFindProductsByCategory(Category $category)
    {
        return $this->productRepo->createQueryBuilder()
            ->field('categories.id')->equals($category->getId());
    }

    /**
     * @return \Application\Vespolina\ProductBundle\Document\Product
     */
    public function createProduct($type = 'default')
    {
        return parent::createProduct($type);
    }

    public function removeProduct($product)
    {
        $this->dm->remove($product);
        $this->dm->flush();
    }

    public function findProductByFeatureId($feature_id)
    {
        return $this->productRepo->findOneBy(array('features._id' => new \MongoId($feature_id)));
        $qb = $this->productRepo->createQueryBuilder();
        //$qb->getQuery()
        $qb->field('features.id')->equals($feature_id);

        return $qb->getQuery()->getSingleResult();
    }

    public function removeFeature($feature_id)
    {
        $product = $this->findProductByFeatureId($feature_id);
        if ($product) {
            $this->productRepo->createQueryBuilder()
                ->field('id')->equals($product->getId())
                ->update()
                ->field('features')->pull(array('_id' => new \MongoId($feature_id)))
                ->getQuery()
                ->execute();
        }
    }

    public function removeFeatureAll($type)
    {
        $this->productRepo->createQueryBuilder()
            ->update()
            ->field('features.type')->equals($type)
            ->field('features')->pull(array('type' => $type))
            ->getQuery()
            ->execute();
    }


    public function flush()
    {
        $this->dm->flush();
    }

    public function disableProduct($product, $flush = true)
    {
        $product->setDisabled(true);
        $this->updateProduct($product, $flush);
    }

    public function enableProduct($product, $flush = true)
    {
        $product->setDisabled(false);
        $this->updateProduct($product, $flush);
    }

    public function disableToggleProduct($product, $flush = true)
    {
        $product->setDisabled(!$product->getDisabled());
        $this->updateProduct($product, $flush);
    }
}