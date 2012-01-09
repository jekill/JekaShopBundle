<?php
namespace Jeka\ShopBundle\Document;

use Vespolina\ProductBundle\Document\ProductManager as BaseManager;

class ProductManager extends BaseManager {

    function findBySlug($slug)
    {
        return $this->productRepo->findOneBy(array('slug'=>$slug));
    }
}