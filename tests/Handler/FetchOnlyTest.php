<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteQuery;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\FetchOnly;
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

#[PHPUnit\CoversClass(FetchOnly::class)]
#[PHPUnit\UsesClass(HttpRequest::class)]
final class FetchOnlyTest extends TestCase
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
    public function handleShouldExecuteTheQueryAndReturnItsContent(): void
    {
        $handler = new FetchOnly(
            new ExecuteQuery($this->bus, $this->creator, stdClass::class),
            new ResponseFactory(),
        );

        $query = (object) ['a' => 'b'];

        $this->creator->expects(self::once())
                      ->method('create')
                      ->willReturn($query);

        $this->bus->expects(self::once())
                  ->method('handle')
                  ->with($query)
                  ->willReturn('result');

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame([ExecuteQuery::class => stdClass::class], $response->getAttributes());
        self::assertSame('result', $response->getUnformattedContent());
    }
}
