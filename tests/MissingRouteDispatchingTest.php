<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests;

use Chimera\Routing\MissingRouteDispatching;
use Chimera\Routing\NoRouteMatched;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[PHPUnit\CoversClass(MissingRouteDispatching::class)]
#[PHPUnit\CoversClass(NoRouteMatched::class)]
final class MissingRouteDispatchingTest extends TestCase
{
    #[PHPUnit\Test]
    public function processShouldThrowAnExceptionWithRequestInformation(): void
    {
        $middleware = new MissingRouteDispatching();

        $this->expectException(NoRouteMatched::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Cannot GET /testing');

        $middleware->process(
            new ServerRequest([], [], '/testing', 'GET'),
            $this->createMock(RequestHandlerInterface::class),
        );
    }
}
