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
    private ExecuteCommand $action;
    private ResponseFactoryInterface $responseFactory;
    private int $statusCode;

    public function __construct(ExecuteCommand $action, ResponseFactoryInterface $responseFactory, int $statusCode)
    {
        $this->action          = $action;
        $this->responseFactory = $responseFactory;
        $this->statusCode      = $statusCode;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->action->execute(new HttpRequest($request));

        return $this->responseFactory->createResponse($this->statusCode);
    }
}
