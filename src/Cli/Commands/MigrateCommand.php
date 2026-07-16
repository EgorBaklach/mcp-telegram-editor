<?php namespace Cli\Commands;

use Framework\Contracts\Console\CommandInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

#[AsCommand(name: 'db:migrate', description: 'Запускает все доступные миграции базы данных')]
class MigrateCommand extends Command implements CommandInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function construct(): void
    {
        // Разрешаем Capsule из контейнера для инициализации БД
        $this->container->get(Capsule::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Инициализация миграций...</info>');

        // 1. Создаем таблицу migrations, если её нет
        if (!Capsule::schema()->hasTable('migrations')) {
            $output->writeln('Создается таблица <comment>migrations</comment>...');
            Capsule::schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
            $output->writeln('Таблица <comment>migrations</comment> успешно создана.');
        }

        // 2. Получаем список уже выполненных миграций
        $executed = Capsule::table('migrations')->pluck('migration')->toArray();

        // 3. Сканируем директорию migrations
        $migrationsDir = dirname(__DIR__, 3) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        $files = glob($migrationsDir . '/*.php');
        sort($files);

        $newMigrations = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $executed)) {
                $newMigrations[$name] = $file;
            }
        }

        if (empty($newMigrations)) {
            $output->writeln('<info>База данных в актуальном состоянии. Нет новых миграций.</info>');
            return Command::SUCCESS;
        }

        // Вычисляем следующий батч (пакет)
        $batch = (Capsule::table('migrations')->max('batch') ?? 0) + 1;

        // 4. Запускаем новые миграции
        foreach ($newMigrations as $name => $file) {
            $output->write("Применение миграции <comment>{$name}</comment>... ");
            
            try {
                $migrationInstance = require $file;
                
                if (is_object($migrationInstance) && method_exists($migrationInstance, 'up')) {
                    $migrationInstance->up();
                } else {
                    throw new \RuntimeException("Файл миграции {$name} должен возвращать объект с методом up().");
                }

                // Записываем информацию о выполненной миграции
                Capsule::table('migrations')->insert([
                    'migration' => $name,
                    'batch'     => $batch,
                ]);

                $output->writeln('<info>ОК</info>');
            } catch (\Throwable $e) {
                $output->writeln('<error>ОШИБКА</error>');
                $output->writeln("<error>Ошибка при выполнении миграции {$name}: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>Все миграции успешно выполнены!</info>');
        return Command::SUCCESS;
    }
}
