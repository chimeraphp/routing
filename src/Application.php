<?php
declare(strict_types=1);

namespace Chimera\Routing;

use Psr\Http\Server\RequestHandlerInterface;

interface Application extends RequestHandlerInterface
{
    public function run(): void;
}
