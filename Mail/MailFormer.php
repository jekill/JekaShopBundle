<?php
namespace Jeka\ShopBundle\Mail;

use \Application\Vespolina\CartBundle\Document\Cart;
use \Symfony\Bundle\FrameworkBundle\Translation\Translator;
use \Symfony\Bundle\TwigBundle\TwigEngine;

class MailFormer
{
    private $mailer;
    private $templating;
    private $translator;

    function __construct(\Swift_Mailer $mailer, TwigEngine $templating,
                         Translator $translator)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->translator = $translator;
    }

    function sendOrderCreatedNotifcations(Cart $order, $data)
    {
        $body = $this->templating->render('JekaShopBundle:Shop:_new_order_email.html.twig', array(
            'cart' => $order,
            'data' => $data
        ));
        // fix hardcode
        $message = \Swift_Message::newInstance()
            //->setContentType()
            ->setTo('albomchik.ru@jeka.ru')
            ->setFrom('zakaz@albomchik.ru')
            ->setSubject(sprintf($this->translator->trans('New order #%s'), $order->getNumber()))
            ->setBody($body,'text/html');

        $this->mailer->send($message);
    }

    function sendFeedbackMail($data){
        $body = $this->templating->render('JekaPagesBundle:Pages:_feedback_mail.html.twig',array(
            'name'=>$data['name'],
            'email'=>$data['email'],
            'message'=>$data['message']
        ));

        $message = \Swift_Message::newInstance()
            ->setTo('albomchik.ru@jeka.ru')
            ->setFrom('zakaz@albomchik.ru')
            ->setSubject('[Albomchik.Ru] Feedback form')
            ->setBody($body,'text/html');
        $this->mailer->send($message);
    }

}