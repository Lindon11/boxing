<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\GenerateLicenseCallbackSecret::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (config('boxingdb.scraper.enabled')) {
            $parameters = [
                '--sources' => config('boxingdb.scraper.sources_path'),
            ];

            if (config('boxingdb.scraper.import_after_scrape')) {
                $parameters['--import'] = true;
            }

            if (config('boxingdb.scraper.replace_event_fights')) {
                $parameters['--replace-event-fights'] = true;
            }

            if (config('boxingdb.scraper.save_raw')) {
                $parameters['--save-raw'] = true;
            }

            if (config('boxingdb.scraper.save_screenshots')) {
                $parameters['--save-screenshots'] = true;
            }

            $event = $schedule->command('boxingdb:scrape', $parameters)
                ->withoutOverlapping()
                ->runInBackground();

            match (config('boxingdb.scraper.schedule')) {
                'hourly' => $event->hourly(),
                'every-six-hours' => $event->everySixHours(),
                'twice-daily' => $event->twiceDaily(),
                default => $event->daily(),
            };
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
