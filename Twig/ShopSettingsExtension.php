<?php

namespace Jeka\ShopBundle\Twig;

use \Jeka\ShopBundle\Model\ShopSettings;

class ShopSettingsExtension extends  \Twig_Extension{

    public function getGlobals()
    {
        return array(
            'settings'=> new ShopSettings()
        );
    }


    public function getName()
    {
        return 'shop_settings';
    }


}