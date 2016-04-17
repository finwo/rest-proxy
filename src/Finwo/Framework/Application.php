<?php

namespace Finwo\Framework;

use Finwo\PropertyAccessor\PropertyAccessor;

class Application
{
    /**
     * @var ParameterBag
     */
    protected $container;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if(is_null($this->propertyAccessor)) {
            $this->propertyAccessor = new PropertyAccessor();
        }
        return $this->propertyAccessor;
    }

    public function __construct( ParameterBag $container = null )
    {
        // Don't do anything if we're an actual application
        if (!is_null($container)) {
            $this->container = $container;
            return;
        }

        // Load basic container
        $this->container = new ParameterBag(array(

            // Relevant about the application
            'document_root' => $_SERVER['DOCUMENT_ROOT'],

            // Relevant about the current request
            'method' => $_SERVER['REQUEST_METHOD']
        ));

        // Very useful extensions
        $accessor = $this->getPropertyAccessor();
        $loader = new \Spyc();

        // Empty array, because we want to be fancy later on
        $config = array();

        // Load configuration files
        $files = glob($this->container->get('document_root') . '/config/*.yml');
        foreach ($files as $file) {
            $accessor->mergeArrays(
                $config,
                $loader->loadFile($file)
            );
        }
        $this->container->set('config', $config);

        // Transform routes into route objects, for easy usage
        $routes = array();
        foreach ($this->container->get('config.routes') as $name => $route) {
            $routes[] = $route = new Route($name, $route);
            $route->map( array_merge($_SERVER, $_REQUEST) );

        }
        $this->container->set('config.routes', $routes);

        // Check if we can do something useful with the application
        $app = $this->getApplicationObject( $this->container->get('config.application') );
        if ($app === false) {
            throw new \Exception('Application does not exist!');
        }

        // Save the app for later use
        $this->app = $app;
    }

    protected function getApplicationObject( $name )
    {
        // Try name directly
        if (class_exists($name)) {
            return new $name( $this->container );
        }

        // Try name with ending "application"
        $tryname = $name . '\\Application';
        if (class_exists($tryname)) {
            return new $tryname( $this->container );
        }

        // Try name with double ending
        $tryname = explode('\\', $name);
        $last = array_pop($tryname);
        array_push($tryname, $last);
        array_push($tryname, $last);
        $tryname = implode('\\', $tryname);

        if (class_exists($tryname)) {
            return new $tryname( $this->container );
        }

        return false;
    }

    public function launch()
    {
        // If we have a child, launch that instead
        if( $this->app instanceof Application ) {
            return $this->app->launch();
        }

        // Check if we match any known routes
        $routes = $this->container->get('config.routes');
        /** @var Route $route */
        $route = @array_shift(array_filter($this->container->get('config.routes'), function(Route $route) {
            return $route->match();
        }));

        // Start known route if possible
        if (!is_null($route)) {

            // Fetch the controller
            $callable = $this->routeToCallable($route);

            var_dump($route, $callable);
            die();

            // Search for the appropriate controller

        }

        var_dump($route);
        die(get_class($this));
    }

    protected function routeToCallable( Route $route )
    {
        @list( $namespace, $controller, $method ) = explode(':', $route->getController());

        // Pre-fetch uri, we might need to work with it
        $uri = explode('/', trim($route->getRequestUri(), '/'));

        // Auto-detect namespace if needed
        if (is_null($namespace) || !strlen($namespace)) {
            $ownClass = explode('\\', get_class($this));
            array_pop($ownClass);
            $namespace = implode('\\', $ownClass);
        }

        // Add "Controller" to the namespace
        $namespace .= '\\Controller';

        // Check if the controller exists
        if(!class_exists(sprintf("%s\\%sController", $namespace, $controller))) {

            // Try to fetch from uri
            if(count($uri) && class_exists(sprintf("%s\\%sController", $namespace, ucfirst($uri[0])))) {
                $controller = ucfirst(array_shift($uri));
            }

            // Try the defaultcontroller
            if(!strlen($controller) && class_exists(sprintf("%s\\DefaultController", $namespace))) {
                $controller = 'Default';
            }

            if(!strlen($controller)) {
                // Too bad, no controller found
                throw new \Exception('No proper controller found');
            }
        }

        // Merge fields into full controller class name
        $controller = sprintf("%s\\%sController", $namespace, $controller);

        // Fetch proper method

        // But first, debugging
        if(method_exists($controller, 'indexAction')) {
            die('VICTORY!!');
        } else {
            die('FAILURE');
        }

        var_dump($namespace, $controller, $method);
        var_dump($actualController);
        return null;
    }
}