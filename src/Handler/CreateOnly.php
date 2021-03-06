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
    private ExecuteCommand $action;
    private ResponseFactoryInterface $responseFactory;
    private string $routeName;
    private UriGenerator $uriGenerator;
    private int $statusCode;
    private IdentifierGenerator $identifierGenerator;

    public function __construct(
        ExecuteCommand $action,
        ResponseFactoryInterface $responseFactory,
        string $routeName,
        UriGenerator $uriGenerator,
        IdentifierGenerator $identifierGenerator,
        int $statusCode
    ) {
        $this->action              = $action;
        $this->responseFactory     = $responseFactory;
        $this->routeName           = $routeName;
        $this->uriGenerator        = $uriGenerator;
        $this->identifierGenerator = $identifierGenerator;
        $this->statusCode          = $statusCode;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(
            IdentifierGenerator::class,
            $request->getAttribute(IdentifierGenerator::class, $this->identifierGenerator->generate())
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
