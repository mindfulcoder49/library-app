<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Guidelines" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Lender and Borrower Guidelines</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 py-8 sm:px-6 lg:px-8">
            <div class="ss-card">
                <h3 class="mb-2 text-lg font-semibold text-slate-900">Guidelines for Lenders</h3>
                <ul class="list-inside list-disc space-y-1 text-slate-700">
                    <li>Willing to share the books offered.</li>
                    <li>Share only books that are in good shape.</li>
                    <li>Share books about Diversity, Inclusion and Equity or written by diverse and underrepresented authors.</li>
                    <li>Contact the borrower in a timely manner and exchange the book.</li>
                </ul>
            </div>

            <div class="ss-card">
                <h3 class="mb-2 text-lg font-semibold text-slate-900">Guidelines for Borrowers</h3>
                <ul class="list-inside list-disc space-y-1 text-slate-700">
                    <li>Treat borrowed books respectfully.</li>
                    <li>Agree with the lender on a time and place to exchange the book.</li>
                    <li>Return the book on time or request an extension.</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
