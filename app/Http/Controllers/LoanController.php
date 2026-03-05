<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Loan;
use App\Models\WaitingListEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function borrowed(): Response
    {
        $user = auth()->user();
        $canViewAll = $user->is_administrator || $user->is_site_owner;

        $loans = Loan::query()
            ->with(['bookItem.book.authors', 'lender.officeLocation', 'borrower.officeLocation'])
            ->when($canViewAll, fn ($query) => $query->whereIn('status', ['approved', 'borrowed']))
            ->when(! $canViewAll, fn ($query) => $query->where('borrower_id', $user->id))
            ->latest()
            ->get();

        return Inertia::render('Loans/Borrowed', [
            'loans' => $loans,
            'isGlobalView' => $canViewAll,
        ]);
    }

    public function requests(): Response
    {
        $user = auth()->user();
        $canViewAll = $user->is_administrator || $user->is_site_owner;

        $loans = Loan::query()
            ->with(['bookItem.book.authors', 'borrower.officeLocation', 'lender.officeLocation'])
            ->when($canViewAll, fn ($query) => $query->where('status', 'requested'))
            ->when(! $canViewAll, fn ($query) => $query->where('lender_id', $user->id))
            ->latest()
            ->get();

        return Inertia::render('Loans/Requests', [
            'loans' => $loans,
            'isGlobalView' => $canViewAll,
        ]);
    }

    public function store(Request $request, BookItem $bookItem): RedirectResponse
    {
        abort_unless($request->user()->is_borrower, 403);
        abort_if($bookItem->lender_id === $request->user()->id, 422, 'You cannot borrow your own book.');

        if ($bookItem->status !== 'available') {
            WaitingListEntry::query()->firstOrCreate([
                'book_id' => $bookItem->book_id,
                'book_item_id' => $bookItem->id,
                'user_id' => $request->user()->id,
            ], [
                'status' => 'waiting',
                'position' => 1,
            ]);

            return back()->with('success', 'Book is unavailable. Added to waiting list.');
        }

        Loan::query()->create([
            'book_item_id' => $bookItem->id,
            'lender_id' => $bookItem->lender_id,
            'borrower_id' => $request->user()->id,
            'status' => 'requested',
            'requested_at' => now(),
            'due_date' => $request->input('due_date'),
            'notes' => $request->input('notes'),
        ]);

        $bookItem->update(['status' => 'loan_pending']);

        return back()->with('success', 'Loan request submitted to lender.');
    }

    public function approve(Loan $loan): RedirectResponse
    {
        abort_unless($loan->lender_id === auth()->id(), 403);

        $loan->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Loan request approved. Coordinate book exchange next.');
    }

    public function reject(Loan $loan): RedirectResponse
    {
        abort_unless($loan->lender_id === auth()->id(), 403);

        $loan->update(['status' => 'rejected']);
        $loan->bookItem()->update(['status' => 'available']);

        return back()->with('success', 'Loan request rejected. Book is available again.');
    }

    public function share(Loan $loan): RedirectResponse
    {
        abort_unless($loan->lender_id === auth()->id(), 403);

        $loan->update([
            'status' => 'borrowed',
            'shared_at' => now(),
            'borrowed_at' => now(),
        ]);

        $loan->bookItem()->update([
            'status' => 'checked_out',
            'expected_return_date' => $loan->due_date,
        ]);

        return back()->with('success', 'Book handoff recorded. Loan is now active.');
    }

    public function returnBook(Loan $loan): RedirectResponse
    {
        abort_unless($loan->borrower_id === auth()->id() || $loan->lender_id === auth()->id(), 403);

        $loan->update([
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        $loan->bookItem()->update([
            'status' => 'available',
            'expected_return_date' => null,
        ]);

        return back()->with('success', 'Return recorded and book reshelved.');
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        abort_unless($loan->borrower_id === auth()->id() || $loan->lender_id === auth()->id(), 403);

        $loan->update(['status' => 'cancelled']);

        if ($loan->bookItem->status === 'loan_pending') {
            $loan->bookItem()->update(['status' => 'available']);
        }

        return back()->with('success', 'Loan request cancelled.');
    }
}
