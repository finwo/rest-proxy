<?php

namespace Finwo\Framework;

use Finwo\PropertyAccessor\PropertyAccessor;
use Invoker\ParameterResolver\ParameterResolver;

class ParameterBag implements ParameterResolver
{
    /**
     * Parameters
     * Contain the actual stuff
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @return PropertyAccessor
     */
    protected function getAccessor()
    {
        if ( !($this->accessor instanceof PropertyAccessor) ) {
            $this->accessor = new PropertyAccessor();
        }
        return $this->accessor;
    }

    /**
     * ParameterBag constructor.
     * @param array $data
     */
    public function __construct($data = array())
    {
        // Insert data if provided
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }
    }

    public function toArray()
    {
        return $this->parameters;
    }

    public function getParameters(
        \ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    )
    {
        var_dump($reflection, $providedParameters, $resolvedParameters);
        die();
    }

    public function set($key, $value)
    {
        $acc = $this->getAccessor();
        $acc->set($this->parameters, $key, $value, '.');
        return $this;
    }

    public function get($key)
    {
        $acc = $this->getAccessor();
        return $acc->get($this->parameters, $key, '.');
    }
}