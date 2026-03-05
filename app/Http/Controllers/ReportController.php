<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Loan;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $loaned = Loan::query()
            ->with(['bookItem.book.category', 'bookItem.book.authors', 'lender.officeLocation', 'borrower.officeLocation'])
            ->whereIn('status', ['borrowed', 'returned'])
            ->get();

        $available = BookItem::query()
            ->with(['book.category', 'book.authors', 'lender.officeLocation'])
            ->where('status', 'available')
            ->get();

        $catalog = BookItem::query()
            ->with(['book.category', 'book.authors', 'lender.officeLocation'])
            ->where('status', '!=', 'removed')
            ->get();

        return Inertia::render('Reports/Index', [
            'reports' => [
                'loaned' => [
                    'byOfficeLocation' => $loaned->groupBy(fn ($loan) => optional($loan->lender->officeLocation)->name ?? 'Unknown')->map->count(),
                    'byCategory' => $loaned->groupBy(fn ($loan) => optional($loan->bookItem->book->category)->name ?? 'Uncategorized')->map->count(),
                    'byAuthor' => $loaned->flatMap(fn ($loan) => $loan->bookItem->book->authors->pluck('display_name'))->countBy(),
                    'byTitle' => $loaned->groupBy(fn ($loan) => $loan->bookItem->book->title)->map->count(),
                    'byLender' => $loaned->groupBy(fn ($loan) => $loan->lender->display_name)->map->count(),
                    'byBorrower' => $loaned->groupBy(fn ($loan) => $loan->borrower->display_name)->map->count(),
                ],
                'available' => [
                    'byOfficeLocation' => $available->groupBy(fn ($item) => optional($item->lender->officeLocation)->name ?? 'Unknown')->map->count(),
                    'byCategory' => $available->groupBy(fn ($item) => optional($item->book->category)->name ?? 'Uncategorized')->map->count(),
                    'byAuthor' => $available->flatMap(fn ($item) => $item->book->authors->pluck('display_name'))->countBy(),
                    'byTitle' => $available->groupBy(fn ($item) => $item->book->title)->map->count(),
                    'byLender' => $available->groupBy(fn ($item) => $item->lender->display_name)->map->count(),
                ],
                'catalog' => [
                    'byOfficeLocation' => $catalog->groupBy(fn ($item) => optional($item->lender->officeLocation)->name ?? 'Unknown')->map->count(),
                    'byCategory' => $catalog->groupBy(fn ($item) => optional($item->book->category)->name ?? 'Uncategorized')->map->count(),
                    'byAuthor' => $catalog->flatMap(fn ($item) => $item->book->authors->pluck('display_name'))->countBy(),
                    'byTitle' => $catalog->groupBy(fn ($item) => $item->book->title)->map->count(),
                    'byLender' => $catalog->groupBy(fn ($item) => $item->lender->display_name)->map->count(),
                ],
            ],
        ]);
    }
}
