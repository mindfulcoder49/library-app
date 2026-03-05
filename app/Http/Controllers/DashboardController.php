<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Loan;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                'myBooks' => BookItem::query()->where('lender_id', $user->id)->count(),
                'booksBorrowed' => Loan::query()
                    ->where('borrower_id', $user->id)
                    ->whereIn('status', ['approved', 'shared', 'borrowed'])
                    ->count(),
                'incomingRequests' => Loan::query()
                    ->where('lender_id', $user->id)
                    ->where('status', 'requested')
                    ->count(),
                'availableCatalog' => BookItem::query()->where('status', 'available')->count(),
            ],
        ]);
    }
}
