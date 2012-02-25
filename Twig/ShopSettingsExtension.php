<?php

namespace Jeka\ShopBundle\Twig;

use \Jeka\ShopBundle\Model\ShopSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShopSettingsExtension extends  \Twig_Extension {

    private $settings;

    function __construct(ShopSettings $settings){
        $this->settings=$settings;
    }

    public function getGlobals()
    {
        return array(
            'settings'=> $this->settings
        );
    }


    public function getName()
    {
        return 'shop_settings';
    }


}