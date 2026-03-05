<?php

namespace App\Http\Controllers;

use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function borrowed(Request $request): Response
    {
        $user = auth()->user();
        $canViewAll = $user->is_administrator || $user->is_site_owner;
        $filters = $this->validateLoanFilters($request);

        $query = Loan::query()
            ->with(['bookItem.book.authors', 'lender.officeLocation', 'borrower.officeLocation'])
            ->when(! $canViewAll, fn (Builder $builder) => $builder->where('borrower_id', $user->id));

        $this->applyLoanFilters($query, $filters);

        $loans = $query->latest()->get();

        return Inertia::render('Loans/Borrowed', [
            'loans' => $loans,
            'isGlobalView' => $canViewAll,
            'filters' => $this->normalizedLoanFilters($filters),
            'people' => $canViewAll ? $this->loanPeopleOptions() : [],
        ]);
    }

    public function requests(Request $request): Response
    {
        $user = auth()->user();
        $canViewAll = $user->is_administrator || $user->is_site_owner;
        $filters = $this->validateLoanFilters($request);

        $query = Loan::query()
            ->with(['bookItem.book.authors', 'borrower.officeLocation', 'lender.officeLocation'])
            ->when(! $canViewAll, fn (Builder $builder) => $builder->where('lender_id', $user->id));

        $this->applyLoanFilters($query, $filters);

        $loans = $query->latest()->get();

        return Inertia::render('Loans/Requests', [
            'loans' => $loans,
            'isGlobalView' => $canViewAll,
            'filters' => $this->normalizedLoanFilters($filters),
            'people' => $canViewAll ? $this->loanPeopleOptions() : [],
        ]);
    }

    public function waitlist(): Response
    {
        $user = auth()->user();
        $canViewAll = $user->is_administrator || $user->is_site_owner;

        $entries = WaitingListEntry::query()
            ->with(['book.authors', 'book.category', 'book.language', 'bookItem.lender.officeLocation', 'user.officeLocation'])
            ->when(! $canViewAll, fn ($query) => $query->where('user_id', $user->id))
            ->orderByRaw("CASE status WHEN 'waiting' THEN 1 WHEN 'notified' THEN 2 WHEN 'fulfilled' THEN 3 WHEN 'cancelled' THEN 4 ELSE 5 END")
            ->orderBy('position')
            ->latest()
            ->get();

        return Inertia::render('Loans/Waitlist', [
            'entries' => $entries,
            'isGlobalView' => $canViewAll,
        ]);
    }

    public function leaveWaitlist(WaitingListEntry $entry): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($entry->user_id === $user->id || $user->is_administrator || $user->is_site_owner, 403);

        if ($entry->status === 'cancelled') {
            return back()->with('warning', 'This waitlist entry is already cancelled.');
        }

        $entry->update(['status' => 'cancelled']);
        $this->rebalanceWaitlistPositions($entry->book_id);

        return back()->with('success', 'Waitlist entry cancelled.');
    }

    public function store(Request $request, BookItem $bookItem): RedirectResponse
    {
        abort_unless($request->user()->is_borrower, 403);
        abort_if($bookItem->lender_id === $request->user()->id, 422, 'You cannot borrow your own book.');

        if ($bookItem->status !== 'available') {
            $existingEntry = WaitingListEntry::query()->firstOrCreate([
                'book_id' => $bookItem->book_id,
                'book_item_id' => $bookItem->id,
                'user_id' => $request->user()->id,
            ], [
                'status' => 'waiting',
                'position' => $this->nextWaitlistPosition($bookItem->book_id),
            ]);

            if (! $existingEntry->wasRecentlyCreated) {
                return back()->with('warning', 'You are already on the waiting list for this book.');
            }

            return back()->with('success', "Book is unavailable. Added to waiting list at position {$existingEntry->position}.");
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

        WaitingListEntry::query()
            ->where('book_id', $bookItem->book_id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->update(['status' => 'fulfilled']);

        $this->rebalanceWaitlistPositions($bookItem->book_id);

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
        $this->notifyNextWaitingEntry($loan->bookItem);

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
        $this->notifyNextWaitingEntry($loan->bookItem);

        return back()->with('success', 'Return recorded and book reshelved.');
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        abort_unless($loan->borrower_id === auth()->id() || $loan->lender_id === auth()->id(), 403);

        $loan->update(['status' => 'cancelled']);

        if ($loan->bookItem->status === 'loan_pending') {
            $loan->bookItem()->update(['status' => 'available']);
            $this->notifyNextWaitingEntry($loan->bookItem);
        }

        return back()->with('success', 'Loan request cancelled.');
    }

    private function nextWaitlistPosition(int $bookId): int
    {
        $maxPosition = WaitingListEntry::query()
            ->where('book_id', $bookId)
            ->where('status', 'waiting')
            ->max('position');

        return ($maxPosition ?? 0) + 1;
    }

    private function rebalanceWaitlistPositions(int $bookId): void
    {
        $waitingEntries = WaitingListEntry::query()
            ->where('book_id', $bookId)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        foreach ($waitingEntries as $index => $waitingEntry) {
            $nextPosition = $index + 1;
            if ((int) $waitingEntry->position !== $nextPosition) {
                $waitingEntry->update(['position' => $nextPosition]);
            }
        }
    }

    private function notifyNextWaitingEntry(BookItem $bookItem): void
    {
        $nextEntry = WaitingListEntry::query()
            ->where('book_id', $bookItem->book_id)
            ->where('status', 'waiting')
            ->orderBy('position')
            ->orderBy('created_at')
            ->first();

        if (! $nextEntry) {
            return;
        }

        $nextEntry->update([
            'status' => 'notified',
            'notified_at' => now(),
            'book_item_id' => $bookItem->id,
        ]);

        $this->rebalanceWaitlistPositions($bookItem->book_id);
    }

    private function validateLoanFilters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', 'in:requested,approved,shared,borrowed,returned,cancelled,rejected'],
            'lender_id' => ['nullable', 'integer', 'exists:users,id'],
            'borrower_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function applyLoanFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['lender_id'] ?? null, fn (Builder $builder, int $lenderId) => $builder->where('lender_id', $lenderId))
            ->when($filters['borrower_id'] ?? null, fn (Builder $builder, int $borrowerId) => $builder->where('borrower_id', $borrowerId))
            ->when($filters['date_from'] ?? null, fn (Builder $builder, string $dateFrom) => $builder->whereDate('requested_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, string $dateTo) => $builder->whereDate('requested_at', '<=', $dateTo))
            ->when($filters['q'] ?? null, function (Builder $builder, string $q) {
                $builder->where(function (Builder $nested) use ($q) {
                    $nested->whereHas('bookItem.book', function (Builder $bookQuery) use ($q) {
                        $bookQuery->where('title', 'like', '%'.$q.'%')
                            ->orWhere('isbn10', 'like', '%'.$q.'%')
                            ->orWhere('isbn13', 'like', '%'.$q.'%');
                    })->orWhereHas('borrower', function (Builder $borrowerQuery) use ($q) {
                        $borrowerQuery->where('first_name', 'like', '%'.$q.'%')
                            ->orWhere('last_name', 'like', '%'.$q.'%')
                            ->orWhere('name', 'like', '%'.$q.'%')
                            ->orWhere('employee_id', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    })->orWhereHas('lender', function (Builder $lenderQuery) use ($q) {
                        $lenderQuery->where('first_name', 'like', '%'.$q.'%')
                            ->orWhere('last_name', 'like', '%'.$q.'%')
                            ->orWhere('name', 'like', '%'.$q.'%')
                            ->orWhere('employee_id', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%');
                    });
                });
            });
    }

    private function normalizedLoanFilters(array $filters): array
    {
        return [
            'status' => $filters['status'] ?? '',
            'lender_id' => $filters['lender_id'] ?? '',
            'borrower_id' => $filters['borrower_id'] ?? '',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'q' => $filters['q'] ?? '',
        ];
    }

    private function loanPeopleOptions(): array
    {
        return User::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'employee_id'])
            ->map(fn (User $person) => [
                'id' => $person->id,
                'name' => $person->display_name,
                'employee_id' => $person->employee_id,
            ])
            ->all();
    }
}
