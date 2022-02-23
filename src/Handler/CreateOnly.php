<?php
declare(strict_types=1);

namespace Chimera\Routing\Handler;

use Chimera\ExecuteCommand;
use Chimera\IdentifierGenerator;
use Chimera\Routing\HttpRequest;
use Chimera\Routing\UriGenerator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Generates an identifier and executes (or schedule) a command, returning
 * an empty response with a link to the new resource
 */
final class CreateOnly implements RequestHandlerInterface
{
    public function __construct(
        private readonly ExecuteCommand $action,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $routeName,
        private readonly UriGenerator $uriGenerator,
        private readonly IdentifierGenerator $identifierGenerator,
        private readonly int $statusCode,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(
            IdentifierGenerator::class,
            $request->getAttribute(IdentifierGenerator::class, $this->identifierGenerator->generate()),
        );

        $this->action->execute(new HttpRequest($request));

        return $this->generateResponse($request);
    }

    private function generateResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response    = $this->responseFactory->createResponse($this->statusCode);
        $resourceUri = $this->uriGenerator->generateRelativePath($request, $this->routeName);

        return $response->withAddedHeader('Location', $resourceUri);
    }
}
