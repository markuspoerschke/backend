<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initializes the backend user authentication object (BE_USER) and the global LANG object.
 *
 * @internal
 */
class BackendUserAuthenticator implements MiddlewareInterface
{
    /**
     * List of requests that don't need a valid BE user
     *
     * @deprecated Will be removed in TYPO3 v10. Use "access" option to mark routes as public instead.
     *
     * @var array
     */
    protected $publicRoutes = [
        '/login',
        '/login/frame',
        '/ajax/login',
        '/ajax/logout',
        '/ajax/login/refresh',
        '/ajax/login/timedout',
        '/ajax/rsa/publickey',
        '/ajax/core/requirejs',
    ];

    /**
     * Calls the bootstrap process to set up $GLOBALS['BE_USER'] AND $GLOBALS['LANG']
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Bootstrap::initializeBackendUser();
        // @todo: once this logic is in this method, the redirect URL should be handled as response here
        Bootstrap::initializeBackendAuthentication($this->isLoggedInBackendUserRequired($request));
        Bootstrap::initializeLanguageObject();
        // Register the backend user as aspect
        $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), $GLOBALS['BE_USER']);

        return $handler->handle($request);
    }

    /**
     * Check if the user is required for the request
     * If we're trying to do a login or an ajax login, don't require a user
     *
     * ATTENTION:
     * The name of this method indicates that true will be returned, if a logged in user is required.
     * BUT, this method will check for public routes. That means:
     * - true is returned, if the user does not need to be logged in and the route can be accessed publicly
     * - false is returned, if a logged in user is required and the route is not accessible publicly
     *
     * @deprecated Will be removed in TYPO3 v10.
     *
     * @param ServerRequestInterface $request
     * @return bool whether the request can proceed without a login required
     */
    protected function isLoggedInBackendUserRequired(ServerRequestInterface $request): bool
    {
        if ($request->getAttribute('public', false)) {
            return true;
        }

        // fallback in case, that this class is overwritten using XCLASS
        $routePath = $request->getAttribute('routePath', '/login');
        if (in_array($routePath, $this->publicRoutes, true)) {
            trigger_error('The property $publicRoutes will be removed in TYPO3 v10. Use the "access" option instead to mark routes as public.', E_USER_DEPRECATED);
            return true;
        }

        return false;
    }

    /**
     * Register the backend user as aspect
     *
     * @param Context $context
     * @param BackendUserAuthentication $user
     */
    protected function setBackendUserAspect(Context $context, BackendUserAuthentication $user)
    {
        $context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user->workspace));
    }
}
