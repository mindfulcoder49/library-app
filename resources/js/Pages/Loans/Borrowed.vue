<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    loans: Array,
    filters: {
        type: Object,
        default: () => ({}),
    },
    people: {
        type: Array,
        default: () => [],
    },
    isGlobalView: {
        type: Boolean,
        default: false,
    },
});

const user = usePage().props.auth.user;
const actionForm = useForm({});
const filterForm = useForm({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    lender_id: props.filters.lender_id ?? '',
    borrower_id: props.filters.borrower_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const canManageLoan = (loan) => loan.borrower_id === user.id || loan.lender_id === user.id;

const applyFilters = () => {
    filterForm.get(route('loans.borrowed'), { preserveState: true, preserveScroll: true, replace: true });
};

const clearFilters = () => {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.lender_id = '';
    filterForm.borrower_id = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    applyFilters();
};

const returnBook = (loanId) => {
    actionForm.patch(route('loans.return', loanId), { preserveScroll: true });
};

const cancel = (loanId) => {
    actionForm.patch(route('loans.cancel', loanId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Books Borrowed" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">{{ isGlobalView ? 'Loans and History (System-wide)' : 'View Books Borrowed' }}</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <form v-if="isGlobalView" @submit.prevent="applyFilters" class="ss-card grid grid-cols-1 gap-3 md:grid-cols-4">
                <input v-model="filterForm.q" class="ss-input md:col-span-2" placeholder="Search title, ISBN, lender, borrower" />
                <select v-model="filterForm.status" class="ss-select">
                    <option value="">All statuses</option>
                    <option value="requested">Requested</option>
                    <option value="approved">Approved</option>
                    <option value="shared">Shared</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="returned">Returned</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </select>
                <div class="flex gap-2">
                    <button class="ss-btn-primary flex-1">Apply</button>
                    <button type="button" class="ss-btn-secondary flex-1" @click="clearFilters">Clear</button>
                </div>

                <select v-model="filterForm.lender_id" class="ss-select">
                    <option value="">All lenders</option>
                    <option v-for="person in people" :key="`lender-${person.id}`" :value="person.id">
                        {{ person.name }} ({{ person.employee_id || 'N/A' }})
                    </option>
                </select>
                <select v-model="filterForm.borrower_id" class="ss-select">
                    <option value="">All borrowers</option>
                    <option v-for="person in people" :key="`borrower-${person.id}`" :value="person.id">
                        {{ person.name }} ({{ person.employee_id || 'N/A' }})
                    </option>
                </select>
                <input v-model="filterForm.date_from" type="date" class="ss-input" />
                <input v-model="filterForm.date_to" type="date" class="ss-input" />
            </form>

            <div v-if="loans.length === 0" class="ss-card">
                <p class="text-sm text-slate-700">{{ isGlobalView ? 'No loan records found in the system.' : 'You do not have any loans in this view.' }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="loan in loans" :key="loan.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ loan.book_item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Borrower: {{ loan.borrower.display_name }}</p>
                    <p class="text-sm text-slate-600">Lender: {{ loan.lender.display_name }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ loan.status }}</span></p>
                    <p class="text-sm text-slate-600">Due: {{ loan.due_date || 'N/A' }}</p>
                    <div class="mt-3 flex gap-2">
                        <button v-if="loan.status === 'borrowed' && canManageLoan(loan)" @click="returnBook(loan.id)" class="ss-btn-secondary">Return Book</button>
                        <button v-if="['requested', 'approved'].includes(loan.status) && canManageLoan(loan)" @click="cancel(loan.id)" class="ss-btn-danger">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
