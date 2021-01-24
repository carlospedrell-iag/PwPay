<?php

namespace Pw\SlimApp\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

final class BeforeMiddleware
{
    public function __invoke(Request $request, RequestHandler $next): Response
    {
        $response = $next->handle($request);

        $existingContent = (string) $response->getBody();

        $response = new Response();
        $response->getBody()->write('BEFORE' . $existingContent);

        return $response;
    }
}