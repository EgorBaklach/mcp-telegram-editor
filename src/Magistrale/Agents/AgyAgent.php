<?php namespace Magistrale\Agents;

use Symfony\Component\Process\Process;

class AgyAgent extends AbstractAgent
{
    private string $home = '';

    public function prepare(string $source): void
    {
        $this->home = sys_get_temp_dir().'/agy-home-'.$source; $this->fs->remove($this->home); $this->fs->mkdir($this->home.'/.gemini/antigravity-cli');

        foreach($this->options['agy']['links'] as $path)
        {
            $target = '/root/'.$path; if($this->fs->exists($target)) $this->fs->symlink($target, $this->home.'/'.$path);
        }
    }

    public function run(string $prompt): bool
    {
        $process = $this->process($prompt); $process->run(); $this->output = trim($process->getOutput()); $this->error = trim($process->getErrorOutput());

        return $process->isSuccessful();
    }

    protected function process(string $prompt): Process
    {
        $ttl = $this->options['timeout']; $process = new Process(...call_user_func($this->options['agy']['endpoint'], $prompt, $this->home, $ttl)); $process->setTimeout($ttl); return $process;
    }

    public function cleanup(): void
    {
        if($this->home) $this->fs->remove($this->home);
    }
}
