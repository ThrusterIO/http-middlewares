<?php

namespace Thruster\Component\HttpMiddlewares;

use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CorsMiddleware
 *
 * @package Thruster\Component\HttpMiddlewares
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class CorsMiddleware
{
    /**
     * @var AnalyzerInterface
     */
    private $analyzer;

    /**
     * @var SettingsStrategyInterface
     */
    private $settings;

    public function __construct(SettingsStrategyInterface $settings = null, LoggerInterface $logger = null)
    {
        $this->settings = $settings ?? new Settings();

        $this->analyzer = Analyzer::instance($this->settings);

        if (null !== $logger) {
            $this->analyzer->setLogger($logger);
        }
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $cors = $this->analyzer->analyze($request);

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $response->withStatus(403);
            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $next($request, $response);
            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                foreach ($cors->getResponseHeaders() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $response->withStatus(200);
            default:
                foreach ($cors->getResponseHeaders() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }

                return $next($request, $response);
        }
    }

    /**
     * @return SettingsStrategyInterface
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param AnalyzerInterface $analyzer
     *
     * @return $this
     */
    public function setAnalyzer($analyzer)
    {
        $this->analyzer = $analyzer;

        return $this;
    }
}
