<?php
declare(strict_types=1);

namespace Chimera\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteParamsExtraction implements MiddlewareInterface
{
    public function __construct(private readonly RouteParamsExtractor $extractor)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request->withAttribute(self::class, $this->extractor->getParams($request)),
        );
    }
}
