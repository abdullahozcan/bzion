<?php

require_once("bzion-load.php");

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

$locator = new FileLocator(array(__DIR__));

$request = Request::createFromGlobals();
$requestContext = new RequestContext();
$requestContext->fromRequest($request);

Service::setRequest($request);

// Disable caching while on the DEVELOPMENT environment
$cacheDir = DEVELOPMENT ? null : __DIR__.'/cache';

$router = new Router(
    new YamlFileLoader($locator),
    'routes.yml',
    array('cache_dir' => $cacheDir),
    $requestContext
);

Service::setGenerator($router->getGenerator());

$parameters = $router->matchRequest($request);

$con = Controller::getController($parameters);
$con->callAction();
