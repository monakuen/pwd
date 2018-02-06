<?php

namespace SP\ShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('SPShareBundle:Default:index.html.twig');
    }
}
