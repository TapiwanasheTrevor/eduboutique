<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrichBookTitles extends Command
{
    protected $signature = 'books:enrich-titles {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Enrich duplicate book titles by appending level, publisher, or other distinguishing info';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made\n");
        }

        // Find all duplicate titles
        $duplicateTitles = Product::select('title', DB::raw('COUNT(*) as count'))
            ->groupBy('title')
            ->having('count', '>', 1)
            ->orderByDesc('count')
            ->pluck('count', 'title');

        $this->info("Found " . $duplicateTitles->count() . " titles with duplicates\n");

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($duplicateTitles as $title => $count) {
            $books = Product::where('title', $title)->get();

            $this->line("\nProcessing: <comment>{$title}</comment> ({$count} books)");

            foreach ($books as $book) {
                $newTitle = $this->generateEnrichedTitle($book, $title);

                if ($newTitle === $title) {
                    $skippedCount++;
                    continue;
                }

                // Check if new title already exists
                if (Product::where('title', $newTitle)->where('id', '!=', $book->id)->exists()) {
                    // Add more specificity
                    $newTitle = $this->generateEnrichedTitle($book, $title, true);
                }

                if ($isDryRun) {
                    $this->line("  Would update: <info>{$newTitle}</info>");
                } else {
                    $book->update(['title' => $newTitle]);
                    $this->line("  Updated to: <info>{$newTitle}</info>");
                }
                $updatedCount++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Titles processed', $duplicateTitles->count()],
                ['Books updated', $updatedCount],
                ['Books skipped (no enrichment possible)', $skippedCount],
            ]
        );

        return Command::SUCCESS;
    }

    private function generateEnrichedTitle(Product $book, string $originalTitle, bool $includePublisher = false): string
    {
        $parts = [$originalTitle];

        // Add level if available and meaningful
        if ($book->level && !$this->titleContainsLevel($originalTitle, $book->level)) {
            $level = $this->normalizeLevel($book->level);
            if ($level) {
                $parts[] = $level;
            }
        }

        // Add publisher if needed for extra distinction
        if ($includePublisher && $book->publisher && $book->publisher !== 'Unknown') {
            $parts[] = "({$book->publisher})";
        }

        return implode(' ', $parts);
    }

    private function titleContainsLevel(string $title, string $level): bool
    {
        $titleLower = strtolower($title);
        $levelLower = strtolower($level);

        // Check if level info is already in the title
        $levelPatterns = [
            'form 1', 'form 2', 'form 3', 'form 4', 'form 5', 'form 6',
            'f1', 'f2', 'f3', 'f4', 'f5', 'f6',
            'grade 1', 'grade 2', 'grade 3', 'grade 4', 'grade 5', 'grade 6', 'grade 7',
            'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7',
            'ecd a', 'ecd b', 'ecd',
            'o level', 'o-level', "o'level", 'a level', 'a-level',
            'igcse', 'as level', 'as-level',
            'lower secondary', 'upper secondary',
            'stage 1', 'stage 2', 'stage 3', 'stage 4', 'stage 5', 'stage 6',
            'stage 7', 'stage 8', 'stage 9',
            'book 1', 'book 2', 'book 3', 'book 4', 'book 5',
        ];

        foreach ($levelPatterns as $pattern) {
            if (str_contains($titleLower, $pattern)) {
                return true;
            }
        }

        // Also check if the level value itself is in the title
        if (str_contains($titleLower, $levelLower)) {
            return true;
        }

        return false;
    }

    private function normalizeLevel(string $level): ?string
    {
        $level = trim($level);

        // Skip if level is not meaningful
        $skipLevels = ['other', 'unknown', 'n/a', ''];
        if (in_array(strtolower($level), $skipLevels)) {
            return null;
        }

        // Normalize common level formats
        $normalizations = [
            '/^form\s*(\d+)$/i' => 'Form $1',
            '/^f(\d+)$/i' => 'Form $1',
            '/^grade\s*(\d+)$/i' => 'Grade $1',
            '/^g(\d+)$/i' => 'Grade $1',
            '/^ecd\s*a$/i' => 'ECD A',
            '/^ecd\s*b$/i' => 'ECD B',
            '/^o[\s\-]?level$/i' => "O'Level",
            '/^a[\s\-]?level$/i' => 'A-Level',
            '/^gce\s+ordinary\s+level$/i' => "O'Level",
            '/^gce\s+advanced\s+level$/i' => 'A-Level',
            '/^igcse$/i' => 'IGCSE',
            '/^as[\s\-]?level$/i' => 'AS-Level',
            '/^advanced\s+level$/i' => 'A-Level',
            '/^stage\s*(\d+)$/i' => 'Stage $1',
        ];

        foreach ($normalizations as $pattern => $replacement) {
            if (preg_match($pattern, $level)) {
                return preg_replace($pattern, $replacement, $level);
            }
        }

        // Return cleaned up level if no normalization matched
        return ucwords(strtolower($level));
    }
}
