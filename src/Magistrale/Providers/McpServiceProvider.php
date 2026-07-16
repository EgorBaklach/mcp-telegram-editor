<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use Magistrale\Logging\StderrLogger;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Session\FileSessionStore;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Laminas\Diactoros\StreamFactory;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class McpServiceProvider extends ProviderAbstract implements BootableServiceProviderInterface
{
    private array $settings;

    protected array $provides = [
        LoggerInterface::class,
        Server::class,
        StreamFactoryInterface::class,
    ];

    public function boot(): void
    {
        $this->settings = $this->container()->get('mcp.settings');
    }

    public function register(): void
    {
        // 1. Logger
        $this->container()->addShared(LoggerInterface::class, fn (): StderrLogger => new StderrLogger($this->settings['logging']['stream'] ?? 'php://stderr'));

        // 2. StreamFactory
        $this->container()->addShared(StreamFactoryInterface::class, StreamFactory::class);

        // 3. MCP Server
        $this->container()->addShared(Server::class, function (): Server
        {
            $builder = (new Builder())
                ->setServerInfo($this->settings['server_name'], $this->settings['server_ver'])
                ->setLogger($this->container()->get(LoggerInterface::class))
                ->setSession(new FileSessionStore($this->settings['session_dir']));

            foreach ($this->container()->get('mcp.tools') as $tool) $builder->addTool(...$tool);

            return $builder->build();
        });
    }
}
