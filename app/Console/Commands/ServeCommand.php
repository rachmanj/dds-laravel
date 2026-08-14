<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class ServeCommand extends BaseServeCommand
{
    protected function startProcess($hasEnvironment)
    {
        if (! windows_os()) {
            return parent::startProcess($hasEnvironment);
        }

        $environment = new Collection($_ENV);

        if ($this->phpServerWorkers) {
            $environment = $environment->merge([
                'PHP_CLI_SERVER_WORKERS' => $this->phpServerWorkers,
            ]);
        }

        $process = new Process(
            $this->serverCommand(),
            public_path(),
            $environment->all()
        );

        $this->trap(fn () => [SIGTERM, SIGINT, SIGHUP, SIGUSR1, SIGUSR2, SIGQUIT], function ($signal) use ($process) {
            if ($process->isRunning()) {
                $process->stop(10, $signal);
            }

            exit;
        });

        $process->start($this->handleProcessOutput());

        return $process;
    }
}
