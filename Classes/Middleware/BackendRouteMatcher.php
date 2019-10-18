<?php

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendRouteMatcher implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = GeneralUtility::makeInstance(Router::class);
        $route = $router->matchRequest($request);
        $request = $request->withAttribute('route', $route);
        $request = $request->withAttribute('target', $route->getOption('target'));
        $request = $request->withAttribute('public', $route->getOption('access') === 'public');

        return $handler->handle($request);
    }
}
