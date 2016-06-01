<?php

namespace Thruster\Component\HttpMiddlewares;

use Namshi\JOSE\SimpleJWS;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\HttpMessage\Response;

/**
 * Class JWTValidatorMiddleware
 *
 * @package Thruster\Component\HttpMiddlewares
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class JWTValidatorMiddleware
{
    /**
     * @var resource
     */
    private $publicKey;

    /**
     * @var string
     */
    private $encoder;

    /**
     * @var ResponseInterface
     */
    private $invalidResponse;

    /**
     * @var bool
     */
    private $allRequests;

    public function __construct(
        $publicKey,
        string $encoder = 'RS256',
        bool $allRequests = false,
        ResponseInterface $invalidResponse = null
    ) {
        $this->publicKey   = $publicKey;
        $this->encoder     = $encoder;
        $this->allRequests = $allRequests;

        if (null !== $invalidResponse) {
            $this->invalidResponse = $invalidResponse;
        } else {
            $this->invalidResponse = new Response(403);
        }
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (false === $request->hasHeader('Authorization')) {
            if (false === $this->allRequests) {
                return $next($request, $response);
            }

            return $this->invalidResponse;
        }

        $token = $request->getHeaderLine('Authorization');
        if (false === strpos($token, 'Bearer ')) {
            return $this->invalidResponse;
        }

        $token = substr($token, 7);

        /** @var SimpleJWS $jws */
        $jws = SimpleJWS::load($token, false);

        if (false === $jws->isValid($this->publicKey, $this->encoder)) {
            return $this->invalidResponse;
        }

        return $next($request->withAttribute('jwt', $jws->getPayload()), $response);
    }
}
