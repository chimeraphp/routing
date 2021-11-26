<?php
declare(strict_types=1);

namespace Chimera\Routing\Handler;

use Chimera\ExecuteQuery;
use Chimera\Routing\HttpRequest;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Executes a query, returning an unformatted response with its result.
 */
final class FetchOnly implements RequestHandlerInterface
{
    public function __construct(private ExecuteQuery $action, private ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new UnformattedResponse(
            $this->responseFactory->createResponse(),
            $this->action->fetch(new HttpRequest($request)),
            [ExecuteQuery::class => $this->action->getQuery()],
        );
    }
}
