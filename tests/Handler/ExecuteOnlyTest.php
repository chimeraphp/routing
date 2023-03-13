<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteCommand;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\ExecuteOnly;
use Chimera\Routing\HttpRequest;
use Chimera\ServiceBus;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/** @coversDefaultClass \Chimera\Routing\Handler\ExecuteOnly */
#[PHPUnit\CoversClass(ExecuteOnly::class)]
#[PHPUnit\UsesClass(HttpRequest::class)]
final class ExecuteOnlyTest extends TestCase
{
    private ServiceBus&MockObject $bus;
    private MessageCreator&MockObject $creator;

    #[PHPUnit\Before]
    public function createDependencies(): void
    {
        $this->bus     = $this->createMock(ServiceBus::class);
        $this->creator = $this->createMock(MessageCreator::class);
    }

    #[PHPUnit\Test]
    public function handleShouldExecuteTheCommandAndReturnAnEmptyResponse(): void
    {
        $handler = new ExecuteOnly(
            new ExecuteCommand($this->bus, $this->creator, stdClass::class),
            new ResponseFactory(),
            StatusCodeInterface::STATUS_NO_CONTENT,
        );

        $command = (object) ['a' => 'b'];

        $this->creator->expects(self::once())
                      ->method('create')
                      ->willReturn($command);

        $this->bus->expects(self::once())
                  ->method('handle')
                  ->with($command);

        $response = $handler->handle(new ServerRequest());

        self::assertNotInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());
    }
}
