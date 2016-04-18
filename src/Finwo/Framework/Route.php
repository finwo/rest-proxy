<?php

namespace Finwo\Framework;

use Finwo\Datatools\Mappable;

class Route extends Mappable
{
    // Custom/matching values
    protected $type = 'default';
    protected $name;
    protected $host;
    protected $path;
    protected $method;
    protected $controller;
    protected $defaults;
    protected $prefix;
    protected $parsedUri;

    // After-parse values
    protected $parameters;

    // Request info
    protected $requestUri = '';
    protected $requestMethod = '';
    protected $httpHost = '';

    public function __construct($name, $config)
    {
        $this->name = $name;
        parent::__construct($config);
    }

    public function match()
    {
        // If this function gets called, process the request.. No sooner

        // Handle path prefix
        if(strlen($this->prefix)) {
            if( substr($this->requestUri, 0, strlen($this->prefix)) == $this->prefix ) {
                $this->requestUri = substr($this->requestUri, strlen($this->prefix));
            } else {
                return false;
            }
        }

        // Host match
        if (!is_null($this->host) && !preg_match('/' . $this->host . '/i', $this->httpHost)) {
            return false;
        }

        // Method match
        if (!is_null($this->method) && !preg_match('/' . $this->method . '/i', $this->requestMethod)) {
            return false;
        }

        // Parse the path
        $parsed = array_merge(array(
            'path' => '/',
            'query' => ''
        ), parse_url($this->requestUri));

        // Store parsed uri
        $this->parsedUri = $parsed;
        parse_str($this->parsedUri['query'], $query);
        $this->parsedUri['query'] = $query;

        if (!is_null($this->path) && !preg_match('/' . $this->path . '/i', $parsed['path'], $this->parameters)) {
            return false;
        }

        // Strip parameters of numeric keys
        foreach ($this->parameters as $key => $parameter) {
            if (is_int($key)) {
                unset($this->parameters[$key]);
            }
        }

        // Parse the query
        parse_str($parsed['query'], $matches);
        $this->parameters = array_merge((array)$this->defaults, $this->parameters, $matches);

        return true;
    }

    /**
     * @return mixed
     */
    public function getParsedUri()
    {
        return $this->parsedUri;
    }

    /**
     * @param mixed $parsedUri
     * @return Route
     */
    public function setParsedUri($parsedUri)
    {
        $this->parsedUri = $parsedUri;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Route
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $name
     * @return Route
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     * @return Route
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return Route
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return Route
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param mixed $controller
     * @return Route
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param mixed $defaults
     * @return Route
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     * @return Route
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * @param string $requestUri
     * @return Route
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $requestMethod
     * @return Route
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getHttpHost()
    {
        return $this->httpHost;
    }

    /**
     * @param string $httpHost
     * @return Route
     */
    public function setHttpHost($httpHost)
    {
        $this->httpHost = $httpHost;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param mixed $parameters
     * @return Route
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }
}