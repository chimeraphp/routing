<?php
declare(strict_types=1);

namespace Chimera\Routing;

use Chimera\Input;
use Psr\Http\Message\ServerRequestInterface;

use function assert;
use function is_array;

/**
 * Implementation for input data that comes from HTTP
 */
final class HttpRequest implements Input
{
    public function __construct(private ServerRequestInterface $request)
    {
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        $data = $this->request->getParsedBody() ?? [];

        return (array) $data + $this->getContext();
    }

    /** @return array<string, mixed> */
    private function getContext(): array
    {
        $routeParams = $this->request->getAttribute(RouteParamsExtraction::class, []);
        assert(is_array($routeParams));

        return $routeParams + $this->request->getQueryParams();
    }
}
