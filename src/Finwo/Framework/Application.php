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

        return 'tadaa';
    }
}