<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests;

use Chimera\Routing\RouteParamsExtraction;
use Chimera\Routing\RouteParamsExtractor;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes as PHPUnit;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[PHPUnit\CoversClass(RouteParamsExtraction::class)]
final class RouteParamsExtractionTest extends TestCase
{
    #[PHPUnit\Test]
    public function processShouldUseExtractorAndAddResultAsRequestAttribute(): void
    {
        $request  = new ServerRequest();
        $response = new Response();

        $extractor = $this->createMock(RouteParamsExtractor::class);
        $handler   = $this->createMock(RequestHandlerInterface::class);

        $extractor->method('getParams')
                  ->willReturn(['id' => '123']);

        $handler->expects(self::once())
                ->method('handle')
                ->with($request->withAttribute(RouteParamsExtraction::class, ['id' => '123']))
                ->willReturn($response);

        $middleware = new RouteParamsExtraction($extractor);

        self::assertSame($response, $middleware->process($request, $handler));
    }
}
