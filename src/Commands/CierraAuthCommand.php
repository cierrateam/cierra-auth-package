<?php

namespace Cierra\Auth\Commands;

use Illuminate\Console\Command;

class CierraAuthCommand extends Command
{
    public $signature = 'cierra-auth';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
