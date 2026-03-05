<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    loans: Array,
});

const form = useForm({});

const act = (routeName, loanId) => {
    form.patch(route(routeName, loanId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Loan Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Fulfilling Loan Requests</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="loan in loans" :key="loan.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ loan.book_item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Borrower: {{ loan.borrower.display_name }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ loan.status }}</span></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button v-if="loan.status === 'requested'" @click="act('loans.approve', loan.id)" class="ss-btn-primary">Approve</button>
                        <button v-if="loan.status === 'requested'" @click="act('loans.reject', loan.id)" class="ss-btn-danger">Reject</button>
                        <button v-if="loan.status === 'approved'" @click="act('loans.share', loan.id)" class="ss-btn-secondary">Mark Shared</button>
                        <button v-if="loan.status === 'borrowed'" @click="act('loans.return', loan.id)" class="ss-btn-secondary">Mark Returned</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
