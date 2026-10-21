<?php namespace Magistrale\Agents;

use Symfony\Component\Process\Process;

class AgyAgent extends AbstractAgent
{
    private string $homeDir = '';

    public function prepare(string $sourceName): void
    {
        $this->homeDir = sys_get_temp_dir().'/agy-home-'.$sourceName; $this->fs->remove($this->homeDir); $this->fs->mkdir("{$this->homeDir}/.gemini/antigravity-cli");

        foreach($this->options['agy']['links'] as $relPath)
        {
            $source = "/root/{$relPath}"; if($this->fs->exists($source)) $this->fs->symlink($source, "{$this->homeDir}/{$relPath}");
        }
    }

    public function run(string $prompt): bool
    {
        $process = $this->createProcess($prompt); $process->run(); $this->output = trim($process->getOutput()); $this->errorOutput = trim($process->getErrorOutput());

        return $process->isSuccessful();
    }

    protected function createProcess(string $prompt): Process
    {
        $process = new Process(...call_user_func($this->options['agy']['endpoint'], $prompt, $this->homeDir, $ttl = $this->options['timeout'] ?? 600)); $process->setTimeout($ttl); return $process;
    }

    public function cleanup(): void
    {
        if($this->homeDir) $this->fs->remove($this->homeDir);
    }
}
