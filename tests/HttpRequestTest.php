<?php
declare(strict_types=1);

namespace Chimera\Routing\Tests;

use Chimera\Routing\HttpRequest;
use Chimera\Routing\RouteParamsExtraction;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass \Chimera\Routing\HttpRequest
 */
final class HttpRequestTest extends TestCase
{
    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::getAttribute()
     */
    public function getAttributeShouldReturnAttributeConfiguredInThePSR7Request(): void
    {
        $request = (new ServerRequest())->withAttribute('test', 1);
        $input   = new HttpRequest($request);

        self::assertSame(1, $input->getAttribute('test'));
        self::assertNull($input->getAttribute('test2'));
        self::assertSame(2, $input->getAttribute('test2', 2));
    }

    /**
     * @test
     *
     * @covers ::__construct()
     * @covers ::getData()
     * @covers ::getContext()
     */
    public function getDataShouldReturnAnEmptyArrayIfRequestDoesNotHaveAnyInfo(): void
    {
        $input = new HttpRequest(new ServerRequest());

        self::assertSame([], $input->getData());
    }

    /**
     * @test
     * @dataProvider dataMergingPossibilities
     *
     * @covers ::__construct()
     * @covers ::getData()
     * @covers ::getContext()
     *
     * @param array<string, string|int> $data
     */
    public function getDataShouldReturnMergeDataInTheCorrectPrecedence(
        ServerRequestInterface $request,
        array $data
    ): void {
        $input = new HttpRequest($request);

        self::assertSame($data, $input->getData());
    }

    /**
     * @return array<string, array<ServerRequest|array<string, string|int>>>
     */
    public function dataMergingPossibilities(): array
    {
        $base = new ServerRequest();

        $all = $base->withParsedBody(['test' => '1'])
                    ->withAttribute(RouteParamsExtraction::class, ['test' => '2', 'test1' => '1'])
                    ->withQueryParams(['test' => '2', 'test1' => '2', 'test2' => 1]);

        return [
            'parsed body only'      => [$base->withParsedBody(['test' => '1']), ['test' => '1']],
            'object as parsed body' => [$base->withParsedBody((object) ['test' => '1']), ['test' => '1']],
            'query string only'     => [$base->withQueryParams(['test' => '1']), ['test' => '1']],
            'route params only'     => [$base->withQueryParams(['test' => '1']), ['test' => '1']],
            'all together'          => [$all, ['test' => '1', 'test1' => '1', 'test2' => 1]],
        ];
    }
}
