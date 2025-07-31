<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\UpdateMarketData::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Update market data every minute
        $schedule->command('market:update')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
