<?php

namespace Jeka\ShopBundle\Controller;

use \Application\Vespolina\ProductBundle\Document\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FirstpageController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Template
     */
    public function indexAction()
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');

        $products = $pm->findBy(
            array(
                'type'=> Product::PHYSICAL,
                'disabled'=>false
            ),
            array('random'=>1),
            8
        );
        return array(
            'products'=>$products
        );
    }
}
