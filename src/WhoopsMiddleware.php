<?php

namespace Woody\Middleware\Whoops;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Woody\Http\Server\Middleware\MiddlewareInterface;

/**
 * Class WhoopsMiddleware
 *
 * @package Woody\Middleware\Whoops
 */
class WhoopsMiddleware implements MiddlewareInterface
{

    /**
     * @var Run|null
     */
    protected $whoops;

    /**
     * @var bool Whether catch errors or not
     */
    protected $catchErrors = true;

    /**
     * Set the whoops instance.
     */
    public function __construct()
    {
    }

    /**
     * @param bool $debug
     *
     * @return bool
     */
    public function isEnabled(bool $debug): bool
    {
        return $debug && class_exists('\Whoops\Run');
    }

    /**
     * Whether catch errors or not.
     */
    public function catchErrors(bool $catchErrors = true): self
    {
        $this->catchErrors = $catchErrors;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $whoops = $this->whoops ?: $this->getWhoopsInstance($request);

        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);

        //Catch errors means register whoops globally
        if ($this->catchErrors) {
            $whoops->register();
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $t) {
            $body = $whoops->handleException($t);
            $response = self::createResponse($body, $whoops, $t);
        }

        if ($this->catchErrors) {
            $whoops->unregister();
        }

        return $response;
    }

    /**
     * Returns the whoops instance or create one.
     */
    protected function getWhoopsInstance(ServerRequestInterface $request): Run
    {
        $container = new WhoopsHandlerContainer();

        $whoops = new Run();
        $handler = $container->get($request->getHeaderLine('Accept'));
        $whoops->pushHandler($handler);

        return $whoops;
    }

    /**
     * Returns the content-type for the whoops instance
     */
    protected static function createResponse(string $body, Run $whoops, \Throwable $t): ResponseInterface
    {
        $statusCode = ($t instanceof HttpException ? $t->getStatusCode() : 500);

        $response = new Response($statusCode, [], $body);

        if (1 !== count($whoops->getHandlers())) {
            return $response;
        }

        $handler = current($whoops->getHandlers());

        if ($handler instanceof PrettyPageHandler) {
            return $response->withHeader('Content-Type', 'text/html');
        }

        if ($handler instanceof JsonResponseHandler) {
            return $response->withHeader('Content-Type', 'application/json');
        }

        if ($handler instanceof XmlResponseHandler) {
            return $response->withHeader('Content-Type', 'text/xml');
        }

        if ($handler instanceof PlainTextHandler) {
            return $response->withHeader('Content-Type', 'text/plain');
        }

        return $response;
    }
}
