<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Check It Out Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="ss-page-shell space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="ss-card-soft"><p class="text-xs font-semibold uppercase tracking-wide text-sky-800">My Books</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.myBooks }}</p></div>
                    <div class="ss-card-soft"><p class="text-xs font-semibold uppercase tracking-wide text-sky-800">Borrowed</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.booksBorrowed }}</p></div>
                    <div class="ss-card-soft"><p class="text-xs font-semibold uppercase tracking-wide text-sky-800">Incoming Requests</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.incomingRequests }}</p></div>
                    <div class="ss-card-soft"><p class="text-xs font-semibold uppercase tracking-wide text-sky-800">Available Catalog</p><p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.availableCatalog }}</p></div>
                </div>

                <div class="ss-card">
                    <p class="mb-4 text-sm font-medium text-slate-600">Quick Actions</p>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('catalog.index')" class="ss-btn-secondary">Browse Library</Link>
                        <Link :href="route('books.create')" class="ss-btn-primary">Add New Book</Link>
                        <Link :href="route('books.mine')" class="ss-btn-secondary">View My Books</Link>
                        <Link :href="route('loans.borrowed')" class="ss-btn-secondary">View Borrowed</Link>
                        <Link :href="route('reports.index')" class="ss-btn-secondary">View Reports</Link>
                    </div>
                </div>

                <div class="ss-card">
                    <p class="mb-2 text-lg font-semibold text-slate-900">Need Help?</p>
                    <p class="mb-4 text-sm text-slate-600">
                        Read step-by-step usage docs, CSV import guidance, and lender/borrower policy details.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('help.index')" class="ss-btn-primary">Open Help Center</Link>
                        <Link :href="route('guidelines')" class="ss-btn-secondary">Read Guidelines</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
