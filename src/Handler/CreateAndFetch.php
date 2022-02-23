<?php
declare(strict_types=1);

namespace Chimera\Routing\Handler;

use Chimera\ExecuteCommand;
use Chimera\ExecuteQuery;
use Chimera\IdentifierGenerator;
use Chimera\Routing\HttpRequest;
use Chimera\Routing\UriGenerator;
use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Generates an identifier, executes a command, and then a query. Returns the
 * query result in an unformatted response with a link to the new resource
 */
final class CreateAndFetch implements RequestHandlerInterface
{
    public function __construct(
        private ExecuteCommand $writeAction,
        private ExecuteQuery $readAction,
        private ResponseFactoryInterface $responseFactory,
        private string $routeName,
        private UriGenerator $uriGenerator,
        private IdentifierGenerator $identifierGenerator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(
            IdentifierGenerator::class,
            $request->getAttribute(IdentifierGenerator::class, $this->identifierGenerator->generate()),
        );

        $input = new HttpRequest($request);

        $this->writeAction->execute($input);

        return new UnformattedResponse(
            $this->generateResponse($request),
            $this->readAction->fetch($input),
            [ExecuteQuery::class => $this->readAction->query],
        );
    }

    private function generateResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response    = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_CREATED);
        $resourceUri = $this->uriGenerator->generateRelativePath($request, $this->routeName);

        return $response->withAddedHeader('Location', $resourceUri);
    }
}
