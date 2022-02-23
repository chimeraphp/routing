<?php
declare(strict_types=1);

namespace Chimera\Routing\Handler;

use Chimera\ExecuteCommand;
use Chimera\Routing\HttpRequest;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Executes or schedule a command, returning a response with empty body.
 */
final class ExecuteOnly implements RequestHandlerInterface
{
    public function __construct(
        private readonly ExecuteCommand $action,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $statusCode,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->action->execute(new HttpRequest($request));

        return $this->responseFactory->createResponse($this->statusCode);
    }
}
