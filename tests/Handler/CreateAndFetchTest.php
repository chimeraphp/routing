<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteCommand;
use Chimera\ExecuteQuery;
use Chimera\IdentifierGenerator;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\CreateAndFetch;
use Chimera\Routing\UriGenerator;
use Chimera\ServiceBus;
use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Chimera\Routing\Handler\CreateAndFetch
 */
final class CreateAndFetchTest extends TestCase
{
    /**
     * @var ServiceBus|MockObject
     */
    private $bus;

    /**
     * @var MessageCreator|MockObject
     */
    private $creator;

    /**
     * @var UriGenerator|MockObject
     */
    private $uriGenerator;

    /**
     * @var IdentifierGenerator|MockObject
     */
    private $idGenerator;

    /**
     * @before
     */
    public function createDependencies(): void
    {
        $this->bus          = $this->createMock(ServiceBus::class);
        $this->creator      = $this->createMock(MessageCreator::class);
        $this->uriGenerator = $this->createMock(UriGenerator::class);
        $this->idGenerator  = $this->createMock(IdentifierGenerator::class);
    }

    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::handle()
     * @covers ::generateResponse()
     *
     * @uses \Chimera\Routing\HttpRequest
     */
    public function handleShouldExecuteTheCommandAndReturnAnEmptyResponse(): void
    {
        $request = new ServerRequest();
        $command = (object) ['a' => 'b'];
        $query   = (object) ['c' => 'd'];

        $this->creator->expects(self::exactly(2))
                      ->method('create')
                      ->willReturn($command, $query);

        $this->bus->expects(self::exactly(2))
                  ->method('handle')
                  ->withConsecutive([$command], [$query])
                  ->willReturn(null, 'result');

        $this->idGenerator->method('generate')
                          ->willReturn(1);

        $this->uriGenerator->expects(self::once())
                           ->method('generateRelativePath')
                           ->with($request->withAttribute(IdentifierGenerator::class, 1), 'info')
                           ->willReturn('/testing/1');

        /** @var ResponseInterface|UnformattedResponse $response */
        $response = $this->handleRequest($request);

        self::assertInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        self::assertSame('/testing/1', $response->getHeaderLine('Location'));
        self::assertSame([ExecuteQuery::class => 'query'], $response->getAttributes());
        self::assertSame('result', $response->getUnformattedContent());
    }

    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::handle()
     * @covers ::generateResponse()
     *
     * @uses \Chimera\Routing\HttpRequest
     */
    public function handleShouldPreserveTheRequestGeneratedIdIfAlreadyPresent(): void
    {
        $request = (new ServerRequest())->withAttribute(IdentifierGenerator::class, 2);
        $command = (object) ['a' => 'b'];
        $query   = (object) ['c' => 'd'];

        $this->creator->expects(self::exactly(2))
                      ->method('create')
                      ->willReturn($command, $query);

        $this->bus->expects(self::exactly(2))
                  ->method('handle')
                  ->withConsecutive([$command], [$query])
                  ->willReturn(null, 'result');

        $this->idGenerator->method('generate')
                          ->willReturn(1);

        $this->uriGenerator->expects(self::once())
                           ->method('generateRelativePath')
                           ->with($request, 'info')
                           ->willReturn('/testing/2');

        /** @var ResponseInterface|UnformattedResponse $response */
        $response = $this->handleRequest($request);

        self::assertInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        self::assertSame('/testing/2', $response->getHeaderLine('Location'));
        self::assertSame([ExecuteQuery::class => 'query'], $response->getAttributes());
        self::assertSame('result', $response->getUnformattedContent());
    }

    private function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $handler = new CreateAndFetch(
            new ExecuteCommand($this->bus, $this->creator, 'command'),
            new ExecuteQuery($this->bus, $this->creator, 'query'),
            new ResponseFactory(),
            'info',
            $this->uriGenerator,
            $this->idGenerator
        );

        return $handler->handle($request);
    }
}
