<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests;

use Chimera\Routing\MissingRouteDispatching;
use Chimera\Routing\NoRouteMatched;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Chimera\Routing\MissingRouteDispatching
 */
final class MissingRouteDispatchingTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::process
     * @covers \Chimera\Routing\NoRouteMatched::fromRequest
     */
    public function processShouldThrowAnExceptionWithRequestInformation(): void
    {
        $middleware = new MissingRouteDispatching();

        $this->expectException(NoRouteMatched::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Cannot GET /testing');

        $middleware->process(
            new ServerRequest([], [], '/testing', 'GET'),
            $this->createMock(RequestHandlerInterface::class)
        );
    }
}
