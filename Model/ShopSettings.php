<?php
namespace Jeka\ShopBundle\Model;

class ShopSettings
{

    private $siteName = 'Альбомчик.Ру';
    private $contacts = array(
        'phones' => '+7 (499) 3434 1 34'
    );


    public function __construct()
    {
        //$this->siteName=$sitename
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
}