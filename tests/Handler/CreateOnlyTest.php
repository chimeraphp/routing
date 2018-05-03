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
use Middlewares\Utils\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Chimera\Routing\Handler\CreateOnly
 */
final class CreateOnlyTest extends TestCase
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
        $handler = new CreateOnly(
            new ExecuteCommand($this->bus, $this->creator, 'command'),
            [Factory::class, 'createResponse'],
            'info',
            $this->uriGenerator,
            $this->idGenerator,
            StatusCodeInterface::STATUS_CREATED
        );

        $request = new ServerRequest();
        $command = (object) ['a' => 'b'];

        $this->creator->expects(self::once())
                      ->method('create')
                      ->willReturn($command);

        $this->bus->expects(self::once())
                  ->method('handle')
                  ->with($command);

        $this->idGenerator->method('generate')
                          ->willReturn(1);

        $this->uriGenerator->expects(self::once())
                           ->method('generateRelativePath')
                           ->with($request->withAttribute(IdentifierGenerator::class, 1), 'info')
                           ->willReturn('/testing/1');

        $response = $handler->handle($request);

        self::assertNotInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        self::assertSame('/testing/1', $response->getHeaderLine('Location'));
    }
}
