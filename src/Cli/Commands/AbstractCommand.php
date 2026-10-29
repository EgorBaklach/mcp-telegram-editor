<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use League\Container\DefinitionContainerInterface;
use Magistrale\Contracts\{ConsoleLoggerInterface, MacroableInterface, OperableInterface, RedisInterface};
use Magistrale\Traits\{MacroableTrait, OperableTrait};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command implements CommandInterface, MacroableInterface, OperableInterface
{
    use MacroableTrait, OperableTrait;

    public DefinitionContainerInterface $container;

    public ConsoleLoggerInterface $logger;

    public RedisInterface $redis;

    public InputInterface $input;

    public array $configs = [];

    public function setContainer(DefinitionContainerInterface $container): self
    {
        $this->container = $container; return $this;
    }

    public function construct(): void
    {
        $this->configs = $this->callback->array('get', $this->container->get('commands'), static::class);   

        $this->logger = $this->container->get(ConsoleLoggerInterface::class);
        
        $this->redis = $this->container->get(RedisInterface::class);

        foreach($this->configs as $name => $config) $this->addOption($name, ...$config['option']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input; $this->logger->setOutput($output);

        foreach($this->configs as $option => $config)
        {
            if(!$this->hasOption($option)) continue;

            if($this->redis->locked($lock = $config['lock']))
            {
                $this->logger->comment('Опция '.$option.' уже обрабатывается (lock: '.$lock.'). Пропуск.'); return Command::SUCCESS;
            }

            try
            {
                return $this->handle($option, $config, $this->getOption($option)) ? Command::SUCCESS : Command::FAILURE;
            }
            finally
            {
                $this->redis->unlock($lock);
            }
        }

        $this->logger->error('Не указана опция! Добавьте флаг: --'.implode(', --', array_keys($this->configs)));

        $this->logger->info('Используйте --help для просмотра всех доступных опций.');

        return Command::INVALID;
    }

    public function handle(string $option, array $config, mixed $payload = null): bool
    {
        return $this->container->get($config['dispatcher'])->dispatch($payload);
    }
}
