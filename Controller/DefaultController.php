<?php

namespace Walterra\J4p5Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('WalterraJ4p5Bundle:Default:index.html.twig', array('name' => $name));
    }
}
