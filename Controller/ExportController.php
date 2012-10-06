<?php

namespace Jeka\ShopBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/export")
 */
class ExportController extends Controller
{

    /**
     * @Route("/{exporter}")
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function exportAction($exporter)
    {
        $allowedServices = array('yandex_yml');
        if (!in_array($exporter, $allowedServices)) {
            throw new NotFoundHttpException("Export service not found");
        }


        $exporterBuilder = $this->get('jeka.shop_exporter.' . $exporter);

        $exporterBuilder->build();
        return $exporterBuilder->getResponse();
    }
}