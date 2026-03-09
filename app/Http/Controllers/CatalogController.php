<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Loan;
use App\Models\OfficeLocation;
use App\Models\WaitingListEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canModerate = $user && ($user->is_administrator || $user->is_site_owner);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'category_1_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_2_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_3_id' => ['nullable', 'integer', 'exists:categories,id'],
            'office_location_id' => ['nullable', 'integer', 'exists:office_locations,id'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'book_type' => ['nullable', 'in:hard_copy,online'],
            'availability' => ['nullable', 'in:available,all'],
        ]);
        $normalizedCategory = $this->normalizeCategoryValue($filters['category'] ?? null);

        $requestedBookItemLookup = [];
        $waitlistBookLookup = [];
        if ($user) {
            $requestedBookItemLookup = Loan::query()
                ->where('borrower_id', $user->id)
                ->whereIn('status', ['requested', 'approved', 'shared', 'borrowed'])
                ->pluck('book_item_id')
                ->flip()
                ->all();

            $waitlistBookLookup = WaitingListEntry::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['waiting', 'notified'])
                ->pluck('book_id')
                ->flip()
                ->all();
        }

        $query = BookItem::query()
            ->when(! $canModerate, fn (Builder $query) => $query->where('status', '!=', 'pending_verification'))
            ->when(($filters['availability'] ?? 'all') === 'available', function (Builder $query) {
                $query->where('status', 'available');
            })
            ->when($normalizedCategory, function (Builder $query, string $categoryName) {
                $query->whereHas('book.category', function (Builder $categoryQuery) use ($categoryName) {
                    $categoryQuery->whereRaw('LOWER(TRIM(REPLACE(name, ?, " "))) = ?', ["\xC2\xA0", $categoryName]);
                });
            })
            ->when($filters['category_1_id'] ?? null, function (Builder $query, int $category1Id) {
                $query->whereHas('book.category', function (Builder $categoryQuery) use ($category1Id) {
                    $categoryQuery
                        ->where('id', $category1Id)
                        ->orWhere('parent_id', $category1Id)
                        ->orWhereHas('parent', fn (Builder $parentQuery) => $parentQuery->where('parent_id', $category1Id));
                });
            })
            ->when($filters['category_2_id'] ?? null, function (Builder $query, int $category2Id) {
                $query->whereHas('book.category', function (Builder $categoryQuery) use ($category2Id) {
                    $categoryQuery
                        ->where('id', $category2Id)
                        ->orWhere('parent_id', $category2Id);
                });
            })
            ->when($filters['category_3_id'] ?? null, function (Builder $query, int $category3Id) {
                $query->whereHas('book.category', fn (Builder $categoryQuery) => $categoryQuery->where('id', $category3Id));
            })
            ->when($filters['office_location_id'] ?? null, function (Builder $query, int $officeLocationId) {
                $query->whereHas('lender', fn (Builder $lenderQuery) => $lenderQuery->where('office_location_id', $officeLocationId));
            })
            ->when($filters['language_id'] ?? null, function (Builder $query, int $languageId) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('language_id', $languageId));
            })
            ->when($filters['book_type'] ?? null, function (Builder $query, string $bookType) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('book_type', $bookType));
            })
            ->when($filters['title'] ?? null, function (Builder $query, string $title) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('title', 'like', '%'.$title.'%'));
            })
            ->when($filters['author'] ?? null, function (Builder $query, string $author) {
                $query->whereHas('book.authors', function (Builder $authorQuery) use ($author) {
                    $authorQuery->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', last_name) like ?", ['%'.$author.'%']);
                });
            })
            ->when($filters['q'] ?? null, function (Builder $query, string $q) {
                $query->where(function (Builder $nested) use ($q) {
                    $nested->whereHas('book', function (Builder $bookQuery) use ($q) {
                        $bookQuery->where('title', 'like', '%'.$q.'%')
                            ->orWhere('isbn10', 'like', '%'.$q.'%')
                            ->orWhere('isbn13', 'like', '%'.$q.'%');
                        $bookQuery->orWhere('description', 'like', '%'.$q.'%')
                            ->orWhere('book_type', 'like', '%'.$q.'%');
                    })->orWhereHas('book.authors', function (Builder $authorQuery) use ($q) {
                        $authorQuery->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', last_name) like ?", ['%'.$q.'%']);
                    });
                });
            });

        $items = (clone $query)
            ->with([
                'book.authors',
                'book.category.parent.parent',
                'book.language',
                'lender.officeLocation.city.country',
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(function (BookItem $item) use ($requestedBookItemLookup, $waitlistBookLookup): array {
                $hasRequested = isset($requestedBookItemLookup[$item->id]);
                $onWaitlist = isset($waitlistBookLookup[$item->book_id]);
                $categoryTier1 = null;
                $categoryTier2 = null;
                $categoryTier3 = null;
                $cursor = $item->book->category;

                while ($cursor) {
                    if ($cursor->tier === 1 && ! $categoryTier1) {
                        $categoryTier1 = $cursor->name;
                    } elseif ($cursor->tier === 2 && ! $categoryTier2) {
                        $categoryTier2 = $cursor->name;
                    } elseif ($cursor->tier === 3 && ! $categoryTier3) {
                        $categoryTier3 = $cursor->name;
                    }

                    $cursor = $cursor->parent;
                }

                return [
                    'id' => $item->id,
                    'unique_key' => $item->unique_key,
                    'status' => $item->status,
                    'lender_comments' => $item->lender_comments,
                    'book' => [
                        'title' => $item->book->title,
                        'isbn10' => $item->book->isbn10,
                        'isbn13' => $item->book->isbn13,
                        'description' => $item->book->description,
                        'book_type' => $item->book->book_type,
                        'language' => optional($item->book->language)->name,
                        'category' => optional($item->book->category)->name,
                        'category_tier_1' => $categoryTier1,
                        'category_tier_2' => $categoryTier2,
                        'category_tier_3' => $categoryTier3,
                        'authors' => $item->book->authors->map(fn ($author) => $author->display_name)->values(),
                    ],
                    'lender' => [
                        'id' => $item->lender->id,
                        'name' => $item->lender->display_name,
                        'office_location' => optional($item->lender->officeLocation)->name,
                    ],
                    'user_context' => [
                        'has_requested' => $hasRequested,
                        'on_waitlist' => $onWaitlist,
                    ],
                ];
            });

        $baseCategoryRows = (clone $query)
            ->join('books', 'books.id', '=', 'book_items.book_id')
            ->leftJoin('categories as category_3', 'category_3.id', '=', 'books.category_id')
            ->leftJoin('categories as category_2', 'category_2.id', '=', 'category_3.parent_id')
            ->leftJoin('categories as category_1', 'category_1.id', '=', 'category_2.parent_id')
            ->select([
                'book_items.id as book_item_id',
                'category_1.id as category_1_id',
                'category_1.name as category_1_name',
                'category_2.id as category_2_id',
                'category_2.parent_id as category_2_parent_id',
                'category_2.name as category_2_name',
                'category_3.id as category_3_id',
                'category_3.parent_id as category_3_parent_id',
                'category_3.name as category_3_name',
            ])
            ->get();

        $facetCategories = $baseCategoryRows
            ->flatMap(function ($row) {
                $names = [];

                if ($row->category_1_name) {
                    $names[] = trim(str_replace("\xC2\xA0", ' ', $row->category_1_name));
                }
                if ($row->category_2_name) {
                    $names[] = trim(str_replace("\xC2\xA0", ' ', $row->category_2_name));
                }
                if ($row->category_3_name) {
                    $names[] = trim(str_replace("\xC2\xA0", ' ', $row->category_3_name));
                }

                return collect($names)
                    ->filter()
                    ->unique(fn (string $name) => mb_strtolower($name))
                    ->map(fn (string $name) => [
                        'book_item_id' => $row->book_item_id,
                        'name' => $name,
                        'id' => mb_strtolower($name),
                    ]);
            })
            ->groupBy('id')
            ->map(function ($rows, $id) {
                $first = $rows->first();

                return [
                    'id' => $id,
                    'name' => $first['name'],
                    'total' => $rows->pluck('book_item_id')->unique()->count(),
                ];
            })
            ->values()
            ->sortBy([
                ['total', 'desc'],
                ['name', 'asc'],
            ])
            ->values();

        $tier1Options = $baseCategoryRows
            ->filter(fn ($row) => $row->category_1_id && $row->category_1_name)
            ->map(fn ($row) => [
                'id' => $row->category_1_id,
                'name' => trim(str_replace("\xC2\xA0", ' ', $row->category_1_name)),
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        $tier2Options = $baseCategoryRows
            ->filter(fn ($row) => $row->category_2_id && $row->category_2_name)
            ->map(fn ($row) => [
                'id' => $row->category_2_id,
                'name' => trim(str_replace("\xC2\xA0", ' ', $row->category_2_name)),
                'parent_id' => $row->category_2_parent_id,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        $tier3Options = $baseCategoryRows
            ->filter(fn ($row) => $row->category_3_id && $row->category_3_name)
            ->map(fn ($row) => [
                'id' => $row->category_3_id,
                'name' => trim(str_replace("\xC2\xA0", ' ', $row->category_3_name)),
                'parent_id' => $row->category_3_parent_id,
                'parent_tier1_id' => $row->category_2_parent_id,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();

        $facetOffices = (clone $query)
            ->join('users', 'users.id', '=', 'book_items.lender_id')
            ->leftJoin('office_locations', 'office_locations.id', '=', 'users.office_location_id')
            ->select('office_locations.id', 'office_locations.name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('office_locations.id')
            ->groupBy('office_locations.id', 'office_locations.name')
            ->orderByDesc('total')
            ->orderBy('office_locations.name')
            ->get();

        $facetLanguages = (clone $query)
            ->join('books', 'books.id', '=', 'book_items.book_id')
            ->leftJoin('languages', 'languages.id', '=', 'books.language_id')
            ->select('languages.id', 'languages.name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('languages.id')
            ->groupBy('languages.id', 'languages.name')
            ->orderByDesc('total')
            ->orderBy('languages.name')
            ->get();

        $facetBookTypes = (clone $query)
            ->join('books', 'books.id', '=', 'book_items.book_id')
            ->select('books.book_type', DB::raw('COUNT(*) as total'))
            ->groupBy('books.book_type')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Catalog/Index', [
            'filters' => [
                'q' => $filters['q'] ?? '',
                'title' => $filters['title'] ?? '',
                'author' => $filters['author'] ?? '',
                'category' => $filters['category'] ?? '',
                'category_1_id' => $filters['category_1_id'] ?? '',
                'category_2_id' => $filters['category_2_id'] ?? '',
                'category_3_id' => $filters['category_3_id'] ?? '',
                'office_location_id' => $filters['office_location_id'] ?? '',
                'language_id' => $filters['language_id'] ?? '',
                'book_type' => $filters['book_type'] ?? '',
                'availability' => $filters['availability'] ?? 'all',
            ],
            'items' => $items,
            'categoryTier1' => $tier1Options,
            'categoryTier2' => $tier2Options,
            'categoryTier3' => $tier3Options,
            'officeLocations' => OfficeLocation::query()->orderBy('name')->get(['id', 'name']),
            'facets' => [
                'categories' => $facetCategories,
                'office_locations' => $facetOffices,
                'languages' => $facetLanguages,
                'book_types' => $facetBookTypes,
            ],
        ]);
    }

    private function normalizeCategoryValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = str_replace("\xC2\xA0", ' ', $value);
        $clean = mb_strtolower(trim($clean));

        return $clean !== '' ? $clean : null;
    }
}
