<?php

namespace Jeka\ShopBundle\Controller;

use \Application\Vespolina\CartBundle\Document\Cart;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ShopController extends Controller
{

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

        if (!$product) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        /** @var $cart_manager  \Vespolina\CartBundle\Document\CartManager*/
        $cart_manager = $this->get('vespolina.cart_manager');

        if ($req->getMethod() == 'POST') {

            $cart = $this->getCart();

            /** @var $cart_item \Vespolina\CartBundle\Document\CartItem */
            $cart_item = $cart_manager->createItem($product);
            $cart_item->setName($product->getName());
            $cart_item->setDescription($product->getDescription());
            $cart_item->setPrice($product->getPrice());
            $cart_item->setQuantity($quantity);

            $cart->addItem($cart_item);

            $cart_manager->updateCart($cart);

            if (!$req->isXmlHttpRequest()) {
                return $this->redirect($this->generateUrl('shop_product', array(
                    'category_slug' => $product->getFirstCategory()->getSlug(),
                    'product_slug' => $product->getSlug()
                )));
            }

            return array(
                'product' => $product,
                'cart' => $cart
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
        $cart = $this->getCart();

        /** @var $cart_manager \Application\Vespolina\CartBundle\Document\CartManager */
        $cart_manager = $this->get('vespolina.cart_manager');


        if ($cart->getTotal()<1)
        {
            return $this->render('JekaShopBundle:Shop:cartEmpty.html.twig');
        }

        /** @var $t \Symfony\Bundle\FrameworkBundle\Translation\Translator */
        $t = $this->get('translator');
        $customer_info = $this->getRequest()->getSession()->get('customer_info',array());
        $form = $this->createFormBuilder($customer_info)
            ->add('name','text',array('label'=>$t->trans('customer.name')))
            ->add('email','email',array('required'=>false,'label'=>$t->trans('customer.email')))
            ->add('phone','text',array('label'=>$t->trans('customer.phone')))
            ->add('address','text',array('required'=>false,'label'=>$t->trans('customer.address')))
            ->add('comments', 'textarea',array('required'=>false,'label'=>$t->trans('customer.comments')))
            ->getForm();

        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');
        $moscow_delivery = $pm->findBySlug('courier-delivery');


        if ($this->getRequest()->getMethod()=='POST')
        {
            $form->bindRequest($this->getRequest());
            if ($form->isValid())
            {
                $data = $form->getData();
                $cart->setState(Cart::STATE_LOCKED);
                $cart->setCustomersData($data);
                $delivery_type = $this->getRequest()->get('delivery');
                if ('moscow_courier'==$delivery_type)
                {
                    $cart->setDeliveryCost($moscow_delivery->getPrice());

                }

                $cart->setDeliveryType($delivery_type);

                $cart_manager->saveAsLocked($cart);
                /** @var $mail_former \Jeka\ShopBundle\Mail\MailFormer */
                $mail_former = $this->get('jeka.mail_former');
                $mail_former->sendOrderCreatedNotifcations($cart,$data);

                $this->getRequest()->getSession()->setFlash('success',$t->trans('your.order.sended'));
                $this->getRequest()->getSession()->set('customer_info',$data);

                return $this->redirect($this->generateUrl('shop_cart'));
            }
        }
        return array(
            'cart' => $cart,
            'moscow_courier'=>$moscow_delivery,
            'form' => $form->createView()
        );
    }


    /**
     * @Route("/cart/{id}/remove", name="shop_cart_remove")
     */
    public function cartRemoveAction($id)
    {
        $pm = $this->get('vespolina.product_manager');
        $product = $pm->findProductById($id);

        if (!$product)
        {
            throw $this->createNotFoundException();
        }

        $cart = $this->getCart();
        $cart->removeCartableItemById($id);
        /** @var $cart_manager \Vespolina\CartBundle\Document\CartManager */
        $cart_manager = $this->get('vespolina.cart_manager');
        $cart_manager->updateCart($cart);
        /** @var $trans \Symfony\Component\Translation\Translator */
        $trans = $this->get('translator');
        $this->getRequest()->getSession()->setFlash('success', $trans->trans(sprintf('"%s" removed',$product->getName())));
        return $this->redirect($this->generateUrl('shop_cart'));
    }

    /**
     * @Route("/cart/clean", name="shop_cart_clean")
     */
    public function cartCleanAction()
    {
        $cart = $this->getCart();
        $cart->clearItems();
        /** @var $cart_manager \Vespolina\CartBundle\Document\CartManager */
        $cart_manager = $this->get('vespolina.cart_manager');
        $cart_manager->updateCart($cart);
        /** @var $trans \Symfony\Component\Translation\Translator */
        $trans = $this->get('translator');
        $this->getRequest()->getSession()->setFlash('success', $trans->trans('Your shopping cart was cleared'));
        return $this->redirect($this->generateUrl('shop_cart'));
    }

    /**
     * @Route("/cart/autoSave", name="shop_cart_autosave")
     * @Template
     */
    public function cartAutosaveAction()
    {
        $cart = $this->getCart();
        /** @var $cart_manager  \Vespolina\CartBundle\Document\CartManager*/
        $cart_manager = $this->get('vespolina.cart_manager');
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');

        foreach($this->getRequest()->get('quantity') as $product_id=>$quantity)
        {
            $product = $pm->findProductById($product_id);
            if ($product)
            {
                /** @var $cart_item \Vespolina\CartBundle\Document\CartItem */
                $cart_item = $cart_manager->createItem($product);
                $cart_item->setQuantity($quantity);

                $cart->addItem($cart_item);
            }
        }

        $cart_manager->updateCart($cart);

        return array(
            'cart'=>$cart
        );
    }



    /**
     * @Template
     */
    public function cartGlobalBlockAction()
    {
        return array(
            'cart' => $this->getCart()
        );
    }

    /**
     * @Route("/{slug}", name="shop_category")
     * @Template
     */
    public function categoryAction($slug)
    {
        $request = $this->getRequest();
        /** @var $cat_manager \Jeka\CategoryBundle\Document\CategoryManager */
        $cat_manager = $this->get('jeka.category_manager');
        $category = $cat_manager->findBySlug($slug);
        if (!$category) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
        }

        $this->getRequest()->attributes->set('current_category', $category);

        /** @var $prod_manager \Jeka\ShopBundle\Document\ProductManager */
        $prod_manager = $this->get('vespolina.product_manager');

        $desc = $cat_manager->findDescendants($category);
        //print_r(count($desc));exit;
        $categories = array($category);
        if ($desc && count($desc) > 0) {
            $categories = array_merge($categories, $desc->toArray());
        }
        $products_query = $prod_manager->createQueryFindProductsByCategories($categories);

        $pager = $this->createPager($products_query);
        $pager->setMaxPerPage(120);
        $pager->setCurrentPage($request->get('page', 1));

        //print $cm->findChildren($category);
        return array(
            'category' => $category,
            'children' => $cat_manager->findChildren($category),
            'pager' => $pager
        );

    }


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
        /** @var $product \Application\Vespolina\ProductBundle\Document\Product */
        $product = $prod_manager->findBySlug($product_slug);
        $category = $cat_manager->findBySlug($category_slug);

        //var_dump($product->getIdentifierSet());exit;

        return array(
            'product' => $product,
            'category' => $category,
            'cart' => $this->getCart()
        );
    }


    public function createPager($query)
    {
        $adapter = new \Pagerfanta\Adapter\DoctrineODMMongoDBAdapter($query);
        $pager = new \Pagerfanta\Pagerfanta($adapter);
        return $pager;
    }


    /**
     * @Template
     */
    public function categoriesNavAction()
    {
        /** @var $cat_manager \Jeka\CategoryBundle\Document\CategoryManager */
        $cat_manager = $this->get('jeka.category_manager');
        $root = $cat_manager->getRoot();

        $first_level = $cat_manager->findChildren($root);

        $current_category = $this->initCurrentCategory();
        $tree_layers = array();

        if ($current_category !== null && $current_category->getAncestors()->count() > 1) {
            foreach ($current_category->getAncestors() as $i => $category)
            {
                if ($i < 1) continue;
                $tree_layers[] = $cat_manager->findChildren($category);
            }
        }

        if ($current_category !== null && count($current_category->getParent())>0) {
            $children = $cat_manager->findChildren($current_category);
            if (count($children) > 0) {
                $tree_layers[] = $children;
            }
        }

        return array(
            'first_level' => $first_level,
            'current_category' => $current_category,
            'tree_layers' => $tree_layers
        );
    }



    /**
     * @return \Jeka\CategoryBundle\Document\Category|null
     */
    private function initCurrentCategory()
    {
        /** @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $this->getRequest();
        /** @var $router \Symfony\Bundle\FrameworkBundle\Routing\Router */
        $router = $this->get('router');

        $current_category = $request->attributes->get('current_category');

        if (!$current_category) {
            $url = str_replace($request->getBaseUrl(), '', $request->getRequestUri());
            $cur_routes = ($router->match($url));
            if ($cur_routes !== false) {
                $slug = '';
                switch ($cur_routes['_route'])
                {
                    case 'shop_category':
                        $slug = $cur_routes['slug'];
                        break;
                    case 'shop_product':
                        $slug = $cur_routes['category_slug'];
                        break;
                }
                if ($slug) {
                    $cat_manager = $this->get('jeka.category_manager');
                    $current_category = $cat_manager->findBySlug($slug);
                    $request->attributes->set('current_category', $current_category);
                }
            }
        }
        return $current_category;
    }

    public function getCart()
    {
        /** @var $cart_manager  \Vespolina\CartBundle\Document\CartManager*/
        $cart_manager = $this->get('vespolina.cart_manager');

        /** @var $session \Symfony\Component\HttpFoundation\Session */
        $session = $this->getRequest()->getSession();
        $session_id = $session->getId();
        /** @var $cart Cart */
        $cart = $cart_manager->findOpenCartByOwner($session_id);

        if (!$cart) {
            $cart = $cart_manager->createCart();
            $cart->setOwner($session_id);
            return $cart;
        }
        return $cart;
    }


}
