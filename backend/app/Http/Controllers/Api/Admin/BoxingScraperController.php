<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class BoxingScraperController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'paths' => [
                'tool_dir' => 'tools/boxingdb_scraper',
                'sources_dir' => 'storage/app/boxingdb/sources',
                'imports_dir' => 'storage/app/boxingdb/imports',
                'raw_dir' => 'storage/app/boxingdb/raw',
                'screenshots_dir' => 'storage/app/boxingdb/screenshots',
            ],
            'python' => $this->pythonStatus(),
            'latest_imports' => $this->latestFiles(storage_path('app/boxingdb/imports'), '*.json'),
            'latest_sources' => $this->latestFiles(storage_path('app/boxingdb/sources'), '*.json'),
            'template_sources' => $this->readJsonFile(base_path('tools/boxingdb_scraper/sources.example.json')),
            'template_output' => $this->readJsonFile(base_path('tools/boxingdb_scraper/sample-output.example.json')),
        ]);
    }

    public function collect(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'sources_json' => ['required_without:sources_path', 'nullable', 'string'],
            'sources_path' => ['required_without:sources_json', 'nullable', 'string', 'max:500'],
            'browser' => ['nullable', 'in:chrome,firefox'],
            'headed' => ['boolean'],
            'wait_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'page_load_timeout' => ['nullable', 'integer', 'min:10', 'max:300'],
            'slow_mo' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'save_raw' => ['boolean'],
            'save_screenshots' => ['boolean'],
        ])->validate();

        $timestamp = now()->format('Ymd-His');
        $sourcesPath = $this->resolveInputPath($data['sources_path'] ?? null);

        if (! $sourcesPath) {
            $decoded = json_decode((string) ($data['sources_json'] ?? ''), true);
            if (! is_array($decoded)) {
                return response()->json(['message' => 'Sources JSON is invalid.'], 422);
            }

            $sourcesPath = storage_path("app/boxingdb/sources/admin-{$timestamp}.json");
            File::ensureDirectoryExists(dirname($sourcesPath));
            File::put($sourcesPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if (! is_file($sourcesPath)) {
            return response()->json(['message' => 'Sources file was not found.'], 422);
        }

        $outPath = storage_path("app/boxingdb/imports/scraped-{$timestamp}.json");
        File::ensureDirectoryExists(dirname($outPath));

        $command = [
            $this->pythonBinary(),
            '-m',
            'boxingdb_scraper',
            'collect',
            '--sources',
            $sourcesPath,
            '--out',
            $outPath,
            '--browser',
            $data['browser'] ?? 'chrome',
            '--wait-seconds',
            (string) ($data['wait_seconds'] ?? 15),
            '--page-load-timeout',
            (string) ($data['page_load_timeout'] ?? 45),
            '--slow-mo',
            (string) ($data['slow_mo'] ?? 0),
        ];

        if (! empty($data['headed'])) {
            $command[] = '--headed';
        }

        if (! empty($data['limit'])) {
            $command[] = '--limit';
            $command[] = (string) $data['limit'];
        }

        if (! empty($data['save_raw'])) {
            $rawDir = storage_path("app/boxingdb/raw/{$timestamp}");
            File::ensureDirectoryExists($rawDir);
            $command[] = '--raw-dir';
            $command[] = $rawDir;
        }

        if (! empty($data['save_screenshots'])) {
            $screenshotDir = storage_path("app/boxingdb/screenshots/{$timestamp}");
            File::ensureDirectoryExists($screenshotDir);
            $command[] = '--screenshot-dir';
            $command[] = $screenshotDir;
        }

        $process = new Process($command, base_path('tools/boxingdb_scraper'));
        $process->setTimeout(900);
        $process->run();

        $payload = [
            'ok' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'command' => $this->displayCommand($command),
            'sources_path' => $this->relativePath($sourcesPath),
            'output_path' => $this->relativePath($outPath),
            'stdout' => trim($process->getOutput()),
            'stderr' => trim($process->getErrorOutput()),
        ];

        if ($process->isSuccessful() && is_file($outPath)) {
            $payload['summary'] = $this->scrapeSummary($outPath);
        }

        return response()->json($payload, $process->isSuccessful() ? 200 : 422);
    }

    public function syncThesportsdb(): JsonResponse
    {
        $scraperDir = base_path('boxing-scraper');
        $storageDir = storage_path('app/public/fighters');
        $outputDir = $scraperDir . '/data';
        $script = $scraperDir . '/sync.py';

        if (! is_file($script)) {
            return response()->json(['ok' => false, 'message' => 'sync.py not found at ' . $script], 422);
        }

        File::ensureDirectoryExists($storageDir);
        File::ensureDirectoryExists($outputDir);

        $command = [
            'python3', $script,
            '--storage', $storageDir,
            '--output', $outputDir,
        ];

        $process = new Process($command, $scraperDir);
        $process->setTimeout(300);
        $process->run();

        $payload = [
            'ok' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'stdout' => trim($process->getOutput()),
            'stderr' => trim($process->getErrorOutput()),
            'data_path' => $outputDir . '/fighters.json',
        ];

        if ($process->isSuccessful() && is_file($outputDir . '/fighters.json')) {
            $data = json_decode((string) file_get_contents($outputDir . '/fighters.json'), true);
            $payload['summary'] = [
                'fighters' => is_array($data) ? count($data) : 0,
                'weight_classes' => 8,
                'images_downloaded' => is_array($data) ? count(array_filter(array_column($data, 'photo_url'))) : 0,
            ];
        }

        return response()->json($payload, $process->isSuccessful() ? 200 : 422);
    }

    public function import(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'payload' => ['required_without:path', 'nullable'],
            'path' => ['required_without:payload', 'nullable', 'string', 'max:500'],
            'dry_run' => ['boolean'],
            'replace_event_fights' => ['boolean'],
        ])->validate();

        $timestamp = now()->format('Ymd-His');
        $path = $this->resolveInputPath($data['path'] ?? null);

        if (! $path) {
            $payload = $data['payload'];
            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
            } else {
                $decoded = $payload;
            }

            if (! is_array($decoded)) {
                return response()->json(['message' => 'Import JSON is invalid.'], 422);
            }

            $path = storage_path("app/boxingdb/imports/admin-import-{$timestamp}.json");
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if (! is_file($path)) {
            return response()->json(['message' => 'Import file was not found.'], 422);
        }

        $arguments = [
            'path' => $this->relativePath($path),
            '--dry-run' => (bool) ($data['dry_run'] ?? false),
            '--replace-event-fights' => (bool) ($data['replace_event_fights'] ?? false),
        ];

        $exitCode = Artisan::call('boxingdb:import-scraped', $arguments);
        $output = trim(Artisan::output());

        return response()->json([
            'ok' => $exitCode === 0,
            'exit_code' => $exitCode,
            'path' => $this->relativePath($path),
            'dry_run' => (bool) ($data['dry_run'] ?? false),
            'output' => $output,
        ], $exitCode === 0 ? 200 : 422);
    }

    private function pythonStatus(): array
    {
        $syncScript = base_path('boxing-scraper/sync.py');
        $checks = ['requests'];
        if (is_file($syncScript)) {
            $checks[] = 'sync.py ready';
        }

        $process = new Process(['python3', '-c', 'import requests; print("TheSportsDB API sync ready")']);
        $process->setTimeout(10);
        $process->run();

        return [
            'ok' => $process->isSuccessful(),
            'message' => $process->isSuccessful()
                ? trim($process->getOutput())
                : trim($process->getErrorOutput() ?: $process->getOutput()),
            'sync_script' => is_file($syncScript),
        ];
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

    private function latestFiles(string $directory, string $pattern): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(glob($directory.DIRECTORY_SEPARATOR.$pattern) ?: [])
            ->sortByDesc(fn (string $path) => filemtime($path) ?: 0)
            ->take(10)
            ->map(fn (string $path) => [
                'path' => $this->relativePath($path),
                'name' => basename($path),
                'size' => filesize($path) ?: 0,
                'modified_at' => date(DATE_ATOM, filemtime($path) ?: time()),
            ])
            ->values()
            ->all();
    }

    private function readJsonFile(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    private function resolveInputPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return Str::startsWith($path, $base) ? Str::after($path, $base) : $path;
    }

    private function displayCommand(array $command): string
    {
        return implode(' ', array_map(fn (string $part) => escapeshellarg($part), $command));
    }

    private function scrapeSummary(string $path): array
    {
        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload)) {
            return [];
        }

        return collect(['fighters', 'events', 'fights', 'rankings', 'belts', 'media', 'broadcasters', 'event_broadcasts'])
            ->mapWithKeys(fn (string $key) => [$key => count($payload[$key] ?? [])])
            ->all();
    }
}
