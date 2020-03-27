<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteCommand;
use Chimera\IdentifierGenerator;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\CreateOnly;
use Chimera\Routing\UriGenerator;
use Chimera\ServiceBus;
use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Chimera\Routing\Handler\CreateOnly
 */
final class CreateOnlyTest extends TestCase
{
    /**
     * @var ServiceBus&MockObject
     */
    private ServiceBus $bus;

    /**
     * @var MessageCreator&MockObject
     */
    private MessageCreator $creator;

    /**
     * @var UriGenerator&MockObject
     */
    private UriGenerator $uriGenerator;

    /**
     * @var IdentifierGenerator&MockObject
     */
    private IdentifierGenerator $idGenerator;

    private UuidInterface $id;

    /**
     * @before
     */
    public function createDependencies(): void
    {
        $this->bus          = $this->createMock(ServiceBus::class);
        $this->creator      = $this->createMock(MessageCreator::class);
        $this->uriGenerator = $this->createMock(UriGenerator::class);
        $this->idGenerator  = $this->createMock(IdentifierGenerator::class);
        $this->id           = Uuid::uuid4();
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

        $this->creator->expects(self::once())
                      ->method('create')
                      ->willReturn($command);

        $this->bus->expects(self::once())
                  ->method('handle')
                  ->with($command);

        $this->idGenerator->method('generate')
                          ->willReturn($this->id);

        $this->uriGenerator->expects(self::once())
                           ->method('generateRelativePath')
                           ->with($request->withAttribute(IdentifierGenerator::class, $this->id), 'info')
                           ->willReturn('/testing/' . $this->id);

        $response = $this->handleRequest($request);

        self::assertNotInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        self::assertSame('/testing/' . $this->id, $response->getHeaderLine('Location'));
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
        $request = (new ServerRequest())->withAttribute(IdentifierGenerator::class, $this->id);
        $command = (object) ['a' => 'b'];

        $this->creator->expects(self::once())
                      ->method('create')
                      ->willReturn($command);

        $this->bus->expects(self::once())
                  ->method('handle')
                  ->with($command);

        $this->idGenerator->method('generate')
                          ->willReturn(Uuid::uuid4());

        $this->uriGenerator->expects(self::once())
                           ->method('generateRelativePath')
                           ->with($request, 'info')
                           ->willReturn('/testing/' . $this->id);

        $response = $this->handleRequest($request);

        self::assertNotInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        self::assertSame('/testing/' . $this->id, $response->getHeaderLine('Location'));
    }

    private function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $handler = new CreateOnly(
            new ExecuteCommand($this->bus, $this->creator, 'command'),
            new ResponseFactory(),
            'info',
            $this->uriGenerator,
            $this->idGenerator,
            StatusCodeInterface::STATUS_CREATED
        );

        return $handler->handle($request);
    }
}
