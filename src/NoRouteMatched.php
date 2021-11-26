<?php
declare(strict_types=1);

namespace Chimera\Routing;

use Chimera\Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function sprintf;

final class NoRouteMatched extends RuntimeException implements Exception
{
    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self(
            sprintf('Cannot %s %s', $request->getMethod(), $request->getUri()),
            StatusCodeInterface::STATUS_NOT_FOUND,
        );
    }
}
