<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Category;
use App\Models\OfficeLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
            'availability' => ['nullable', 'in:available,all'],
        ]);

        $items = BookItem::query()
            ->with([
                'book.authors',
                'book.category.parent',
                'lender.officeLocation.city.country',
            ])
            ->when(($filters['availability'] ?? 'available') === 'available', function (Builder $query) {
                $query->where('status', 'available');
            })
            ->when($filters['category_id'] ?? null, function (Builder $query, int $categoryId) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('category_id', $categoryId));
            })
            ->when($filters['office_location_id'] ?? null, function (Builder $query, int $officeLocationId) {
                $query->whereHas('lender', fn (Builder $lenderQuery) => $lenderQuery->where('office_location_id', $officeLocationId));
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
                    })->orWhereHas('book.authors', function (Builder $authorQuery) use ($q) {
                        $authorQuery->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', last_name) like ?", ['%'.$q.'%']);
                    });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(function (BookItem $item): array {
                return [
                    'id' => $item->id,
                    'unique_key' => $item->unique_key,
                    'status' => $item->status,
                    'book' => [
                        'title' => $item->book->title,
                        'isbn10' => $item->book->isbn10,
                        'isbn13' => $item->book->isbn13,
                        'description' => $item->book->description,
                        'book_type' => $item->book->book_type,
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

        return Inertia::render('Catalog/Index', [
            'filters' => [
                'q' => $filters['q'] ?? '',
                'title' => $filters['title'] ?? '',
                'author' => $filters['author'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
                'office_location_id' => $filters['office_location_id'] ?? '',
                'availability' => $filters['availability'] ?? 'available',
            ],
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'officeLocations' => OfficeLocation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
