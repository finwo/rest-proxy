<?php

namespace Finwo\RestProxy\Controller;

use Finwo\Framework\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return 'Hello World';
    }
}