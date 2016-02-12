<?php

namespace Thruster\Component\HttpMiddlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class VendorSpecificJsonHeaderMiddleware
 *
 * @package Thruster\Component\HttpMiddlewares
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class VendorSpecificJsonHeaderMiddleware
{
    /**
     * @var string
     */
    private $contentType;

    public function __construct(string $contentType)
    {
        $this->contentType = $contentType;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (false === strpos($response->getHeaderLine('Content-Type'), 'application/json')) {
            return $next($request, $response);
        }

        return $next($request, $response->withHeader('Content-Type', $this->contentType));
    }
}
