<?php
namespace Jeka\ShopBundle\Document;

use Vespolina\ProductBundle\Document\ProductManager as BaseManager;

class ProductManager extends BaseManager
{

    function findBySlug($slug)
    {
        return $this->productRepo->findOneBy(array('slug' => $slug));
    }

    function createFindAllQuery()
    {
            return $this->productRepo->createQueryBuilder();
    }

    public function createQueryFindProductsByCategories($categories=array())
    {
        $ids = array();
        foreach($categories as $c){
            //print get_class($c)."<br/>";
            $ids[]=$this->dm->createDBRef($c);
        }

        return $this->productRepo->createQueryBuilder()
            ->field('categories')->in($ids);
    }

}