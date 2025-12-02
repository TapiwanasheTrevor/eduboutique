<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class UpdateBookPrices extends Command
{
    protected $signature = 'books:update-prices {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Update book prices from prices.json file';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $pricesPath = public_path('library/updates/prices.json');

        if (!file_exists($pricesPath)) {
            $this->error("Prices file not found at: {$pricesPath}");
            return Command::FAILURE;
        }

        $prices = json_decode(file_get_contents($pricesPath), true);
        $products = Product::all();

        $this->info("Price list entries: " . count($prices));
        $this->info("Database products: " . $products->count());

        if ($isDryRun) {
            $this->warn("\nDRY RUN MODE - No changes will be made\n");
        }

        // Build a lookup index from price list
        $priceIndex = $this->buildPriceIndex($prices);

        $matched = 0;
        $notMatched = 0;
        $updated = 0;

        $this->output->progressStart($products->count());

        foreach ($products as $product) {
            $this->output->progressAdvance();

            $price = $this->findPrice($product, $priceIndex, $prices);

            if ($price !== null) {
                $matched++;

                if ($product->price_usd != $price) {
                    if (!$isDryRun) {
                        $product->update([
                            'price_usd' => $price,
                            'price_zwl' => 0,
                        ]);
                    }
                    $updated++;

                    if ($isDryRun && $updated <= 20) {
                        $this->newLine();
                        $this->line("  <comment>{$product->title}</comment>");
                        $this->line("    Price: \${$product->price_usd} -> <info>\${$price}</info>");
                    }
                }
            } else {
                $notMatched++;
            }
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products matched', $matched],
                ['Products not matched', $notMatched],
                ['Prices updated', $updated],
            ]
        );

        if ($notMatched > 0 && $this->output->isVerbose()) {
            $this->warn("\nUnmatched products (first 20):");
            $unmatchedProducts = $products->filter(function ($p) use ($priceIndex, $prices) {
                return $this->findPrice($p, $priceIndex, $prices) === null;
            })->take(20);

            foreach ($unmatchedProducts as $p) {
                $this->line("  - {$p->title}");
            }
        }

        return Command::SUCCESS;
    }

    private function buildPriceIndex(array $prices): array
    {
        $index = [];

        foreach ($prices as $item) {
            $title = strtolower(trim($item['title']));
            $normalized = $this->normalizeTitle($title);

            $index[$normalized] = $item['price'];
            $index[$title] = $item['price'];

            // Also index by key words
            $words = preg_split('/\s+/', $normalized);
            if (count($words) >= 3) {
                $key = implode(' ', array_slice($words, 0, 3));
                if (!isset($index[$key])) {
                    $index[$key] = $item['price'];
                }
            }
        }

        return $index;
    }

    private function findPrice(Product $product, array $priceIndex, array $prices): ?float
    {
        $title = strtolower(trim($product->title));
        $normalized = $this->normalizeTitle($title);

        // Direct match
        if (isset($priceIndex[$normalized])) {
            return (float) $priceIndex[$normalized];
        }

        if (isset($priceIndex[$title])) {
            return (float) $priceIndex[$title];
        }

        // Try matching by key patterns
        $patterns = $this->extractMatchPatterns($product);

        foreach ($prices as $item) {
            $priceTitle = strtolower(trim($item['title']));
            $priceNormalized = $this->normalizeTitle($priceTitle);

            foreach ($patterns as $pattern) {
                if ($this->matchesTitles($pattern, $priceNormalized, $priceTitle)) {
                    return (float) $item['price'];
                }
            }
        }

        // Fuzzy match - look for significant word overlap
        foreach ($prices as $item) {
            $priceTitle = strtolower(trim($item['title']));

            if ($this->fuzzyMatch($normalized, $priceTitle)) {
                return (float) $item['price'];
            }
        }

        return null;
    }

    private function normalizeTitle(string $title): string
    {
        // Expand common abbreviations
        $expansions = [
            'a.p.a' => 'a practical approach',
            'apa' => 'a practical approach',
            'a p a' => 'a practical approach',
            'f.r.s' => 'family and religious studies',
            'frs' => 'family and religious studies',
            'f r s' => 'family and religious studies',
            'l.b' => 'learners book',
            'lb' => 'learners book',
            't.g' => 'teachers guide',
            'tg' => 'teachers guide',
            'qn' => 'questions',
            'q' => 'questions',
            'ans' => 'answers',
            'a lvl' => 'a level',
            'o lvl' => 'o level',
            'lvl' => 'level',
            'rg' => 'revision guide',
            'f1' => 'form 1',
            'f2' => 'form 2',
            'f3' => 'form 3',
            'f4' => 'form 4',
            'f 1' => 'form 1',
            'f 2' => 'form 2',
            'f 3' => 'form 3',
            'f 4' => 'form 4',
            'gr' => 'grade',
            'g1' => 'grade 1',
            'g2' => 'grade 2',
            'g3' => 'grade 3',
            'g4' => 'grade 4',
            'g5' => 'grade 5',
            'g6' => 'grade 6',
            'g7' => 'grade 7',
            'agric' => 'agriculture',
            'acc' => 'accounts',
            'geog' => 'geography',
            'hist' => 'history',
            'sci' => 'science',
            'tech' => 'technology',
            'maths' => 'mathematics',
            'math' => 'mathematics',
            'chem' => 'chemistry',
            'phys' => 'physics',
            'bio' => 'biology',
            'eng' => 'english',
            'lit' => 'literature',
            'comm' => 'commerce',
            'econ' => 'economics',
            'compu' => 'computer',
            'comp' => 'computer',
            'p.e' => 'physical education',
            'pe' => 'physical education',
            'fd' => 'food',
            '&' => 'and',
        ];

        $title = strtolower($title);

        // Remove special characters but keep spaces
        $title = preg_replace('/[^\w\s]/', ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);

        // Apply expansions
        foreach ($expansions as $abbr => $full) {
            $title = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/', $full, $title);
        }

        return trim($title);
    }

    private function extractMatchPatterns(Product $product): array
    {
        $title = strtolower($product->title);
        $patterns = [];

        // Pattern: "A Practical Approach to X Form Y"
        if (preg_match('/practical approach.*?(\w+).*?(form \d|grade \d|o.?level|a.?level)/i', $title, $m)) {
            $patterns[] = 'practical approach ' . $m[1];
            $patterns[] = $m[1] . ' ' . $m[2];
        }

        // Pattern: Subject + Level
        if (preg_match('/^([\w\s]+?)\s*[-–]\s*(form \d|grade \d|level|ecd)/i', $title, $m)) {
            $patterns[] = trim($m[1]);
        }

        // Just the main subject
        $words = preg_split('/[\s\-–]+/', $title);
        if (count($words) >= 2) {
            $patterns[] = $words[0] . ' ' . $words[1];
        }

        return $patterns;
    }

    private function matchesTitles(string $pattern, string $normalizedPrice, string $rawPrice): bool
    {
        $pattern = strtolower($pattern);

        return str_contains($normalizedPrice, $pattern) || str_contains($rawPrice, $pattern);
    }

    private function fuzzyMatch(string $dbTitle, string $priceTitle): bool
    {
        // Get significant words from both titles
        $dbWords = $this->getSignificantWords($dbTitle);
        $priceWords = $this->getSignificantWords($priceTitle);

        if (count($dbWords) < 2 || count($priceWords) < 2) {
            return false;
        }

        // Count matching words
        $matches = 0;
        foreach ($dbWords as $word) {
            foreach ($priceWords as $pWord) {
                if ($word === $pWord || levenshtein($word, $pWord) <= 1) {
                    $matches++;
                    break;
                }
            }
        }

        // Require at least 60% word match
        $matchRatio = $matches / max(count($dbWords), count($priceWords));

        return $matchRatio >= 0.6;
    }

    private function getSignificantWords(string $title): array
    {
        $stopWords = ['a', 'an', 'the', 'to', 'of', 'and', 'for', 'in', 'on', 'at', 'by', 'with', 'is', 'are'];

        $words = preg_split('/\s+/', strtolower($title));
        $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));

        return array_values($words);
    }
}
