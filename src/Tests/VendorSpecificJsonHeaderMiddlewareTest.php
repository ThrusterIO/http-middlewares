<?php

namespace Thruster\Component\HttpMiddlewares\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpMessage\Request;
use Thruster\Component\HttpMessage\Response;
use Thruster\Component\HttpMessage\ServerRequest;
use Thruster\Component\HttpMiddlewares\VendorSpecificJsonHeaderMiddleware;

/**
 * Class VendorSpecificJsonHeaderMiddlewareTest
 *
 * @package Thruster\Component\HttpMiddlewares\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class VendorSpecificJsonHeaderMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testMiddleware()
    {
        $request = new ServerRequest('GET', '/');
        $response = new Response(200, ['Content-Type' => ['application/json']]);

        $expected = 'foo/bar';

        $callback = function (ServerRequestInterface $request, ResponseInterface $response) use ($expected) {
            $this->assertSame($expected, $response->getHeaderLine('Content-Type'));
        };

        $modifier = new VendorSpecificJsonHeaderMiddleware($expected);
        $modifier($request, $response, $callback);

    }

    public function testModifierNotMatched()
    {
        $request = new ServerRequest('GET', '/');
        $response = new Response(200, ['Content-Type' => ['foo/bar']]);

        $expected = 'foo/bar';

        $callback = function (ServerRequestInterface $request, ResponseInterface $response) use ($expected) {
            $this->assertSame($expected, $response->getHeaderLine('Content-Type'));
        };

        $modifier = new VendorSpecificJsonHeaderMiddleware('bar/foo');
        $modifier($request, $response, $callback);
    }
}
