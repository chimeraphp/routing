<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteCommand;
use Chimera\IdentifierGenerator;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\CreateOnly;
use Chimera\Routing\HttpRequest;
use Chimera\Routing\UriGenerator;
use Chimera\ServiceBus;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;

#[PHPUnit\CoversClass(CreateOnly::class)]
#[PHPUnit\UsesClass(HttpRequest::class)]
final class CreateOnlyTest extends TestCase
{
    private ServiceBus&MockObject $bus;
    private MessageCreator&MockObject $creator;
    private UriGenerator&MockObject $uriGenerator;
    private IdentifierGenerator&MockObject $idGenerator;
    private UuidInterface $id;

    #[PHPUnit\Before]
    public function createDependencies(): void
    {
        $this->bus          = $this->createMock(ServiceBus::class);
        $this->creator      = $this->createMock(MessageCreator::class);
        $this->uriGenerator = $this->createMock(UriGenerator::class);
        $this->idGenerator  = $this->createMock(IdentifierGenerator::class);
        $this->id           = Uuid::uuid4();
    }

    #[PHPUnit\Test]
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

    #[PHPUnit\Test]
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
            new ExecuteCommand($this->bus, $this->creator, stdClass::class),
            new ResponseFactory(),
            'info',
            $this->uriGenerator,
            $this->idGenerator,
            StatusCodeInterface::STATUS_CREATED,
        );

        return $handler->handle($request);
    }
}
