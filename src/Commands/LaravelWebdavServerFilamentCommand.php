<?php

namespace N3XT0R\LaravelWebdavServerFilament\Commands;

use Illuminate\Console\Command;

class LaravelWebdavServerFilamentCommand extends Command
{
    public $signature = 'laravel-webdav-server-filament';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
