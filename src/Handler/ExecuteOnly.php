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
    /**
     * @var ExecuteCommand
     */
    private $action;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var int
     */
    private $statusCode;

    public function __construct(ExecuteCommand $action, ResponseFactoryInterface $responseFactory, int $statusCode)
    {
        $this->action          = $action;
        $this->responseFactory = $responseFactory;
        $this->statusCode      = $statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->action->execute(new HttpRequest($request));

        return $this->responseFactory->createResponse($this->statusCode);
    }
}
