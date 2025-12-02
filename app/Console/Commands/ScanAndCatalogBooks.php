<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScanAndCatalogBooks extends Command
{
    protected $signature = 'books:scan-catalog
                            {--output= : Output JSON file path for catalog}
                            {--import : Import directly to database}
                            {--dry-run : Show what would be imported without making changes}';

    protected $description = 'Scan the library/updates folder for images and generate/import book catalog';

    private array $subjectKeywords = [
        'Mathematics' => ['math', 'maths', 'mathematics', 'mental maths', 'pure maths', 'statistics'],
        'English' => ['english', 'eng ', 'engl', 'grammar', 'companion'],
        'Science' => ['science', 'scie', 'sci '],
        'Biology' => ['biology', 'bio '],
        'Chemistry' => ['chemistry', 'chem'],
        'Physics' => ['physics', 'phys'],
        'History' => ['history', 'hist'],
        'Geography' => ['geography', 'geo '],
        'Commerce' => ['commerce', 'comm'],
        'Economics' => ['economics', 'econ'],
        'Accounting' => ['accounting', 'accounts', 'acc '],
        'Business Studies' => ['business', 'bes ', 'enterprise'],
        'Computer Science' => ['computer', 'comp ', 'ict', 'csit'],
        'Shona' => ['shona', 'chishona'],
        'Ndebele' => ['ndebele', 'isindebele'],
        'French' => ['french', 'tricolore'],
        'Heritage' => ['heritage'],
        'Religious Studies' => ['religious', 'frs', 'bible'],
        'Sociology' => ['sociology', 'haralambos'],
        'Food & Nutrition' => ['food', 'nutrition', 'anita tull'],
        'Design & Technology' => ['design', 'technology', 'textile', 'ttd', 'metal', 'building', 'btd'],
        'Physical Education' => ['physical education', 'pe ', 'pesmd', 'sport'],
        'Agriculture' => ['agriculture', 'agric'],
        'Art' => ['art'],
        'Combined Science' => ['combined science', 'comb scie'],
    ];

    private array $levelKeywords = [
        'ECD' => ['ecd', 'foundation'],
        'Primary' => ['grade 1', 'grade 2', 'grade 3', 'grade 4', 'grade 5', 'grade 6', 'grade 7', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'gr1', 'gr2', 'gr3', 'gr4', 'gr5', 'gr6', 'gr7', 'primary', 'infant', 'stage 1', 'stage 2', 'stage 3', 'stage 4', 'stage 5', 'stage 6'],
        'O-Level' => ['form 1', 'form 2', 'form 3', 'form 4', 'f1', 'f2', 'f3', 'f4', 'o level', 'o\'level', 'o-level', 'olevel', 'igcse'],
        'A-Level' => ['form 5', 'form 6', 'f5', 'f6', 'a level', 'a\'level', 'a-level', 'alevel', 'advanced'],
        'Cambridge' => ['cambridge', 'stage 7', 'stage 8', 'stage 9', 'checkpoint', 'lower secondary'],
    ];

    private array $syllabusKeywords = [
        'ZIMSEC' => ['zimsec', 'zimbabwe', 'apa ', 'step ahead', 'gramsol', 'diamond key', 'emerald key', 'excel ', 'hbc', 'new trends'],
        'Cambridge' => ['cambridge', 'igcse', 'checkpoint', 'cup', 'oxford'],
    ];

    private array $publisherPatterns = [
        'A Practical Approach' => ['apa ', 'a p a', 'practical approach'],
        'Step Ahead Publishers' => ['step ahead', 'sa '],
        'Gramsol Publishers' => ['gramsol', 'grams '],
        'Diamond Publishers' => ['diamond'],
        'Emerald Publishers' => ['emerald'],
        'Excel Publishers' => ['excel '],
        'Cambridge University Press' => ['cambridge', 'cup'],
        'Oxford University Press' => ['oxford'],
        'HBC Publishers' => ['hbc'],
        'New Trends Publishers' => ['new trends'],
        'College Press' => ['college press'],
        'Longman' => ['longman'],
        'Focus Publishers' => ['focus '],
        'Plus One Publishers' => ['plus1', 'plusone', 'plus one'],
    ];

    public function handle(): int
    {
        $basePath = public_path('library/updates');

        if (!File::isDirectory($basePath)) {
            $this->error("Directory not found: {$basePath}");
            return Command::FAILURE;
        }

        $this->info("Scanning: {$basePath}");
        $this->newLine();

        // Get all image files
        $images = $this->getAllImages($basePath);
        $this->info("Found " . count($images) . " image files");
        $this->newLine();

        // Group by folder for review
        $folders = [];
        foreach ($images as $imagePath) {
            $relativePath = str_replace($basePath . '/', '', $imagePath);
            $folder = dirname($relativePath);
            if (!isset($folders[$folder])) {
                $folders[$folder] = [];
            }
            $folders[$folder][] = $relativePath;
        }

        // Display folder structure
        $this->info("=== FOLDER STRUCTURE ===");
        $this->newLine();

        $catalog = [];

        foreach ($folders as $folder => $files) {
            $this->warn("📁 {$folder} (" . count($files) . " images)");

            foreach ($files as $file) {
                $bookData = $this->extractBookData($file, $folder);
                $catalog[] = $bookData;

                $this->line("   📖 {$bookData['title']}");
                $this->line("      Subject: {$bookData['subject']} | Level: {$bookData['level']} | Syllabus: {$bookData['syllabus']}");
            }
            $this->newLine();
        }

        $this->info("=== SUMMARY ===");
        $this->info("Total books cataloged: " . count($catalog));
        $this->newLine();

        // Output to JSON file
        $outputPath = $this->option('output') ?: public_path('library/updates/verified_catalog.json');
        File::put($outputPath, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("Catalog saved to: {$outputPath}");

        // Import if requested
        if ($this->option('import')) {
            $this->newLine();
            $this->importBooks($catalog, $this->option('dry-run'));
        }

        return Command::SUCCESS;
    }

    private function getAllImages(string $basePath): array
    {
        $images = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions)) {
                    $images[] = $file->getPathname();
                }
            }
        }

        sort($images);
        return $images;
    }

    private function extractBookData(string $relativePath, string $folder): array
    {
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $searchText = strtolower($filename . ' ' . $folder);

        // Clean up filename for title
        $title = $this->cleanTitle($filename, $folder);

        // Extract metadata
        $subject = $this->detectSubject($searchText, $folder);
        $level = $this->detectLevel($searchText, $folder);
        $syllabus = $this->detectSyllabus($searchText, $folder);
        $publisher = $this->detectPublisher($searchText);
        $author = $this->detectAuthor($filename, $searchText);

        // Generate pricing based on level
        $pricing = $this->generatePricing($level);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->generateDescription($title, $subject, $level, $syllabus, $author),
            'price_zwl' => $pricing['zwl'],
            'price_usd' => $pricing['usd'],
            'category_id' => null,
            'syllabus' => $syllabus,
            'level' => $level,
            'subject' => $subject,
            'publisher' => $publisher,
            'isbn' => null,
            'author' => $author,
            'cover_image' => './' . $relativePath,
            'stock_status' => 'in_stock',
            'stock_quantity' => 10,
            'featured' => false,
            'folder' => $folder,
        ];
    }

    private function cleanTitle(string $filename, string $folder): string
    {
        // Remove common patterns
        $title = $filename;

        // Handle "Image (XXX)" pattern - use folder context
        if (preg_match('/^Image\s*\(\d+\)/i', $title)) {
            $folderParts = explode('/', $folder);
            $contextFolder = end($folderParts);
            $parentFolder = count($folderParts) > 1 ? $folderParts[count($folderParts) - 2] : '';

            // Try to create a meaningful title from folder
            $title = $this->titleFromFolder($contextFolder, $parentFolder, $filename);
        }

        // Handle Screenshot patterns
        if (preg_match('/^Screenshot/i', $title)) {
            $folderParts = explode('/', $folder);
            $contextFolder = end($folderParts);
            $title = str_replace(['G7', 'Grade 7'], 'Grade 7', $contextFolder) . ' Book';
        }

        // Clean up common abbreviations
        $replacements = [
            '/\bLB\b/i' => "Learner's Book",
            '/\bWB\b/i' => 'Workbook',
            '/\bWK\b/i' => 'Workbook',
            '/\bTG\b/i' => "Teacher's Guide",
            '/\bRG\b/i' => 'Revision Guide',
            '/\bRR\b/i' => 'Revision',
            '/\bF(\d)\b/i' => 'Form $1',
            '/\bG(\d)\b/i' => 'Grade $1',
            '/\bgr(\d)\b/i' => 'Grade $1',
            '/\bO Lvl\b/i' => 'O Level',
            '/\bA Lvl\b/i' => 'A Level',
            '/\bSA\b/' => 'Step Ahead',
            '/\bAPA\b/i' => 'A Practical Approach',
            '/\bCamb\b/i' => 'Cambridge',
            '/\bScie\b/i' => 'Science',
            '/\bMaths\b/i' => 'Mathematics',
            '/\bEng\b/i' => 'English',
            '/\bHist\b/i' => 'History',
            '/\bGeo\b/i' => 'Geography',
            '/\bAcc\b/i' => 'Accounting',
            '/\bComp\b/i' => 'Computer Science',
            '/\bEcon\b/i' => 'Economics',
            '/\bBio\b/i' => 'Biology',
            '/\bChem\b/i' => 'Chemistry',
            '/\bPhys\b/i' => 'Physics',
            '/\bComm\b/i' => 'Commerce',
            '/\bBES\b/i' => 'Business Enterprise Studies',
            '/\bFRS\b/i' => 'Family Religious Studies',
            '/\bTTD\b/i' => 'Textile Technology',
            '/\bBTD\b/i' => 'Building Technology',
            '/\bPESMD\b/i' => 'Physical Education, Sport, Mass Display',
            '/\bQn\b/i' => 'Questions',
            '/\bAns\b/i' => 'Answers',
            '/\bCBK\b/i' => 'Coursebook',
            '/\bPc\b/i' => 'Pack',
            '/\bECD\b/i' => 'ECD',
            '/\s+/' => ' ',
            '/ - Copy$/i' => '',
            '/\.tmp$/i' => '',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $title = preg_replace($pattern, $replacement, $title);
        }

        return trim(ucwords(strtolower(trim($title))));
    }

    private function titleFromFolder(string $folder, string $parentFolder, string $filename): string
    {
        // Extract number from Image (XXX)
        preg_match('/\((\d+)\)/', $filename, $matches);
        $num = $matches[1] ?? '';

        $folder = str_replace(['WB & LB', 'Bks', 'Books'], '', $folder);
        $folder = trim($folder);

        if (!empty($parentFolder)) {
            return "{$parentFolder} - {$folder} Book {$num}";
        }

        return "{$folder} Book {$num}";
    }

    private function detectSubject(string $searchText, string $folder): ?string
    {
        // First check folder name
        $folderLower = strtolower($folder);
        foreach ($this->subjectKeywords as $subject => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($folderLower, $keyword)) {
                    return $subject;
                }
            }
        }

        // Then check filename/full text
        foreach ($this->subjectKeywords as $subject => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    return $subject;
                }
            }
        }

        return 'General';
    }

    private function detectLevel(string $searchText, string $folder): string
    {
        $folderLower = strtolower($folder);

        foreach ($this->levelKeywords as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($folderLower, $keyword) || str_contains($searchText, $keyword)) {
                    return $level;
                }
            }
        }

        // Detect from folder structure
        if (str_contains($folderLower, 'o & a level')) {
            // Could be either - check more context
            if (preg_match('/f[5-6]|form [5-6]|a level|a lvl/i', $searchText)) {
                return 'A-Level';
            }
            return 'O-Level';
        }

        if (str_contains($folderLower, 'igcse') || str_contains($folderLower, 'cambridge')) {
            return 'Cambridge';
        }

        return 'Other';
    }

    private function detectSyllabus(string $searchText, string $folder): string
    {
        $folderLower = strtolower($folder);

        if (str_contains($folderLower, 'cambridge') || str_contains($folderLower, 'igcse')) {
            return 'Cambridge';
        }

        if (str_contains($folderLower, 'zimsec')) {
            return 'ZIMSEC';
        }

        foreach ($this->syllabusKeywords as $syllabus => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    return $syllabus;
                }
            }
        }

        return 'General';
    }

    private function detectPublisher(string $searchText): ?string
    {
        foreach ($this->publisherPatterns as $publisher => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($searchText, $pattern)) {
                    return $publisher;
                }
            }
        }

        return null;
    }

    private function detectAuthor(string $filename, string $searchText): ?string
    {
        // Known author patterns
        $authors = [
            'D G Mackean' => ['mackean'],
            'Eric Lam' => ['eric lam'],
            'Anita Tull' => ['anita tull'],
            'Haralambos' => ['haralambos'],
            'D Sang' => ['d sang'],
            'Sue Pemberton' => ['sue pemberton', 'pemberton'],
            'Ian Harrison' => ['ian harrison'],
            'Bruce Jewell' => ['bruce jewel'],
            'Ian Marcouse' => ['ian marcouse', 'malcolm'],
            'David Watson' => ['d watson', 'david watson'],
            'R Norris' => ['r norris'],
            'Clegg' => ['clegg'],
            'Vijay Sokharee' => ['vijay sokharee'],
            'Agatha Ramm' => ['agatha ramm'],
            'David Thompson' => ['david tompson', 'david thompson'],
            'D Richards' => ['d richards'],
            'Graham' => ['graham'],
            'Julia Burchell' => ['julia burchell'],
            'Dean Roberts' => ['dean roberts'],
            'Marian Cox' => ['marian cox'],
            'Paul Long' => ['paul long'],
            'David Waller' => ['david waller'],
        ];

        foreach ($authors as $author => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($searchText, $pattern)) {
                    return $author;
                }
            }
        }

        return null;
    }

    private function generatePricing(string $level): array
    {
        $basePrices = [
            'ECD' => 6.00,
            'Primary' => 8.00,
            'O-Level' => 12.00,
            'A-Level' => 15.00,
            'Cambridge' => 18.00,
            'Other' => 10.00,
        ];

        $usd = $basePrices[$level] ?? 10.00;
        $zwlRate = 35000; // Approximate rate

        return [
            'usd' => round($usd, 2),
            'zwl' => round($usd * $zwlRate, 2),
        ];
    }

    private function generateDescription(string $title, ?string $subject, string $level, string $syllabus, ?string $author): string
    {
        $parts = [$title];

        if ($author) {
            $parts[] = "by {$author}";
        }

        if ($level !== 'Other') {
            $parts[] = "for {$level} students";
        }

        if ($subject && $subject !== 'General') {
            $parts[] = "- {$subject}";
        }

        if ($syllabus !== 'General') {
            $parts[] = "({$syllabus} syllabus)";
        }

        return implode(' ', $parts);
    }

    private function importBooks(array $catalog, bool $dryRun): void
    {
        $this->info($dryRun ? "DRY RUN - No changes will be made" : "Importing books to database...");

        $bookCategory = Category::firstOrCreate(
            ['name' => 'Books'],
            ['slug' => 'books', 'description' => 'Educational books for all levels']
        );

        $imported = 0;
        $skipped = 0;

        foreach ($catalog as $book) {
            // Check if image exists
            $imagePath = public_path('library/updates/' . ltrim($book['cover_image'], './'));
            if (!File::exists($imagePath)) {
                $this->warn("Skipping (no image): {$book['title']}");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("Would import: {$book['title']}");
                $imported++;
                continue;
            }

            // Generate unique slug
            $slug = $book['slug'];
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $book['slug'] . '-' . $counter;
                $counter++;
            }

            Product::create([
                'title' => $book['title'],
                'slug' => $slug,
                'description' => $book['description'],
                'price_zwl' => $book['price_zwl'],
                'price_usd' => $book['price_usd'],
                'category_id' => $bookCategory->id,
                'syllabus' => $book['syllabus'],
                'level' => $book['level'],
                'subject' => $book['subject'],
                'publisher' => $book['publisher'],
                'author' => $book['author'],
                'cover_image' => '/library/updates/' . ltrim($book['cover_image'], './'),
                'stock_status' => $book['stock_status'],
                'stock_quantity' => $book['stock_quantity'],
                'featured' => $book['featured'],
            ]);

            $imported++;
        }

        $this->newLine();
        $this->info("Imported: {$imported} | Skipped: {$skipped}");
    }
}
