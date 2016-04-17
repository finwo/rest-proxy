<?php

// Act like our parent folder is the document root
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../');

// Include composer's autoloader
include __DIR__ . '/../vendor/autoload.php';