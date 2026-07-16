<?php namespace App\Controllers;

use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class McpController
{
    public function __construct(
        private readonly Server $mcpServer,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') return $this->responseFactory->createResponse(204);

        $stream = new StreamableHttpTransport(
            request: $request,
            responseFactory: $this->responseFactory,
            streamFactory: $this->streamFactory,
            middleware: []
        );

        return $this->mcpServer->run($stream);
    }
}
