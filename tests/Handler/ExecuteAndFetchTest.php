<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests\Handler;

use Chimera\ExecuteCommand;
use Chimera\ExecuteQuery;
use Chimera\MessageCreator;
use Chimera\Routing\Handler\ExecuteAndFetch;
use Chimera\ServiceBus;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/** @coversDefaultClass \Chimera\Routing\Handler\ExecuteAndFetch */
final class ExecuteAndFetchTest extends TestCase
{
    // phpcs:disable PSR12.Operators.OperatorSpacing.NoSpaceBefore -- PHPCS isn't ready for PHP 8.1 features yet
    // phpcs:disable PSR12.Operators.OperatorSpacing.NoSpaceAfter
    private ServiceBus&MockObject $bus;
    private MessageCreator&MockObject $creator;
    // phpcs:enable PSR12.Operators.OperatorSpacing.NoSpaceBefore
    // phpcs:enable PSR12.Operators.OperatorSpacing.NoSpaceAfter

    /** @before */
    public function createDependencies(): void
    {
        $this->bus     = $this->createMock(ServiceBus::class);
        $this->creator = $this->createMock(MessageCreator::class);
    }

    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::handle()
     *
     * @uses \Chimera\Routing\HttpRequest
     */
    public function handleShouldExecuteTheCommandAndReturnAnEmptyResponse(): void
    {
        $handler = new ExecuteAndFetch(
            new ExecuteCommand($this->bus, $this->creator, stdClass::class),
            new ExecuteQuery($this->bus, $this->creator, stdClass::class),
            new ResponseFactory(),
        );

        $command = (object) ['a' => 'b'];
        $query   = (object) ['c' => 'd'];

        $this->creator->expects(self::exactly(2))
                      ->method('create')
                      ->willReturn($command, $query);

        $this->bus->expects(self::exactly(2))
                  ->method('handle')
                  ->withConsecutive([$command], [$query])
                  ->willReturn(null, 'result');

        $response = $handler->handle(new ServerRequest());

        self::assertInstanceOf(UnformattedResponse::class, $response);
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame([ExecuteQuery::class => stdClass::class], $response->getAttributes());
        self::assertSame('result', $response->getUnformattedContent());
    }
}
