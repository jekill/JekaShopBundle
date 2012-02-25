<?php
namespace Jeka\ShopBundle\Model;

class ShopSettings
{

    private $container;
    private $siteName = 'Альбомчик.Ру';
    private $contacts = array(
        'phones' => '+7 (499) 3434 1 34'
    );


    public function __construct($container)
    {
        //$this->siteName=$sitename
        $this->container = $container;
    }

    public function getContacts()
    {
        return $this->contacts;
    }

    public function getSiteName()
    {
        return $this->siteName;
    }

    public function getSiteLogoDescription(){
        return 'Магазин фототоваров';
    }

    public function getAnalyticsCode(){
        return $this->container->getParameter('jeka_shop.analytics');
    }
}