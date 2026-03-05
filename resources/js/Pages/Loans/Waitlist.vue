<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    entries: Array,
    isGlobalView: {
        type: Boolean,
        default: false,
    },
});

const user = usePage().props.auth.user;
const form = useForm({});

const canCancel = (entry) => {
    if (!user) return false;

    return entry.user_id === user.id || user.is_administrator || user.is_site_owner;
};

const cancelEntry = (entryId) => {
    form.patch(route('waitlist.cancel', entryId), { preserveScroll: true });
};

const statusLabel = (status) => {
    if (status === 'notified') return 'notified - book is available';

    return status;
};
</script>

<template>
    <Head title="Waitlist" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">{{ isGlobalView ? 'Waitlist (System-wide)' : 'My Waitlist' }}</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div v-if="entries.length === 0" class="ss-card">
                <p class="text-sm text-slate-700">{{ isGlobalView ? 'No waitlist entries found in the system.' : 'You are not currently on any waitlists.' }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="entry in entries" :key="entry.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ entry.book.title }}</h3>
                    <p class="text-sm text-slate-600">Author(s): {{ entry.book.authors.map((author) => author.display_name).join(', ') || 'Unknown' }}</p>
                    <p class="text-sm text-slate-600">Category: {{ entry.book.category?.name || 'Uncategorized' }}</p>
                    <p class="text-sm text-slate-600">Language: {{ entry.book.language?.name || 'Unknown' }}</p>
                    <p class="text-sm text-slate-600">Borrower: {{ entry.user.display_name }}</p>
                    <p class="text-sm text-slate-600">Queue position: {{ entry.position }}</p>
                    <p v-if="entry.book_item?.lender" class="text-sm text-slate-600">Current lender: {{ entry.book_item.lender.display_name }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ statusLabel(entry.status) }}</span></p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-if="['waiting', 'notified'].includes(entry.status) && canCancel(entry)"
                            @click="cancelEntry(entry.id)"
                            class="ss-btn-danger"
                            :disabled="form.processing"
                        >
                            Leave Waitlist
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
