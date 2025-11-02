<?php

namespace Scy;

class LineCounter
{
    private string $directory;
    private array $stats = [];
    private array $excludeDirs = [
        'vendor',
        'node_modules',
        '.git',
        '.idea',
        'storage',
        'cache',
        'build',
        'dist',
    ];

    private array $excludeFiles = [
        '.gitignore',
        '.env',
        '.DS_Store',
        '.ico',
        '.lock'
    ];

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
    }

    /**
     * Scan the directory and count lines
     */
    public function scan(): void
    {
        $this->scanDirectory($this->directory);
    }

    /**
     * Recursively scan directory
     */
    private function scanDirectory(string $dir): void
    {
        if (!is_readable($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            // Skip excluded directories
            if (is_dir($path)) {
                if (in_array($item, $this->excludeDirs)) {
                    continue;
                }
                $this->scanDirectory($path);
                continue;
            }

            // Skip excluded files
            if (in_array($item, $this->excludeFiles)) {
                continue;
            }

            // Process file
            if (is_file($path)) {
                $this->processFile($path);
            }
        }
    }

    /**
     * Process a single file and count its lines
     */
    private function processFile(string $filePath): void
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Handle .blade.php correctly
        if (str_ends_with($filePath, '.blade.php')) {
            $extension = 'blade.php';
        }

        // Skip files without extension
        if (empty($extension)) {
            return;
        }

        // Only include code extensions
        $allowedExtensions = [
            'php',
            'blade.php',
            'html',
            'css',
            'js',
            'ts',
            'vue',
            'jsx',
            'tsx',
            'scss',
            'less',
            'py',
            'java',
            'c',
            'cpp',
            'h',
            'cs',
            'go',
            'rb',
            'swift',
            'kt',
            'rs',
            'sh',
        ];

        if (!in_array($extension, $allowedExtensions, true)) {
            return;
        }

        // Count lines
        $lines = $this->countLines($filePath);

        if (!isset($this->stats[$extension])) {
            $this->stats[$extension] = [
                'files' => 0,
                'lines' => 0,
            ];
        }

        $this->stats[$extension]['files']++;
        $this->stats[$extension]['lines'] += $lines;
    }

    /**
     * Count lines in a file
     */
    private function countLines(string $filePath): int
    {
        $lines = 0;
        $handle = fopen($filePath, 'r');

        if ($handle) {
            while (fgets($handle) !== false) {
                $lines++;
            }
            fclose($handle);
        }

        return $lines;
    }

    /**
     * Display the results
     */
    public function display(): void
    {
        if (empty($this->stats)) {
            echo "No files found.\n";
            return;
        }

        // Sort by line count (desc)
        uasort($this->stats, fn($a, $b) => $b['lines'] <=> $a['lines']);

        $totalFiles = array_sum(array_column($this->stats, 'files'));
        $totalLines = array_sum(array_column($this->stats, 'lines'));

        // Dynamic terminal width (fallback to 80)
        $cols = exec('tput cols 2>/dev/null') ?: 80;

        $title = "SCY CLI";
        $subtitle = "{$this->directory}";
        $line = str_repeat('─', $cols);

        // Center helper
        $center = fn(string $text): string => str_pad($text, $cols, ' ', STR_PAD_BOTH);

        echo "\033[1;36m" . $line . "\033[0m\n"; // cyan line
        echo "\033[1;37m" . $center($title) . "\033[0m\n";
        echo "\033[2;37m" . $center($subtitle) . "\033[0m\n";
        echo "\033[1;36m" . $line . "\033[0m\n\n";

        // Header
        printf(
            " \033[1;34m%-15s %-15s %-20s\033[0m\n",
            'Extension',
            'Files',
            'Lines'
        );
        echo str_repeat('-', 52) . "\n";

        // Rows
        foreach ($this->stats as $extension => $data) {
            printf(
                " %-15s %-15s %-20s\n",
                '.' . $extension,
                number_format($data['files']),
                $this->formatNumber($data['lines'])
            );
        }

        echo str_repeat('-', 52) . "\n";
        printf(
            " \033[1;33m%-15s %-15s %-20s\033[0m\n",
            'TOTAL',
            number_format($totalFiles),
            $this->formatNumber($totalLines)
        );

        echo "\n" . "\033[1;36m" . $line . "\033[0m\n";
        echo "\n";
    }


    /**
     * Format large numbers (e.g., 1000 -> 1.0k)
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M (' . number_format($number) . ')';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'k (' . number_format($number) . ')';
        }
        return number_format($number);
    }

    /**
     * Add custom exclusions
     */
    public function excludeDirectories(array $dirs): self
    {
        $this->excludeDirs = array_merge($this->excludeDirs, $dirs);
        return $this;
    }

    /**
     * Add custom file exclusions
     */
    public function excludeFiles(array $files): self
    {
        $this->excludeFiles = array_merge($this->excludeFiles, $files);
        return $this;
    }

    /**
     * Get raw stats
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}