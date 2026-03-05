<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Category;
use App\Models\OfficeLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'office_location_id' => ['nullable', 'integer', 'exists:office_locations,id'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'book_type' => ['nullable', 'in:hard_copy,online'],
            'availability' => ['nullable', 'in:available,all'],
        ]);

        $query = BookItem::query()
            ->when(($filters['availability'] ?? 'all') === 'available', function (Builder $query) {
                $query->where('status', 'available');
            })
            ->when($filters['category_id'] ?? null, function (Builder $query, int $categoryId) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('category_id', $categoryId));
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
                'book.category.parent',
                'book.language',
                'lender.officeLocation.city.country',
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(function (BookItem $item): array {
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
                        'authors' => $item->book->authors->map(fn ($author) => $author->display_name)->values(),
                    ],
                    'lender' => [
                        'id' => $item->lender->id,
                        'name' => $item->lender->display_name,
                        'office_location' => optional($item->lender->officeLocation)->name,
                    ],
                ];
            });

        $facetCategories = (clone $query)
            ->join('books', 'books.id', '=', 'book_items.book_id')
            ->leftJoin('categories', 'categories.id', '=', 'books.category_id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->orderBy('categories.name')
            ->get();

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
                'category_id' => $filters['category_id'] ?? '',
                'office_location_id' => $filters['office_location_id'] ?? '',
                'language_id' => $filters['language_id'] ?? '',
                'book_type' => $filters['book_type'] ?? '',
                'availability' => $filters['availability'] ?? 'all',
            ],
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'officeLocations' => OfficeLocation::query()->orderBy('name')->get(['id', 'name']),
            'facets' => [
                'categories' => $facetCategories,
                'office_locations' => $facetOffices,
                'languages' => $facetLanguages,
                'book_types' => $facetBookTypes,
            ],
        ]);
    }
}
