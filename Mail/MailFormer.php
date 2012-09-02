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
    /** @var array */
    private $feedback;

    function __construct(\Swift_Mailer $mailer, TwigEngine $templating,
                         Translator $translator,$feedback)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->feedback = $feedback;
    }

    function sendOrderCreatedNotifcations(Cart $order, $data)
    {
        if (empty($this->feedback['order_notify_emails'])){
            return false;
        }

        $body = $this->templating->render('JekaShopBundle:Shop:_new_order_email.html.twig', array(
            'cart' => $order,
            'data' => $data
        ));
        // todo: fix hardcode, email must be from configs
        $message = \Swift_Message::newInstance()
            //->setContentType()
            ->setTo($this->feedback['order_notify_emails'])
            ->setFrom($this->feedback['order_back_email'])
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