<?php

return [
    'scraper' => [
        'enabled' => env('BOXINGDB_SCRAPER_ENABLED', false),
        'python' => env('BOXINGDB_SCRAPER_PYTHON'),
        'sources_path' => env('BOXINGDB_SCRAPER_SOURCES_PATH', 'tools/boxingdb_scraper/sources.local.json'),
        'schedule' => env('BOXINGDB_SCRAPER_SCHEDULE', 'daily'),
        'timeout' => (int) env('BOXINGDB_SCRAPER_TIMEOUT', 1800),
        'import_after_scrape' => env('BOXINGDB_SCRAPER_IMPORT', true),
        'replace_event_fights' => env('BOXINGDB_SCRAPER_REPLACE_EVENT_FIGHTS', false),
        'save_raw' => env('BOXINGDB_SCRAPER_SAVE_RAW', false),
        'save_screenshots' => env('BOXINGDB_SCRAPER_SAVE_SCREENSHOTS', false),
    ],
];
