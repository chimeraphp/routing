<?php
declare(strict_types=1);

namespace Chimera\Routing\Handler;

use Chimera\ExecuteCommand;
use Chimera\ExecuteQuery;
use Chimera\Routing\HttpRequest;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Executes a command and then a query, returning its result in an unformatted
 * response.
 */
final class ExecuteAndFetch implements RequestHandlerInterface
{
    /**
     * @var ExecuteCommand
     */
    private $writeAction;

    /**
     * @var ExecuteQuery
     */
    private $readAction;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(
        ExecuteCommand $writeAction,
        ExecuteQuery $readAction,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->writeAction     = $writeAction;
        $this->readAction      = $readAction;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $input = new HttpRequest($request);

        $this->writeAction->execute($input);

        return new UnformattedResponse(
            $this->responseFactory->createResponse(),
            $this->readAction->fetch($input),
            [ExecuteQuery::class => $this->readAction->getQuery()]
        );
    }
}
