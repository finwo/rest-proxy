<?php

// Include autoloader
include __DIR__ . '/../config/autoload.php';

// Uses
use Finwo\Framework\Application;

// Start the application
$app = new Application();
$app->launch();