<?php namespace Magistrale\Dispatchers\Migration;

use Illuminate\Database\Capsule\Manager;
use Symfony\Component\Filesystem\Filesystem;

class CreateMigrationDispatcher extends AbstractMigrationDispatcher
{
    public function __construct(private Filesystem $fs, Manager $capsule)
    {
        return parent::__construct($capsule);
    }

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return $this->command->logger->error('Укажите название миграции через --new=ИМЯ_МИГРАЦИИ');

        $fileName = date('Y_m_d_His').'_'.preg_replace('/[^a-z0-9_]/', '_', strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $payload))).'.php';

        $templatePath = getcwd() . '/storage/app/templates/migration.php';

        $template = $this->fs->exists($templatePath) ? $this->fs->readFile($templatePath) : "<?php\n\nreturn new class\n{\n    public function up(): void {}\n    public function down(): void {}\n};\n";

        $this->fs->dumpFile(getcwd() . '/database/migrations/' . $fileName, $template);

        $this->command->logger->info("Миграция успешно создана: database/migrations/{$fileName}");

        return true;
    }
}