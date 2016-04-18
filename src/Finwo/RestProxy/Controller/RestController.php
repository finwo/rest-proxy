<?php

namespace Finwo\RestProxy\Controller;

use Finwo\Framework\RestController as BaseController;
use Finwo\Framework\Route;

class RestController extends BaseController
{
    public function getAction($resource = '', $query = array())
    {
        return array(
            'html' => array(
                'head' => array(
                    'I\'m fine, thank you'
                ),
                'body' => array(
                    'div' => array(
                        'How are you doing?'
                    )
                )
            )
        );
    }
}