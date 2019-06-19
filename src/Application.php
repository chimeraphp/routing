<?php
declare(strict_types=1);

namespace Chimera\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Application
{
    public function handle(ServerRequestInterface $request): ResponseInterface;

    public function run(): void;
}
