<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Category;
use App\Models\Language;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookItemController extends Controller
{
    private const DRY_RUN_ROLLBACK_SIGNAL = '__DRY_RUN_ROLLBACK__';

    public function index(): Response
    {
        $items = BookItem::query()
            ->with(['book.authors', 'book.category'])
            ->where('lender_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn (BookItem $item) => [
                'id' => $item->id,
                'unique_key' => $item->unique_key,
                'status' => $item->status,
                'expected_return_date' => $item->expected_return_date?->toDateString(),
                'verified_at' => $item->verified_at?->toDateTimeString(),
                'book' => [
                    'title' => $item->book->title,
                    'isbn10' => $item->book->isbn10,
                    'isbn13' => $item->book->isbn13,
                    'category' => optional($item->book->category)->name,
                    'authors' => $item->book->authors->map(fn ($author) => $author->display_name)->values(),
                ],
            ]);

        return Inertia::render('Books/MyBooks', [
            'items' => $items,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Books/Create', [
            'categoryTier1' => Category::query()->where('tier', 1)->orderBy('name')->get(['id', 'name']),
            'categoryTier2' => Category::query()->where('tier', 2)->orderBy('name')->get(['id', 'name', 'parent_id']),
            'categoryTier3' => Category::query()->where('tier', 3)->orderBy('name')->get(['id', 'name', 'parent_id']),
            'languages' => Language::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Request $request, BookItem $bookItem): Response
    {
        $this->authorizeBookItemEdit($request->user(), $bookItem);

        $bookItem->load(['book.authors', 'book.category.parent.parent', 'book.language']);
        $categoryTierIds = $this->extractCategoryTierIds($bookItem->book->category);

        return Inertia::render('Books/Edit', [
            'categoryTier1' => Category::query()->where('tier', 1)->orderBy('name')->get(['id', 'name']),
            'categoryTier2' => Category::query()->where('tier', 2)->orderBy('name')->get(['id', 'name', 'parent_id']),
            'categoryTier3' => Category::query()->where('tier', 3)->orderBy('name')->get(['id', 'name', 'parent_id']),
            'languages' => Language::query()->orderBy('name')->get(['id', 'name']),
            'item' => [
                'id' => $bookItem->id,
                'status' => $bookItem->status,
                'unique_key' => $bookItem->unique_key,
                'expected_return_date' => $bookItem->expected_return_date?->toDateString(),
                'lender_comments' => $bookItem->lender_comments,
                'book' => [
                    'title' => $bookItem->book->title,
                    'isbn10' => $bookItem->book->isbn10,
                    'isbn13' => $bookItem->book->isbn13,
                    'description' => $bookItem->book->description,
                    'book_type' => $bookItem->book->book_type,
                    'category_1_id' => $categoryTierIds['category_1_id'],
                    'category_2_id' => $categoryTierIds['category_2_id'],
                    'category_3_id' => $categoryTierIds['category_3_id'],
                    'language_id' => $bookItem->book->language_id,
                    'language_name' => optional($bookItem->book->language)->name,
                    'authors' => $bookItem->book->authors
                        ->map(fn ($author) => ['first_name' => $author->first_name, 'last_name' => $author->last_name])
                        ->values()
                        ->all(),
                ],
            ],
        ]);
    }

    public function pendingVerification(Request $request): Response
    {
        abort_unless($request->user()->is_administrator || $request->user()->is_site_owner, 403);

        $items = BookItem::query()
            ->with(['book.authors', 'book.category', 'book.language', 'lender.officeLocation'])
            ->where('status', 'pending_verification')
            ->latest()
            ->get()
            ->map(fn (BookItem $item) => [
                'id' => $item->id,
                'unique_key' => $item->unique_key,
                'created_at' => $item->created_at?->toDateTimeString(),
                'book' => [
                    'title' => $item->book->title,
                    'isbn10' => $item->book->isbn10,
                    'isbn13' => $item->book->isbn13,
                    'description' => $item->book->description,
                    'book_type' => $item->book->book_type,
                    'language' => optional($item->book->language)->name,
                    'category' => optional($item->book->category)->name,
                    'authors' => $item->book->authors->map(fn ($author) => $author->display_name)->values(),
                ],
                'lender' => [
                    'name' => $item->lender->display_name,
                    'employee_id' => $item->lender->employee_id,
                    'office_location' => optional($item->lender->officeLocation)->name,
                ],
                'lender_comments' => $item->lender_comments,
            ]);

        return Inertia::render('Books/PendingVerification', [
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'isbn10' => ['nullable', 'string', 'size:10'],
            'isbn13' => ['nullable', 'string', 'size:13'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'book_type' => ['required', 'in:hard_copy,online'],
            'category_1_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_1_name' => ['nullable', 'string', 'max:255'],
            'category_2_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_2_name' => ['nullable', 'string', 'max:255'],
            'category_3_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_3_name' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'language_name' => ['nullable', 'string', 'max:120'],
            'lender_comments' => ['nullable', 'string'],
            'expected_return_date' => ['nullable', 'date'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*.first_name' => ['nullable', 'string', 'max:120'],
            'authors.*.last_name' => ['required', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $category = $this->resolveCategoryFromSelection($validated);
        $language = $this->resolveLanguageFromSelection($validated);

        DB::transaction(function () use ($validated, $user, $category, $language): void {
            $book = Book::query()->create([
                'isbn10' => $validated['isbn10'] ?? null,
                'isbn13' => $validated['isbn13'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'book_type' => $validated['book_type'],
                'category_id' => $category?->id,
                'language_id' => $language?->id,
            ]);

            $authorIds = collect($validated['authors'])
                ->map(function (array $authorData): int {
                    return Author::query()->firstOrCreate([
                        'first_name' => $authorData['first_name'] ?? null,
                        'last_name' => $authorData['last_name'],
                    ])->id;
                })
                ->unique()
                ->values()
                ->all();

            $book->authors()->sync($authorIds);

            $identifier = $user->employee_id ?: (string) $user->id;
            $isbnPart = $validated['isbn13'] ?? $validated['isbn10'] ?? (string) $book->id;

            BookItem::query()->create([
                'book_id' => $book->id,
                'lender_id' => $user->id,
                'unique_key' => $identifier.'-'.$isbnPart,
                'lender_comments' => $validated['lender_comments'] ?? null,
                'status' => 'pending_verification',
                'expected_return_date' => $validated['expected_return_date'] ?? null,
            ]);
        });

        return redirect()->route('books.mine')->with('success', 'Book submitted for librarian verification.');
    }

    public function update(Request $request, BookItem $bookItem): RedirectResponse
    {
        $this->authorizeBookItemEdit($request->user(), $bookItem);

        $validated = $request->validate([
            'isbn10' => ['nullable', 'string', 'size:10'],
            'isbn13' => ['nullable', 'string', 'size:13'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'book_type' => ['required', 'in:hard_copy,online'],
            'category_1_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_1_name' => ['nullable', 'string', 'max:255'],
            'category_2_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_2_name' => ['nullable', 'string', 'max:255'],
            'category_3_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_3_name' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'language_name' => ['nullable', 'string', 'max:120'],
            'lender_comments' => ['nullable', 'string'],
            'expected_return_date' => ['nullable', 'date'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*.first_name' => ['nullable', 'string', 'max:120'],
            'authors.*.last_name' => ['required', 'string', 'max:120'],
        ]);
        $category = $this->resolveCategoryFromSelection($validated);
        $language = $this->resolveLanguageFromSelection($validated);

        DB::transaction(function () use ($validated, $bookItem, $category, $language): void {
            $bookItem->book()->update([
                'isbn10' => $validated['isbn10'] ?? null,
                'isbn13' => $validated['isbn13'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'book_type' => $validated['book_type'],
                'category_id' => $category?->id,
                'language_id' => $language?->id,
            ]);

            $authorIds = collect($validated['authors'])
                ->map(function (array $authorData): int {
                    return Author::query()->firstOrCreate([
                        'first_name' => $authorData['first_name'] ?? null,
                        'last_name' => $authorData['last_name'],
                    ])->id;
                })
                ->unique()
                ->values()
                ->all();

            $bookItem->book->authors()->sync($authorIds);

            $bookItem->update([
                'lender_comments' => $validated['lender_comments'] ?? null,
                'expected_return_date' => $validated['expected_return_date'] ?? null,
            ]);
        });

        return redirect()->route('books.mine')->with('success', 'Book updated successfully.');
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $isDryRun = (bool) ($validated['dry_run'] ?? false);
        try {
            $rows = $this->readDelimitedFile($validated['csv_file']);
        } catch (\Throwable $e) {
            return back()->with('error', 'CSV could not be read. Please verify file encoding and delimiter format.');
        }

        if ($rows === []) {
            return back()->with('warning', 'No rows found in the uploaded file.');
        }

        $imported = 0;
        $failed = 0;
        $processedRows = 0;
        $errors = [];
        $previewRows = [];

        foreach ($rows as $index => $row) {
            if (! $this->hasImportableData($row)) {
                continue;
            }
            $processedRows++;

            try {
                DB::transaction(function () use ($row, $request, $isDryRun): void {
                    $this->importCsvRow($row, $request->user());
                    if ($isDryRun) {
                        throw new \RuntimeException(self::DRY_RUN_ROLLBACK_SIGNAL);
                    }
                });
                $imported++;
            } catch (\Throwable $e) {
                if ($isDryRun && $e->getMessage() === self::DRY_RUN_ROLLBACK_SIGNAL) {
                    $imported++;
                } else {
                    $failed++;
                    $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
                }
            }

            if (count($previewRows) < 20) {
                $previewRows[] = [
                    'row' => $index + 2,
                    'isbn_emp' => $this->csvValue($row, 'isbn_emp'),
                    'title' => $this->csvValue($row, 'title'),
                    'author' => $this->csvValue($row, 'author'),
                    'lender_id' => $this->csvValue($row, 'lender_id'),
                    'status' => $this->mapItemStatus($this->csvValue($row, 'status')),
                    'offices' => $this->parseOfficeLocations($this->csvValue($row, 'office_locations')),
                ];
            }
        }

        $action = $isDryRun ? 'Dry run complete.' : 'CSV import complete.';
        $message = "{$action} Imported: {$imported}. Failed: {$failed}.";
        if ($errors !== []) {
            $message .= ' '.implode(' ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' (Additional row errors omitted.)';
            }
        }

        if ($processedRows === 0) {
            $message = 'No importable rows were detected. Make sure the template columns are present (Title or ISBN is required).';
        }

        $flashType = 'success';
        if ($processedRows === 0 || ($imported === 0 && $failed > 0)) {
            $flashType = 'error';
        } elseif ($failed > 0) {
            $flashType = 'warning';
        }

        return back()
            ->with($flashType, $message)
            ->with('import_preview', [
                'is_dry_run' => $isDryRun,
                'imported' => $imported,
                'failed' => $failed,
                'processed_rows' => $processedRows,
                'errors' => array_slice($errors, 0, 10),
                'sample_rows' => $previewRows,
            ]);
    }

    public function downloadImportTemplate(): HttpResponse
    {
        $headers = [
            'ISBN-Emp',
            'ISBN',
            'Title',
            'Author',
            'Language',
            'Book Type',
            'Category 1',
            'Category 2',
            'Category 3',
            'Description',
            'Lender Comments',
            'Lender ID',
            'Office Locations',
            'Status',
        ];

        $sampleRow = [
            '306847221-451776',
            '306847221',
            'The Wake Up: Closing the Gap Between Good Intentions and Real Change',
            'Kim, Michelle Mijung',
            'English',
            'Hard Copy',
            'Nonfiction',
            'Social Justice',
            'Race',
            'Foundational DEI principles and practical frameworks.',
            '',
            '451776',
            'OCB, CCB, JAB',
            'available',
        ];

        $toCsv = static fn (array $values): string => collect($values)->map(function (?string $value): string {
            $escaped = str_replace('"', '""', (string) ($value ?? ''));

            return '"'.$escaped.'"';
        })->implode(',');

        $content = $toCsv($headers).PHP_EOL.$toCsv($sampleRow).PHP_EOL;

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="check-it-out-import-template.csv"',
        ]);
    }

    public function verify(BookItem $bookItem): RedirectResponse
    {
        abort_unless(auth()->user()->is_administrator || auth()->user()->is_site_owner, 403);

        $bookItem->update([
            'status' => 'available',
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Book verified and available in catalog.');
    }

    public function verifyBulk(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_administrator || $request->user()->is_site_owner, 403);

        $validated = $request->validate([
            'book_item_ids' => ['required', 'array', 'min:1'],
            'book_item_ids.*' => ['integer', 'exists:book_items,id'],
        ]);

        $itemIds = collect($validated['book_item_ids'])->unique()->values()->all();

        $updated = BookItem::query()
            ->whereIn('id', $itemIds)
            ->where('status', 'pending_verification')
            ->update([
                'status' => 'available',
                'verified_at' => now(),
                'removed_at' => null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return back()->with('warning', 'No selected books were pending verification.');
        }

        return back()->with('success', "Verified {$updated} selected book(s).");
    }

    public function remove(BookItem $bookItem): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            $bookItem->lender_id === $user->id || $user->is_administrator || $user->is_site_owner,
            403
        );
        abort_if($this->hasActiveLoan($bookItem), 422, 'Cannot remove a book with an active loan.');

        $bookItem->update([
            'status' => 'removed',
            'removed_at' => now(),
        ]);

        return back()->with('success', 'Book removed.');
    }

    public function markPending(BookItem $bookItem): RedirectResponse
    {
        abort_unless(auth()->user()->is_administrator || auth()->user()->is_site_owner, 403);
        abort_if($this->hasActiveLoan($bookItem), 422, 'Cannot move to pending verification while the book has an active loan.');

        $bookItem->update([
            'status' => 'pending_verification',
            'verified_at' => null,
            'removed_at' => null,
        ]);

        return back()->with('success', 'Book moved to pending verification.');
    }

    public function reshelve(BookItem $bookItem): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            $bookItem->lender_id === $user->id || $user->is_administrator || $user->is_site_owner,
            403
        );

        $bookItem->update([
            'status' => 'available',
            'removed_at' => null,
        ]);

        return back()->with('success', 'Book reshelved and available.');
    }

    private function authorizeBookItemEdit(User $user, BookItem $bookItem): void
    {
        abort_unless(
            $bookItem->lender_id === $user->id || $user->is_administrator || $user->is_site_owner,
            403
        );
    }

    private function hasActiveLoan(BookItem $bookItem): bool
    {
        return $bookItem->loans()
            ->whereIn('status', ['requested', 'approved', 'shared', 'borrowed'])
            ->exists();
    }

    private function hasImportableData(array $row): bool
    {
        return $this->csvValue($row, 'title') !== null || $this->csvValue($row, 'isbn') !== null;
    }

    private function importCsvRow(array $row, User $defaultUser): void
    {
        $title = $this->csvValue($row, 'title');
        if (! $title) {
            throw new \RuntimeException('Missing required Title.');
        }

        $isbn = $this->cleanIsbn($this->csvValue($row, 'isbn'));
        $isbn10 = $isbn && strlen($isbn) === 10 ? $isbn : null;
        $isbn13 = $isbn && strlen($isbn) === 13 ? $isbn : null;

        $category = $this->resolveCategory(
            $this->csvValue($row, 'category_1'),
            $this->csvValue($row, 'category_2'),
            $this->csvValue($row, 'category_3')
        );

        $language = null;
        $languageName = $this->csvValue($row, 'language');
        if ($languageName) {
            $language = Language::query()->firstOrCreate(['name' => $languageName]);
        }

        $book = $this->resolveBook(
            title: $title,
            isbn10: $isbn10,
            isbn13: $isbn13,
            bookType: $this->mapBookType($this->csvValue($row, 'book_type')),
            description: $this->csvValue($row, 'description'),
            categoryId: $category?->id,
            languageId: $language?->id
        );

        $authors = $this->parseAuthors($this->csvValue($row, 'author'));
        if ($authors !== []) {
            $authorIds = collect($authors)
                ->map(function (array $author): int {
                    return Author::query()->firstOrCreate([
                        'first_name' => $author['first_name'] ?: null,
                        'last_name' => $author['last_name'],
                    ])->id;
                })
                ->unique()
                ->values()
                ->all();

            $book->authors()->syncWithoutDetaching($authorIds);
        }

        $canAssignLenderFromCsv = $defaultUser->is_administrator || $defaultUser->is_site_owner;
        $lender = $this->resolveLender($this->csvValue($row, 'lender_id'), $defaultUser, $canAssignLenderFromCsv);
        if (! $lender->is_lender) {
            $lender->update(['is_lender' => true]);
        }

        $officeNames = $this->parseOfficeLocations($this->csvValue($row, 'office_locations'));
        if ($officeNames !== []) {
            $officeIds = collect($officeNames)->map(function (string $name): int {
                return $this->resolveOrCreateOffice($name)->id;
            })->values()->all();

            if (! $lender->office_location_id) {
                $lender->update(['office_location_id' => $officeIds[0]]);
            }

            $lender->shareLocations()->syncWithoutDetaching($officeIds);
        }

        $preferredUniqueKey = $this->csvValue($row, 'isbn_emp')
            ?? (($lender->employee_id ?: (string) $lender->id).'-'.($isbn13 ?? $isbn10 ?? Str::slug($title)));

        $itemStatus = $this->mapItemStatus($this->csvValue($row, 'status'));

        $bookItem = BookItem::query()->firstOrNew([
            'book_id' => $book->id,
            'lender_id' => $lender->id,
        ]);

        if (! $bookItem->exists) {
            $bookItem->unique_key = $this->ensureUniqueKey($preferredUniqueKey);
        }

        $bookItem->lender_comments = $this->csvValue($row, 'lender_comments');
        $bookItem->status = $itemStatus;
        $bookItem->verified_at = $itemStatus === 'available'
            ? ($bookItem->verified_at ?? now())
            : null;
        $bookItem->removed_at = $itemStatus === 'removed' ? ($bookItem->removed_at ?? now()) : null;
        $bookItem->save();
    }

    private function resolveBook(
        string $title,
        ?string $isbn10,
        ?string $isbn13,
        string $bookType,
        ?string $description,
        ?int $categoryId,
        ?int $languageId
    ): Book {
        $query = Book::query();

        if ($isbn13 || $isbn10) {
            $query->where(function ($nested) use ($isbn10, $isbn13): void {
                if ($isbn13) {
                    $nested->where('isbn13', $isbn13);
                }

                if ($isbn10) {
                    $isbn13 ? $nested->orWhere('isbn10', $isbn10) : $nested->where('isbn10', $isbn10);
                }
            });
        } else {
            $query->where('title', $title);
        }

        $book = $query->first();

        if (! $book) {
            return Book::query()->create([
                'isbn10' => $isbn10,
                'isbn13' => $isbn13,
                'title' => $title,
                'book_type' => $bookType,
                'description' => $description,
                'category_id' => $categoryId,
                'language_id' => $languageId,
            ]);
        }

        $book->update([
            'isbn10' => $book->isbn10 ?: $isbn10,
            'isbn13' => $book->isbn13 ?: $isbn13,
            'title' => $title,
            'book_type' => $bookType,
            'description' => $description ?: $book->description,
            'category_id' => $categoryId ?: $book->category_id,
            'language_id' => $languageId ?: $book->language_id,
        ]);

        return $book;
    }

    private function resolveCategory(?string $tier1, ?string $tier2, ?string $tier3): ?Category
    {
        $parent = null;
        $last = null;

        foreach ([$tier1, $tier2, $tier3] as $index => $name) {
            $cleanName = $name ? trim($name) : null;
            if (! $cleanName) {
                continue;
            }

            $last = Category::query()->firstOrCreate(
                ['name' => $cleanName, 'parent_id' => $parent?->id],
                ['tier' => $index + 1]
            );

            $parent = $last;
        }

        return $last;
    }

    private function resolveCategoryFromSelection(array $validated): ?Category
    {
        $hasTier1 = $this->hasTierInput($validated, 1);
        $hasTier2 = $this->hasTierInput($validated, 2);
        $hasTier3 = $this->hasTierInput($validated, 3);

        if ($hasTier2 && ! $hasTier1) {
            throw ValidationException::withMessages([
                'category_1_id' => 'Category 1 must be selected or entered before Category 2.',
            ]);
        }

        if ($hasTier3 && ! $hasTier2) {
            throw ValidationException::withMessages([
                'category_2_id' => 'Category 2 must be selected or entered before Category 3.',
            ]);
        }

        $tier1 = $this->resolveCategoryTierNode(
            1,
            null,
            $validated['category_1_id'] ?? null,
            $validated['category_1_name'] ?? null
        );
        $tier2 = $this->resolveCategoryTierNode(
            2,
            $tier1,
            $validated['category_2_id'] ?? null,
            $validated['category_2_name'] ?? null
        );
        $tier3 = $this->resolveCategoryTierNode(
            3,
            $tier2,
            $validated['category_3_id'] ?? null,
            $validated['category_3_name'] ?? null
        );

        return $tier3 ?? $tier2 ?? $tier1;
    }

    private function hasTierInput(array $validated, int $tier): bool
    {
        return ! empty($validated["category_{$tier}_id"]) || ! empty(trim((string) ($validated["category_{$tier}_name"] ?? '')));
    }

    private function resolveCategoryTierNode(int $tier, ?Category $parent, ?int $categoryId, ?string $categoryName): ?Category
    {
        $cleanName = trim((string) ($categoryName ?? ''));
        $cleanName = $cleanName !== '' ? $cleanName : null;

        if (! $categoryId && ! $cleanName) {
            return null;
        }

        if ($categoryId) {
            $category = Category::query()->findOrFail($categoryId);
            if ((int) $category->tier !== $tier) {
                throw ValidationException::withMessages([
                    "category_{$tier}_id" => "Selected Category {$tier} is invalid.",
                ]);
            }

            $expectedParentId = $parent?->id;
            if ((int) ($category->parent_id ?? 0) !== (int) ($expectedParentId ?? 0)) {
                throw ValidationException::withMessages([
                    "category_{$tier}_id" => "Selected Category {$tier} does not match the selected parent category.",
                ]);
            }

            return $category;
        }

        return Category::query()->firstOrCreate(
            ['name' => $cleanName, 'parent_id' => $parent?->id],
            ['tier' => $tier]
        );
    }

    private function resolveLanguageFromSelection(array $validated): ?Language
    {
        if (! empty($validated['language_id'])) {
            return Language::query()->find($validated['language_id']);
        }

        $languageName = trim((string) ($validated['language_name'] ?? ''));
        if ($languageName === '') {
            return null;
        }

        return Language::query()->firstOrCreate(['name' => $languageName]);
    }

    private function extractCategoryTierIds(?Category $category): array
    {
        $result = [
            'category_1_id' => null,
            'category_2_id' => null,
            'category_3_id' => null,
        ];

        $cursor = $category;
        while ($cursor) {
            if ((int) $cursor->tier === 1) {
                $result['category_1_id'] = $cursor->id;
            } elseif ((int) $cursor->tier === 2) {
                $result['category_2_id'] = $cursor->id;
            } elseif ((int) $cursor->tier === 3) {
                $result['category_3_id'] = $cursor->id;
            }

            $cursor = $cursor->parent;
        }

        return $result;
    }

    private function resolveLender(?string $employeeId, User $fallbackUser, bool $canAssignFromCsv): User
    {
        if (! $canAssignFromCsv) {
            return $fallbackUser;
        }

        if (! $employeeId) {
            return $fallbackUser;
        }

        $existing = User::query()->where('employee_id', $employeeId)->first();
        if ($existing) {
            return $existing;
        }

        $baseEmail = 'emp-'.$employeeId.'@import.local';
        $email = $baseEmail;
        $counter = 2;

        while (User::query()->where('email', $email)->exists()) {
            $email = 'emp-'.$employeeId.'+'.$counter.'@import.local';
            $counter++;
        }

        return User::query()->create([
            'name' => 'Employee '.$employeeId,
            'employee_id' => $employeeId,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'is_lender' => true,
            'is_borrower' => true,
            'agree_lender_guidelines' => true,
            'agree_borrower_guidelines' => true,
        ]);
    }

    private function resolveOrCreateOffice(string $name): OfficeLocation
    {
        $cleanName = trim($name);

        return OfficeLocation::query()->firstOrCreate(
            ['name' => $cleanName],
            [
                'is_virtual' => strtolower($cleanName) === 'degreed',
                'is_active' => true,
            ]
        );
    }

    private function parseAuthors(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $chunks = array_filter(array_map('trim', explode(';', $value)));
        if ($chunks === []) {
            $chunks = [trim($value)];
        }

        return collect($chunks)->map(function (string $author): ?array {
            if (str_contains($author, ',')) {
                $parts = array_values(array_filter(array_map('trim', explode(',', $author)), fn ($part) => $part !== ''));
                if ($parts === []) {
                    return null;
                }

                $firstName = count($parts) > 1 ? array_pop($parts) : '';
                $lastName = implode(', ', $parts);

                return $lastName !== ''
                    ? ['first_name' => $firstName, 'last_name' => $lastName]
                    : null;
            }

            $parts = preg_split('/\s+/', $author) ?: [];
            $lastName = array_pop($parts) ?: '';
            $firstName = trim(implode(' ', $parts));

            return $lastName !== ''
                ? ['first_name' => $firstName, 'last_name' => $lastName]
                : null;
        })->filter()->values()->all();
    }

    private function parseOfficeLocations(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function mapBookType(?string $bookType): string
    {
        $normalized = strtolower(trim((string) $bookType));
        if (in_array($normalized, ['online', 'ebook', 'e-book', 'digital'], true)) {
            return 'online';
        }

        return 'hard_copy';
    }

    private function mapItemStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'available', 'verified' => 'available',
            'loan_pending', 'requested' => 'loan_pending',
            'checked_out', 'borrowed', 'shared' => 'checked_out',
            'removed', 'inactive' => 'removed',
            default => 'pending_verification',
        };
    }

    private function ensureUniqueKey(string $candidate): string
    {
        $base = trim(preg_replace('/\s+/', '', $candidate) ?: '');
        $base = $base !== '' ? $base : 'imported-item';
        $unique = $base;
        $counter = 2;

        while (BookItem::query()->where('unique_key', $unique)->exists()) {
            $unique = $base.'-'.$counter;
            $counter++;
        }

        return $unique;
    }

    private function cleanIsbn(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^0-9X]/i', '', $value) ?: '');

        return $clean !== '' ? $clean : null;
    }

    private function csvValue(array $row, string $key): ?string
    {
        return $row[$key] ?? null;
    }

    private function readDelimitedFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $firstDataLine = null;
        while (($line = fgets($handle)) !== false) {
            $lineWithoutNbsp = str_replace("\xC2\xA0", ' ', $line);
            if (trim($lineWithoutNbsp) !== '') {
                $firstDataLine = $lineWithoutNbsp;
                break;
            }
        }

        if (! $firstDataLine) {
            fclose($handle);

            return [];
        }

        $delimiter = substr_count($firstDataLine, "\t") > substr_count($firstDataLine, ',') ? "\t" : ',';
        rewind($handle);

        $headers = [];
        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null]) {
                continue;
            }

            if ($headers === []) {
                $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $data);
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $this->cleanCell($data[$index] ?? null);
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(string $header): string
    {
        $clean = str_replace(["\xEF\xBB\xBF", "\xC2\xA0"], ['', ' '], $header);
        $clean = strtolower(trim($clean));
        $clean = preg_replace('/[^a-z0-9]+/', '_', $clean) ?? '';

        return trim($clean, '_');
    }

    private function cleanCell(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = str_replace("\xC2\xA0", ' ', $value);
        $clean = trim($clean);

        return $clean !== '' ? $clean : null;
    }
}
