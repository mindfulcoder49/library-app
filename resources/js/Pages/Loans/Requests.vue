<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    loans: Array,
    isGlobalView: {
        type: Boolean,
        default: false,
    },
});

const user = usePage().props.auth.user;
const form = useForm({});

const canActAsLender = (loan) => loan.lender_id === user.id;

const act = (routeName, loanId) => {
    form.patch(route(routeName, loanId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Loan Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">{{ isGlobalView ? 'All Loan Requests (System-wide)' : 'Fulfilling Loan Requests' }}</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div v-if="loans.length === 0" class="ss-card">
                <p class="text-sm text-slate-700">{{ isGlobalView ? 'No pending requests found in the system.' : 'No loan requests found in this view.' }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="loan in loans" :key="loan.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ loan.book_item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Borrower: {{ loan.borrower.display_name }}</p>
                    <p class="text-sm text-slate-600">Lender: {{ loan.lender.display_name }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ loan.status }}</span></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button v-if="loan.status === 'requested' && canActAsLender(loan)" @click="act('loans.approve', loan.id)" class="ss-btn-primary">Approve</button>
                        <button v-if="loan.status === 'requested' && canActAsLender(loan)" @click="act('loans.reject', loan.id)" class="ss-btn-danger">Reject</button>
                        <button v-if="loan.status === 'approved' && canActAsLender(loan)" @click="act('loans.share', loan.id)" class="ss-btn-secondary">Mark Shared</button>
                        <button v-if="loan.status === 'borrowed' && canActAsLender(loan)" @click="act('loans.return', loan.id)" class="ss-btn-secondary">Mark Returned</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
