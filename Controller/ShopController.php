<?php

namespace Jeka\ShopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ShopController extends Controller
{
    /**
     * @Route("/{category_slug}/{product_slug}", name="shop_product")
     * @Template
     */
    public function productAction($category_slug, $product_slug)
    {
        /** @var $cat_manager \Jeka\CategoryBundle\Document\CategoryManager */
        $cat_manager = $this->get('jeka.category_manager');
        /** @var $prod_manager \Vespolina\ProductBundle\Document\ProductManager */
        $prod_manager = $this->get('vespolina.product_manager');
        $product = $prod_manager->findBySlug($product_slug);
        $category = $cat_manager->findBySlug($category_slug);
        return array(
            'product' => $product,
            'category' => $category
        );
    }



    /**
     * @Route("/to-cart", name="shop_to_cart")
     * @Template
     */
    public function toCartAction()
    {
        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();
        /** @var $prod_man \Jeka\ShopBundle\Document\ProductManager */
        $prod_man = $this->get('vespolina.product_manager');
        /** @var $product \Application\Vespolina\ProductBundle\Document\Product */
        $product = $prod_man->findProductById($req->get('product_id'));
        $quantity = $req->get('quantity', 1);
        if ($quantity < 1) $quantity = 1;

        if (!$product){
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        /** @var $cart_manager  \Vespolina\CartBundle\Document\CartManager*/
        $cart_manager= $this->get('vespolina.cart_manager');
        $owner = $this->getUser();
        /** @var $cart \Vespolina\CartBundle\Document\Cart */
        $cart = $cart_manager->findOpenCartByOwner($owner);

        if ($req->getMethod() == 'POST') {


            if (!$cart){
                $cart = $cart_manager->createCart();
            }
            /** @var $cart_item \Vespolina\CartBundle\Document\CartItem */
            $cart_item = $cart_manager->createItem();
            $cart_item->setName($product->getName());
            $cart_item->setDescription($product->getDescription());
            $cart_item->setPrice($product->getPrice());
            $cart_item->setQuantity($quantity);

            $cart->addItem($cart_item);

            $cart_manager->updateCart($cart);

            if (!$req->isXmlHttpRequest()) {
                return $this->redirect($this->generateUrl('shop_product', array(
                    'category_slug'=>$product->getFirstCategory()->getSlug(),
                    'product_slug'=>$product->getSlug()
                )));
            }

            return array(
                'product'=>$product
            );

        }
        return $this->redirect($this->generateUrl('shop_cart'));
    }


    /**
     * @Route("/cart", name="shop_cart")
     * @Template
     */
    public function cartAction()
    {
        return array();
    }


    /**
     * @Route("/{slug}", name="shop_category")
     * @Template
     */
    public function categoryAction($slug)
    {
        $cm = $this->get('jeka.category_manager');
        $category = $cm->findBySlug($slug);
        if (!$category)
        {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
        }

        //print $cm->findChildren($category);
        return array(
            'category'=>$category,
            'children'=>$cm->findChildren($category)
        );

    }

}
