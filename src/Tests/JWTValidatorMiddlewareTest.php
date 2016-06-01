<?php

namespace Thruster\Component\HttpMiddlewares\Tests;

use Namshi\JOSE\SimpleJWS;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpMessage\Response;
use Thruster\Component\HttpMessage\ServerRequest;
use Thruster\Component\HttpMiddlewares\JWTValidatorMiddleware;

/**
 * Class JWTValidatorMiddlewareTest
 *
 * @package Thruster\Component\HttpMiddlewares\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class JWTValidatorMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testMiddleware()
    {
        $expected = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'admin' => true,
            'iat' => '1464708611'
        ];

        $jws  = new SimpleJWS(['alg' => 'RS256']);
        $jws->setPayload($expected);

        $jws->sign(openssl_pkey_get_private('file://' . __DIR__ . '/Fixtures/private.pem', 'tests'));

        $request = new ServerRequest('GET', '/');
        $request = $request->withAddedHeader(
            'Authorization',
            'Bearer ' . $jws->getTokenString()
        );

        $response = new Response();

        $modifier = new JWTValidatorMiddleware(
            openssl_pkey_get_public('file://' . __DIR__ . '/Fixtures/public.pem'),
            'RS256',
            false
        );

        $givenResponse = $modifier($request, $response, function ($request, $respone) use ($expected) {
            $this->assertSame($expected, $request->getAttribute('jwt'));

            return $respone;
        });

        $this->assertEquals($response, $givenResponse);
    }

    public function testModifierNotMatched()
    {
        $request  = new ServerRequest('GET', '/');
        $response = new Response(200);

        $callback = function (ServerRequestInterface $request, ResponseInterface $givenResponse) use ($response) {
            $this->assertEquals($response, $givenResponse);
        };

        $modifier = new JWTValidatorMiddleware('secret', 'RS256', false);
        $modifier($request, $response, $callback);
    }

    public function testMiddlewareInvalidToken()
    {
        $request = new ServerRequest('GET', '/');
        $request = $request->withAddedHeader(
            'Authorization',
            'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6Ikpv' .
            'aG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxoYZgeFONFh7HgQ'
        );

        $response = new Response();

        $invalidResponse = new Response(403);

        $callback = function (ServerRequestInterface $req, ResponseInterface $givenResponse) use ($invalidResponse) {
            $this->assertEquals($invalidResponse, $givenResponse);
        };

        $modifier = new JWTValidatorMiddleware('', 'RS256', false, $invalidResponse);
        $modifier($request, $response, $callback);
    }

    public function testMiddlewareInvalidHeader()
    {
        $request = new ServerRequest('GET', '/');
        $request = $request->withAddedHeader(
            'Authorization',
            'foobar'
        );

        $response = new Response();

        $invalidResponse = new Response(403);

        $callback = function (ServerRequestInterface $req, ResponseInterface $givenResponse) use ($invalidResponse) {
            $this->assertEquals($invalidResponse, $givenResponse);
        };

        $modifier = new JWTValidatorMiddleware('', 'RS256', false, $invalidResponse);
        $modifier($request, $response, $callback);
    }

    public function testMiddlewareAllRequest()
    {
        $request = new ServerRequest('GET', '/');

        $response = new Response();

        $invalidResponse = new Response(403);

        $callback = function (ServerRequestInterface $req, ResponseInterface $givenResponse) use ($invalidResponse) {
            $this->assertEquals($invalidResponse, $givenResponse);
        };

        $modifier = new JWTValidatorMiddleware('', 'RS256', true, $invalidResponse);
        $modifier($request, $response, $callback);
    }
}
