<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load books from JSON file
        $jsonPath = public_path('library/books/books.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("Books JSON file not found at: {$jsonPath}");
            return;
        }

        $booksData = json_decode(File::get($jsonPath), true);

        if (!$booksData) {
            $this->command->error("Failed to parse books JSON file");
            return;
        }

        $this->command->info("Found " . count($booksData) . " books to seed");

        // Get or create a "Books" category
        $bookCategory = Category::firstOrCreate(
            ['name' => 'Books'],
            [
                'slug' => 'books',
                'description' => 'Educational books for all levels',
            ]
        );

        $seededCount = 0;
        $skippedCount = 0;

        foreach ($booksData as $bookData) {
            try {
                // Extract enriched data from book title and author
                $enrichedData = $this->enrichBookData($bookData);

                // Skip if image doesn't exist
                $imagePath = public_path('library/books/' . $bookData['image_url']);
                if (!File::exists($imagePath)) {
                    $this->command->warn("Image not found for: {$bookData['book_title']}");
                    $skippedCount++;
                    continue;
                }

                // Create or update the product
                Product::updateOrCreate(
                    [
                        'slug' => Str::slug($bookData['book_title']),
                    ],
                    [
                        'title' => $bookData['book_title'],
                        'description' => $enrichedData['description'],
                        'price_zwl' => $enrichedData['price_zwl'],
                        'price_usd' => $enrichedData['price_usd'],
                        'category_id' => $bookCategory->id,
                        'syllabus' => $enrichedData['syllabus'],
                        'level' => $enrichedData['level'],
                        'subject' => $enrichedData['subject'],
                        'publisher' => $enrichedData['publisher'],
                        'author' => $bookData['author'] ?? 'Unknown',
                        'cover_image' => '/library/books/' . $bookData['image_url'],
                        'stock_status' => 'in_stock',
                        'stock_quantity' => rand(5, 50),
                        'featured' => $enrichedData['featured'],
                    ]
                );

                $seededCount++;

                if ($seededCount % 10 == 0) {
                    $this->command->info("Seeded {$seededCount} books...");
                }

            } catch (\Exception $e) {
                $this->command->error("Error seeding book: {$bookData['book_title']} - " . $e->getMessage());
                $skippedCount++;
            }
        }

        $this->command->info("✓ Successfully seeded {$seededCount} books");
        if ($skippedCount > 0) {
            $this->command->warn("⚠ Skipped {$skippedCount} books due to errors");
        }
    }

    /**
     * Enrich book data based on title and metadata
     */
    private function enrichBookData(array $bookData): array
    {
        $title = $bookData['book_title'];
        $author = $bookData['author'] ?? '';

        // Initialize enriched data
        $enriched = [
            'description' => $bookData['description'] ?? $title . ' by ' . $author,
            'price_zwl' => 0,
            'price_usd' => 0,
            'syllabus' => 'Other',
            'level' => 'Other',
            'subject' => null,
            'publisher' => null,
            'featured' => false,
        ];

        // Determine education level
        $enriched['level'] = $this->determineLevel($title);

        // Determine subject
        $enriched['subject'] = $this->determineSubject($title);

        // Determine syllabus
        $enriched['syllabus'] = $this->determineSyllabus($title);

        // Determine publisher
        $enriched['publisher'] = $this->determinePublisher($title, $author);

        // Set pricing based on level and type
        $pricing = $this->determinePricing($enriched['level'], $title);
        $enriched['price_zwl'] = $pricing['zwl'];
        $enriched['price_usd'] = $pricing['usd'];

        // Feature popular series
        $enriched['featured'] = $this->shouldFeature($title);

        // Enrich description
        $enriched['description'] = $this->createRichDescription($title, $author, $enriched);

        return $enriched;
    }

    /**
     * Determine education level from title
     */
    private function determineLevel(string $title): string
    {
        $title = strtolower($title);

        // Primary levels
        if (preg_match('/grade [1-7]|primary|book [1-7](?![0-9])/i', $title)) {
            return 'Primary';
        }

        // O-Level / Form 1-4
        if (preg_match('/form [1-4]|o[\s\-\']*level|ordinary level|igcse/i', $title)) {
            return 'O-Level';
        }

        // A-Level / Form 5-6
        if (preg_match('/form [5-6]|a[\s\-\']*level|advanced level|as\s+level/i', $title)) {
            return 'A-Level';
        }

        // Cambridge International
        if (preg_match('/cambridge|checkpoint/i', $title)) {
            return 'Cambridge';
        }

        // Tertiary
        if (preg_match('/university|college|degree|diploma/i', $title)) {
            return 'Tertiary';
        }

        return 'Other';
    }

    /**
     * Determine subject from title
     */
    private function determineSubject(string $title): ?string
    {
        $title = strtolower($title);

        $subjects = [
            'Mathematics' => ['mathematics', 'maths', 'math'],
            'English' => ['english'],
            'Science' => ['science', 'biology', 'chemistry', 'physics', 'combined science'],
            'History' => ['history', 'heritage'],
            'Geography' => ['geography'],
            'Commerce' => ['commerce', 'business', 'accounting', 'economics'],
            'Agriculture' => ['agriculture', 'agric', 'farming', 'poultry', 'livestock', 'crop'],
            'Shona' => ['shona', 'chiShona'],
            'Ndebele' => ['ndebele', 'isindebele'],
            'French' => ['french'],
            'Computer Science' => ['computer', 'ict', 'information technology'],
            'Design & Technology' => ['textile', 'metal', 'wood', 'technology', 'design', 'technical drawing'],
            'Food & Nutrition' => ['food', 'nutrition'],
            'Physical Education' => ['physical education', 'pe', 'sport'],
            'Art & Design' => ['art', 'design'],
            'Religious Studies' => ['religious', 'bible'],
        ];

        foreach ($subjects as $subject => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return $subject;
                }
            }
        }

        return null;
    }

    /**
     * Determine syllabus from title
     */
    private function determineSyllabus(string $title): string
    {
        $title = strtolower($title);

        if (str_contains($title, 'cambridge') || str_contains($title, 'igcse')) {
            return 'Cambridge';
        }

        if (str_contains($title, 'zimsec') || str_contains($title, 'zimbabwe')) {
            return 'ZIMSEC';
        }

        return 'General';
    }

    /**
     * Determine publisher from title or author
     */
    private function determinePublisher(string $title, string $author): ?string
    {
        $title = strtolower($title);

        $publishers = [
            'College Press' => 'college press',
            'Longman' => 'longman',
            'Cambridge University Press' => 'cambridge',
            'Oxford University Press' => 'oxford',
            'Literature Bureau' => 'literature bureau',
            'Secondary Book Press' => 'secondary book press',
            'Step Ahead Publishers' => 'step ahead',
            'Practical Approach Publishers' => 'practical approach',
            'New Trends Publishers' => 'new trends',
            'Gramsol Publishers' => 'gramsol',
        ];

        foreach ($publishers as $publisher => $keyword) {
            if (str_contains($title, $keyword)) {
                return $publisher;
            }
        }

        return null;
    }

    /**
     * Determine pricing based on level and type
     */
    private function determinePricing(string $level, string $title): array
    {
        $title = strtolower($title);

        // Base prices by level (in USD)
        $basePrices = [
            'Primary' => 8.00,
            'O-Level' => 12.00,
            'A-Level' => 15.00,
            'Cambridge' => 18.00,
            'Tertiary' => 25.00,
            'Other' => 10.00,
        ];

        $baseUsd = $basePrices[$level] ?? 10.00;

        // Adjust for special types
        if (str_contains($title, 'revision') || str_contains($title, 'exam')) {
            $baseUsd *= 0.8; // Revision guides are typically cheaper
        } elseif (str_contains($title, 'dictionary') || str_contains($title, 'reference')) {
            $baseUsd *= 1.2; // Dictionaries cost more
        } elseif (str_contains($title, 'workbook')) {
            $baseUsd *= 0.7; // Workbooks are cheaper
        }

        // Convert to ZWL (approximate rate: 1 USD = 35000 ZWL as of 2025)
        $zwlRate = 35000;

        return [
            'usd' => round($baseUsd, 2),
            'zwl' => round($baseUsd * $zwlRate, 2),
        ];
    }

    /**
     * Determine if book should be featured
     */
    private function shouldFeature(string $title): bool
    {
        $title = strtolower($title);

        // Feature popular series and exam materials
        $featuredKeywords = [
            'cambridge',
            'step ahead',
            'practical approach',
            'revision',
            'exam',
            'o\' level',
            'a level',
            'form 4',
            'form 6',
        ];

        foreach ($featuredKeywords as $keyword) {
            if (str_contains($title, $keyword)) {
                return rand(1, 100) <= 30; // 30% chance to feature
            }
        }

        return false;
    }

    /**
     * Create a rich description for the book
     */
    private function createRichDescription(string $title, string $author, array $enriched): string
    {
        $parts = [];

        // Add title and author
        $parts[] = "{$title}" . ($author ? " by {$author}" : "");

        // Add level and subject info
        if ($enriched['level'] && $enriched['level'] !== 'Other') {
            $levelText = $enriched['level'];
            if ($enriched['subject']) {
                $parts[] = "This {$enriched['subject']} textbook is designed for {$levelText} students.";
            } else {
                $parts[] = "Designed for {$levelText} students.";
            }
        }

        // Add syllabus info
        if ($enriched['syllabus'] && $enriched['syllabus'] !== 'General') {
            $parts[] = "Aligned with the {$enriched['syllabus']} curriculum.";
        }

        // Add special features based on title
        $titleLower = strtolower($title);
        if (str_contains($titleLower, 'revision')) {
            $parts[] = "Perfect for exam preparation and revision.";
        } elseif (str_contains($titleLower, 'workbook')) {
            $parts[] = "Includes practical exercises and activities.";
        } elseif (str_contains($titleLower, 'question')) {
            $parts[] = "Features comprehensive questions and answers.";
        }

        return implode(' ', $parts);
    }
}
