<?php

namespace Finwo\Framework;

use Finwo\PropertyAccessor\PropertyAccessor;
use Invoker\Invoker;

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

        // Construct a callable
        $controller = '';
        $method     = '';
        if (!is_null($route)) {
            // Fetch callable for the route
            list($controller, $method) = $this->routeToCallable($route);
        }

        // Create the controller object
        $controller = new $controller($this->container);

        // Call the function
        $invoker = new Invoker();
        $answer = $invoker->call(array($controller, $method), array_merge(
            $route->getParameters(),
            array(
                'route'     => $route,
                'container' => $this->container,
                'query'     => $route->getParameters()
            )
        ));

        switch($route->getType()) {
            case 'rest':
                // Must transform data

                // Check how to transform
                $format = null;
                if (isset($route->getParameters()['format'])) {
                    $format = $route->getParameters()['format'];
                } else {
                    preg_match('/\.(?<format>[a-z]+)\?/i', $route->getRequestUri(), $matches);
                    if (isset($matches['format'])) {
                        $format = $matches['format'];
                    }
                }
                if (is_null($format)) {
                    throw new \Exception('Target format not given');
                }

                switch(strtolower($format)) {
                    case 'json':
                        die(json_encode($answer));
                    case 'xml':
                        $transformer = new XmlTransformer();
                        die($transformer->objToXML($answer));
                    default:
                        throw new \Exception('Target format not supported');
                        die();
                }

                die('transform');
            default:
                // Must return a view
                die('Not implemented yet');
                break;
        }
    }

    protected function routeToCallable( Route $route )
    {
        @list( $namespace, $controller, $method ) = explode(':', $route->getController());

        // This might introduce bugs we don't want
//        // Add prefix to the uri if used
        $parsedPath = $route->getParsedUri()['path'];
//        if (strlen($prefix=$route->getPrefix())) {
//            $parsedPath = $prefix . $parsedPath;
//        }

        // Pre-fetch uri, we might need to work with it
        $uri = explode('/', trim($parsedPath, '/'));

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

        // Fix method if needed
        if( is_null($method) || !strlen($method) || !method_exists($controller, $method) ) {
            $parts = $uri;

            // Check if appending 'action' does the trick
            if (method_exists($controller, $method . 'Action')) {
                $method .= 'Action';
            } else {
                // Make sure the rest of the code catches this
                $method = null;
            }

            if (is_null($method) || !strlen($method)) {
                while(count($parts)) {
                    if(method_exists($controller, ($camelized = $this->camelizedArray($parts)) . 'Action')) {
                        $method = $camelized . 'Action';
                        break;
                    }
                    array_pop($parts);
                }

            }

            // Try request method
            if (is_null($method) || !strlen($method)) {
                if (method_exists($controller, ($m=strtolower($route->getRequestMethod())) . 'Action')) {
                    $method = $m . 'Action';
                }
            }

            // Try index method
            if (is_null($method) || !strlen($method)) {
                if (method_exists($controller, 'indexAction')) {
                    $method = 'indexAction';
                }
            }

            // Throw exception, we don't have a function
            if (is_null($method) || !strlen($method)) {
                throw new \Exception('No method for the route found');
            }
        }

        // Fetch how to call the method (parameters etc)
        $parameters = array();

        // We're here, that means we have all the requirements
        $chosenController = new $controller($this->container);

        return array($controller,$method);
    }

    protected function camelizedArray($input) {
        $output = lcfirst(array_shift($input));

        foreach($input as $str) {
            $output .= ucfirst($str);
        }

        return $output;
    }
}