<?php

namespace Finwo\RestProxy\Controller;

use Finwo\Framework\RestController as BaseController;
use Finwo\Framework\Route;

class RestController extends BaseController
{
    public function getAction($resource = '', $query = array())
    {
        var_dump($resource);
        var_dump($query);
        return;
    }
}