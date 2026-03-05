<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    items: Array,
});

const form = useForm({
    book_item_ids: [],
});
const actionForm = useForm({});

const selectedIds = ref([]);

const totalItems = computed(() => props.items.length);
const selectedCount = computed(() => selectedIds.value.length);
const allSelected = computed(() => totalItems.value > 0 && selectedCount.value === totalItems.value);

const toggleItem = (id, checked) => {
    if (checked) {
        if (!selectedIds.value.includes(id)) {
            selectedIds.value.push(id);
        }
        return;
    }

    selectedIds.value = selectedIds.value.filter((itemId) => itemId !== id);
};

const selectAll = () => {
    selectedIds.value = props.items.map((item) => item.id);
};

const deselectAll = () => {
    selectedIds.value = [];
};

const verifySelected = () => {
    form.book_item_ids = [...selectedIds.value];
    form.post(route('books.verify-bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
        },
    });
};

const bookTypeLabel = (value) => {
    if (value === 'hard_copy') return 'Hard Copy';
    if (value === 'online') return 'Online';

    return value || 'N/A';
};

const amazonLink = (book) => {
    const isbn = book?.isbn13 || book?.isbn10;
    if (!isbn) return null;

    return `https://www.amazon.com/s?k=${encodeURIComponent(isbn)}`;
};

const verify = (id) => {
    form.patch(route('books.verify', id), { preserveScroll: true });
};

const removeItem = (id) => {
    actionForm.patch(route('books.remove', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Pending Verification" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Pending Verification</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div v-if="items.length === 0" class="ss-card">
                <p class="text-sm text-slate-700">No books are currently pending verification.</p>
            </div>

            <div v-else class="ss-card">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="ss-btn-secondary" :disabled="allSelected" @click="selectAll">Select All</button>
                    <button type="button" class="ss-btn-secondary" :disabled="selectedCount === 0" @click="deselectAll">Deselect All</button>
                    <button type="button" class="ss-btn-primary" :disabled="form.processing || selectedCount === 0" @click="verifySelected">
                        {{ form.processing ? 'Verifying...' : `Verify Selected (${selectedCount})` }}
                    </button>
                    <span class="text-sm text-slate-600">{{ selectedCount }} of {{ totalItems }} selected</span>
                </div>
            </div>

            <div v-for="item in items" :key="item.id" class="ss-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            class="mt-1 rounded border-sky-300 text-sky-700 focus:ring-sky-500"
                            :checked="selectedIds.includes(item.id)"
                            @change="toggleItem(item.id, $event.target.checked)"
                        />
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                            <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
                            <p class="text-sm text-slate-600">Language: {{ item.book.language || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Type: {{ bookTypeLabel(item.book.book_type) }}</p>
                            <p class="text-sm text-slate-600">ISBN: {{ item.book.isbn13 || item.book.isbn10 || 'N/A' }}</p>
                        </div>
                    </div>
                    <span class="ss-pill">pending_verification</span>
                </div>

                <div class="mt-3 grid gap-2 text-sm text-slate-700 md:grid-cols-2">
                    <p><span class="font-semibold text-slate-900">Lender:</span> {{ item.lender.name }}</p>
                    <p><span class="font-semibold text-slate-900">Employee ID:</span> {{ item.lender.employee_id || 'N/A' }}</p>
                    <p><span class="font-semibold text-slate-900">Office:</span> {{ item.lender.office_location || 'N/A' }}</p>
                    <p><span class="font-semibold text-slate-900">Submitted:</span> {{ item.created_at || 'N/A' }}</p>
                </div>

                <p v-if="item.lender_comments" class="mt-2 text-sm text-slate-700">
                    <span class="font-semibold text-slate-900">Lender Comments:</span> {{ item.lender_comments }}
                </p>

                <p v-if="item.book.description" class="mt-2 text-sm text-slate-700">{{ item.book.description }}</p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Link :href="route('books.edit', item.id)" class="ss-btn-secondary">
                        Edit Book
                    </Link>
                    <a
                        v-if="amazonLink(item.book)"
                        :href="amazonLink(item.book)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="ss-btn-secondary"
                    >
                        View on Amazon
                    </a>
                    <button class="ss-btn-secondary" :disabled="form.processing" @click="verify(item.id)">
                        Verify and Publish
                    </button>
                    <button class="ss-btn-danger" :disabled="actionForm.processing" @click="removeItem(item.id)">
                        Remove Book
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
