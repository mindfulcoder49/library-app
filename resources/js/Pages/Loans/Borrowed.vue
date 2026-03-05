<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    loans: Array,
});

const form = useForm({});

const returnBook = (loanId) => {
    form.patch(route('loans.return', loanId), { preserveScroll: true });
};

const cancel = (loanId) => {
    form.patch(route('loans.cancel', loanId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Books Borrowed" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">View Books Borrowed</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="loan in loans" :key="loan.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ loan.book_item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Lender: {{ loan.lender.display_name }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ loan.status }}</span></p>
                    <p class="text-sm text-slate-600">Due: {{ loan.due_date || 'N/A' }}</p>
                    <div class="mt-3 flex gap-2">
                        <button v-if="loan.status === 'borrowed'" @click="returnBook(loan.id)" class="ss-btn-secondary">Return Book</button>
                        <button v-if="['requested', 'approved'].includes(loan.status)" @click="cancel(loan.id)" class="ss-btn-danger">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
