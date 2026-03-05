<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Help Center" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Help Center</h2>
        </template>

        <div class="ss-page-shell space-y-6 py-8">
            <section class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">What You Can Do</h3>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div class="ss-card-soft">
                        <p class="font-semibold text-slate-900">Browse and Request</p>
                        <p class="mt-1 text-sm text-slate-700">Use Browse to search by title, author, category, and office. Request available books directly from the catalog.</p>
                    </div>
                    <div class="ss-card-soft">
                        <p class="font-semibold text-slate-900">Lend and Manage</p>
                        <p class="mt-1 text-sm text-slate-700">Add books from your shelf, track verification status, and remove or reshelve items as needed.</p>
                    </div>
                    <div class="ss-card-soft">
                        <p class="font-semibold text-slate-900">Process Requests</p>
                        <p class="mt-1 text-sm text-slate-700">On the Requests page, approve/reject requests, mark shared, and mark returned.</p>
                    </div>
                    <div class="ss-card-soft">
                        <p class="font-semibold text-slate-900">Track Borrowing</p>
                        <p class="mt-1 text-sm text-slate-700">On Borrowed, monitor due dates, return books, or cancel pending requests.</p>
                    </div>
                </div>
            </section>

            <section class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">Quick Navigation</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Link :href="route('dashboard')" class="ss-btn-secondary">Dashboard</Link>
                    <Link :href="route('catalog.index')" class="ss-btn-secondary">Browse Catalog</Link>
                    <Link :href="route('books.create')" class="ss-btn-secondary">Add / Import Books</Link>
                    <Link :href="route('books.mine')" class="ss-btn-secondary">My Books</Link>
                    <Link :href="route('loans.requests')" class="ss-btn-secondary">Loan Requests</Link>
                    <Link :href="route('loans.borrowed')" class="ss-btn-secondary">Borrowed</Link>
                    <Link :href="route('reports.index')" class="ss-btn-secondary">Reports</Link>
                    <Link :href="route('guidelines')" class="ss-btn-primary">Guidelines</Link>
                </div>
            </section>

            <section class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">Bulk CSV Import Tips</h3>
                <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700">
                    <li>Use the template from Add Book for the correct column order.</li>
                    <li>Run Preview (Dry Run) first to validate rows before importing.</li>
                    <li>Use comma-separated office codes in Office Locations, such as OCB, CCB, JAB.</li>
                    <li>Admins/site owners can use Lender ID to import on behalf of another lender. For all other users, books are assigned to the uploader.</li>
                </ul>
            </section>

            <section class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">Guidelines</h3>
                <p class="mt-2 text-sm text-slate-700">
                    All lenders and borrowers should follow the community rules before sharing or requesting books.
                </p>
                <div class="mt-3">
                    <Link :href="route('guidelines')" class="ss-btn-primary">View Lender and Borrower Guidelines</Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
