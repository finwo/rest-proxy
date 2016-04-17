<?php

namespace Finwo\Framework;

class Controller
{
    /**
     * @var ParameterBag
     */
    protected $container;

    public function __construct(ParameterBag $container)
    {
        $this->container = $container;
    }
}