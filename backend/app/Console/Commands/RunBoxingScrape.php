<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunBoxingScrape extends Command
{
    protected $signature = 'boxingdb:scrape
        {--sources= : Source JSON path, relative to backend/ or absolute}
        {--out= : Output JSON path, relative to backend/ or absolute}
        {--browser=chrome : Selenium browser: chrome or firefox}
        {--headed : Show the browser window}
        {--wait-seconds=15 : Seconds to wait for rendered selectors}
        {--page-load-timeout=45 : Page load timeout in seconds}
        {--slow-mo=0 : Extra seconds to wait after page load}
        {--limit= : Only scrape the first N sources}
        {--save-raw : Save rendered HTML snapshots}
        {--save-screenshots : Save screenshots}
        {--import : Import the scraped JSON after collection}
        {--dry-run : Roll back the import after validation}
        {--replace-event-fights : Replace existing event fight cards during import}';

    protected $description = 'Run the Selenium BoxingDB scraper and optionally import the result.';

    public function handle(): int
    {
        $sources = $this->resolvePath($this->option('sources') ?: config('boxingdb.scraper.sources_path'));

        if (! $sources || ! is_file($sources)) {
            $this->error('Source file not found. Pass --sources=tools/boxingdb_scraper/sources.local.json or set BOXINGDB_SCRAPER_SOURCES_PATH.');

            return self::FAILURE;
        }

        $timestamp = now()->format('Ymd-His');
        $out = $this->resolvePath($this->option('out') ?: "storage/app/boxingdb/imports/scraped-{$timestamp}.json");
        File::ensureDirectoryExists(dirname($out));

        $command = [
            $this->pythonBinary(),
            '-m',
            'boxingdb_scraper',
            'collect',
            '--sources',
            $sources,
            '--out',
            $out,
            '--browser',
            (string) $this->option('browser'),
            '--wait-seconds',
            (string) $this->option('wait-seconds'),
            '--page-load-timeout',
            (string) $this->option('page-load-timeout'),
            '--slow-mo',
            (string) $this->option('slow-mo'),
        ];

        if ($this->option('headed')) {
            $command[] = '--headed';
        }

        if ($this->option('limit')) {
            $command[] = '--limit';
            $command[] = (string) $this->option('limit');
        }

        if ($this->option('save-raw')) {
            $rawDir = storage_path("app/boxingdb/raw/{$timestamp}");
            File::ensureDirectoryExists($rawDir);
            $command[] = '--raw-dir';
            $command[] = $rawDir;
        }

        if ($this->option('save-screenshots')) {
            $screenshotsDir = storage_path("app/boxingdb/screenshots/{$timestamp}");
            File::ensureDirectoryExists($screenshotsDir);
            $command[] = '--screenshot-dir';
            $command[] = $screenshotsDir;
        }

        $this->info('Running Selenium scraper...');
        $this->line($this->displayCommand($command));

        $process = new Process($command, base_path('tools/boxingdb_scraper'));
        $process->setTimeout((int) config('boxingdb.scraper.timeout', 1800));
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Scrape failed.');

            return self::FAILURE;
        }

        $this->info('Scrape saved to '.$this->relativePath($out));

        if (! $this->option('import')) {
            return self::SUCCESS;
        }

        $this->info('Importing scraped data...');
        $exitCode = Artisan::call('boxingdb:import-scraped', [
            'path' => $this->relativePath($out),
            '--dry-run' => (bool) $this->option('dry-run'),
            '--replace-event-fights' => (bool) $this->option('replace-event-fights'),
        ]);

        $this->output->write(Artisan::output());

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolvePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    private function pythonBinary(): string
    {
        if (config('boxingdb.scraper.python')) {
            return (string) config('boxingdb.scraper.python');
        }

        $linuxVenv = base_path('tools/boxingdb_scraper/.venv-linux/bin/python');
        if (PHP_OS_FAMILY === 'Linux' && is_file($linuxVenv)) {
            return $linuxVenv;
        }

        $venv = base_path('tools/boxingdb_scraper/.venv/bin/python');

        return PHP_OS_FAMILY !== 'Linux' && is_file($venv) ? $venv : 'python3';
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private function displayCommand(array $command): string
    {
        return implode(' ', array_map(fn (string $part) => escapeshellarg($part), $command));
    }
}
