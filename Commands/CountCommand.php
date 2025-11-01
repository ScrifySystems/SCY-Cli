<?php

namespace Scy\Commands;

use Scy\LineCounter;

class CountCommand
{
    public function execute(array $args): int
    {
        // defaults
        $path = getcwd();
        $excludeDirs = [];
        $excludeFiles = [];
        $json = false;

        // first non-flag = path
        foreach ($args as $i => $arg) {
            if ($arg === '-h' || $arg === '--help') {
                $this->printHelp();
                return 0;
            }
            if ($arg !== '' && $arg[0] !== '-') {
                $path = $arg;
                unset($args[$i]);
                break;
            }
        }

        // options
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--exclude-dir=')) {
                $excludeDirs = array_filter(array_map('trim', explode(',', substr($arg, 14))));
            } elseif (str_starts_with($arg, '--exclude-file=')) {
                $excludeFiles = array_filter(array_map('trim', explode(',', substr($arg, 15))));
            } elseif ($arg === '--json' || $arg === '-j') {
                $json = true;
            }
        }

        if (!is_dir($path)) {
            fwrite(STDERR, "Error: '{$path}' is not a valid directory.\n");
            return 1;
        }
        if (!is_readable($path)) {
            fwrite(STDERR, "Error: '{$path}' is not readable.\n");
            return 1;
        }

        $counter = new LineCounter($path);
        if ($excludeDirs)  { $counter->excludeDirectories($excludeDirs); }
        if ($excludeFiles) { $counter->excludeFiles($excludeFiles);   }

        echo "🔍 Scanning: " . (realpath($path) ?: $path) . "\n\n";
        $counter->scan();

        if ($json) {
            $stats = $counter->getStats();
            $totFiles = 0; $totLines = 0;
            foreach ($stats as $d) { $totFiles += $d['files'] ?? 0; $totLines += $d['lines'] ?? 0; }
            $payload = [
                'directory' => realpath($path) ?: $path,
                'totals' => ['files' => $totFiles, 'lines' => $totLines],
                'by_extension' => $stats,
            ];
            echo json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL;
        } else {
            $counter->display();
        }

        return 0;
    }

    private function printHelp(): void
    {
        echo <<<TXT
Usage:
  scy count [path] [options]

Options:
  --exclude-dir=list     Comma-separated directories to exclude
  --exclude-file=list    Comma-separated files to exclude
  -j, --json             Output JSON
  -h, --help             Show help

Examples:
  scy count .
  scy count /var/www/SCY-Home --exclude-dir=vendor,node_modules --json

TXT;
    }
}
