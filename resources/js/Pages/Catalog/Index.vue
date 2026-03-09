<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    filters: Object,
    items: Object,
    categoryTier1: Array,
    categoryTier2: Array,
    categoryTier3: Array,
    officeLocations: Array,
    facets: Object,
});

const user = usePage().props.auth.user;
const viewMode = ref('card');

const form = useForm({
    q: props.filters.q ?? '',
    title: props.filters.title ?? '',
    author: props.filters.author ?? '',
    category: props.filters.category ?? '',
    category_1_id: props.filters.category_1_id ?? '',
    category_2_id: props.filters.category_2_id ?? '',
    category_3_id: props.filters.category_3_id ?? '',
    office_location_id: props.filters.office_location_id ?? '',
    language_id: props.filters.language_id ?? '',
    book_type: props.filters.book_type ?? '',
    availability: props.filters.availability ?? 'all',
});
const actionForm = useForm({});

const search = () => {
    form.get(route('catalog.index'), { preserveState: true, replace: true, preserveScroll: true });
};

const setFacet = (key, value) => {
    form[key] = value;
    search();
};

const clearFacets = () => {
    form.category = '';
    form.category_1_id = '';
    form.category_2_id = '';
    form.category_3_id = '';
    form.office_location_id = '';
    form.language_id = '';
    form.book_type = '';
    search();
};

const normalizeCategoryValue = (value) => {
    if (!value) return '';

    return String(value).replace(/\u00A0/g, ' ').trim().toLowerCase();
};

const filteredTier1Options = computed(() => {
    let options = props.categoryTier1 ?? [];

    if (form.category_2_id) {
        const category2 = (props.categoryTier2 ?? []).find((category) => String(category.id) === String(form.category_2_id));
        options = category2 ? options.filter((category) => String(category.id) === String(category2.parent_id)) : [];
    }

    if (form.category_3_id) {
        const category3 = (props.categoryTier3 ?? []).find((category) => String(category.id) === String(form.category_3_id));
        options = category3 ? options.filter((category) => String(category.id) === String(category3.parent_tier1_id)) : [];
    }

    return options;
});

const filteredTier2Options = computed(() => {
    let options = props.categoryTier2 ?? [];

    if (form.category_1_id) {
        options = options.filter((category) => String(category.parent_id) === String(form.category_1_id));
    }

    if (form.category_3_id) {
        const category3 = (props.categoryTier3 ?? []).find((category) => String(category.id) === String(form.category_3_id));
        options = category3 ? options.filter((category) => String(category.id) === String(category3.parent_id)) : [];
    }

    return options;
});

const filteredTier3Options = computed(() => {
    let options = props.categoryTier3 ?? [];

    if (form.category_2_id) {
        options = options.filter((category) => String(category.parent_id) === String(form.category_2_id));
    }

    if (form.category_1_id) {
        options = options.filter((category) => String(category.parent_tier1_id) === String(form.category_1_id));
    }

    return options;
});

watch(() => form.category_3_id, () => {
    if (!filteredTier1Options.value.some((category) => String(category.id) === String(form.category_1_id))) {
        form.category_1_id = '';
    }
    if (!filteredTier2Options.value.some((category) => String(category.id) === String(form.category_2_id))) {
        form.category_2_id = '';
    }
});

watch(() => form.category_1_id, () => {
    if (!filteredTier2Options.value.some((category) => String(category.id) === String(form.category_2_id))) {
        form.category_2_id = '';
        form.category_3_id = '';
    }
});

watch(() => form.category_2_id, () => {
    if (!filteredTier3Options.value.some((category) => String(category.id) === String(form.category_3_id))) {
        form.category_3_id = '';
    }
});

const requestLoan = (itemId) => {
    form.post(route('loans.store', itemId), { preserveScroll: true });
};

const canRequestOrWaitlist = (item) => {
    if (!user) return false;

    if (item.lender.id === user.id) return false;

    return ['available', 'loan_pending', 'checked_out'].includes(item.status);
};

const requestButtonLabel = (item) => {
    return item.status === 'available' ? 'Request Book' : 'Join Waitlist';
};

const removeItem = (itemId) => {
    actionForm.patch(route('books.remove', itemId), { preserveScroll: true });
};

const moveToPending = (itemId) => {
    actionForm.patch(route('books.mark-pending', itemId), { preserveScroll: true });
};

const bookTypeLabel = (value) => {
    if (value === 'hard_copy') return 'Hard Copy';
    if (value === 'online') return 'Online';

    return value;
};

const amazonLink = (book) => {
    const isbn = book?.isbn13 || book?.isbn10;
    if (!isbn) {
        return null;
    }

    return `https://www.amazon.com/s?k=${encodeURIComponent(isbn)}`;
};

const canEditItem = (item) => {
    if (!user) return false;

    return item.lender.id === user.id || user.is_administrator || user.is_site_owner;
};

const isAdminUser = () => {
    if (!user) return false;

    return user.is_administrator || user.is_site_owner;
};

const setViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('catalog_view_mode', mode);
};

onMounted(() => {
    const stored = localStorage.getItem('catalog_view_mode');
    if (stored === 'card' || stored === 'list') {
        viewMode.value = stored;
    }
});
</script>

<template>
    <Head title="Library Catalog" />

    <AuthenticatedLayout v-if="user">
        <template #header>
            <h2 class="ss-title">Browse Library</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <form @submit.prevent="search" class="ss-card grid grid-cols-1 gap-3 md:grid-cols-8">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600 md:col-span-2">
                    Keyword Search
                    <input v-model="form.q" class="ss-input mt-1 w-full" placeholder="Title, author, ISBN, description..." />
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Title
                    <input v-model="form.title" class="ss-input mt-1 w-full" placeholder="Book title" />
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Author
                    <input v-model="form.author" class="ss-input mt-1 w-full" placeholder="Author name" />
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Category 1
                    <select v-model="form.category_1_id" class="ss-select mt-1 w-full">
                        <option value="">All Category 1</option>
                        <option v-for="category in filteredTier1Options" :key="`t1-${category.id}`" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Category 2
                    <select v-model="form.category_2_id" class="ss-select mt-1 w-full">
                        <option value="">All Category 2</option>
                        <option v-for="category in filteredTier2Options" :key="`t2-${category.id}`" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Category 3
                    <select v-model="form.category_3_id" class="ss-select mt-1 w-full">
                        <option value="">All Category 3</option>
                        <option v-for="category in filteredTier3Options" :key="`t3-${category.id}`" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Availability
                    <select v-model="form.availability" class="ss-select mt-1 w-full">
                        <option value="all">All Books</option>
                        <option value="available">Available</option>
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Book Type
                    <select v-model="form.book_type" class="ss-select mt-1 w-full">
                        <option value="">All Types</option>
                        <option value="hard_copy">Hard Copy</option>
                        <option value="online">Online</option>
                    </select>
                </label>
                <div class="md:col-span-8">
                    <button class="ss-btn-primary w-full justify-center">Apply Search</button>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <aside class="space-y-4 lg:col-span-3">
                    <div class="ss-card">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-800">Facets</h3>
                            <button type="button" class="text-xs font-semibold text-sky-700 underline" @click="clearFacets">Clear</button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Category</p>
                                <div class="space-y-1">
                                    <button
                                        v-for="facet in facets?.categories || []"
                                        :key="`cat-${facet.id}`"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg px-2 py-1 text-left text-sm hover:bg-sky-50"
                                        :class="normalizeCategoryValue(form.category) === normalizeCategoryValue(facet.id) ? 'bg-sky-100 text-sky-900' : 'text-slate-700'"
                                        @click="setFacet('category', normalizeCategoryValue(form.category) === normalizeCategoryValue(facet.id) ? '' : facet.id)"
                                    >
                                        <span>{{ facet.name }}</span>
                                        <span class="text-xs">{{ facet.total }}</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Office</p>
                                <div class="space-y-1">
                                    <button
                                        v-for="facet in facets?.office_locations || []"
                                        :key="`office-${facet.id}`"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg px-2 py-1 text-left text-sm hover:bg-sky-50"
                                        :class="String(form.office_location_id) === String(facet.id) ? 'bg-sky-100 text-sky-900' : 'text-slate-700'"
                                        @click="setFacet('office_location_id', String(form.office_location_id) === String(facet.id) ? '' : String(facet.id))"
                                    >
                                        <span>{{ facet.name }}</span>
                                        <span class="text-xs">{{ facet.total }}</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Language</p>
                                <div class="space-y-1">
                                    <button
                                        v-for="facet in facets?.languages || []"
                                        :key="`lang-${facet.id}`"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg px-2 py-1 text-left text-sm hover:bg-sky-50"
                                        :class="String(form.language_id) === String(facet.id) ? 'bg-sky-100 text-sky-900' : 'text-slate-700'"
                                        @click="setFacet('language_id', String(form.language_id) === String(facet.id) ? '' : String(facet.id))"
                                    >
                                        <span>{{ facet.name }}</span>
                                        <span class="text-xs">{{ facet.total }}</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Book Type</p>
                                <div class="space-y-1">
                                    <button
                                        v-for="facet in facets?.book_types || []"
                                        :key="`type-${facet.book_type}`"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg px-2 py-1 text-left text-sm hover:bg-sky-50"
                                        :class="String(form.book_type) === String(facet.book_type) ? 'bg-sky-100 text-sky-900' : 'text-slate-700'"
                                        @click="setFacet('book_type', String(form.book_type) === String(facet.book_type) ? '' : String(facet.book_type))"
                                    >
                                        <span>{{ bookTypeLabel(facet.book_type) }}</span>
                                        <span class="text-xs">{{ facet.total }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="space-y-4 lg:col-span-9">
                    <div class="ss-card flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="ss-btn-secondary"
                            :class="viewMode === 'card' ? 'border-sky-500 bg-sky-100 text-sky-900' : ''"
                            @click="setViewMode('card')"
                        >
                            Card View
                        </button>
                        <button
                            type="button"
                            class="ss-btn-secondary"
                            :class="viewMode === 'list' ? 'border-sky-500 bg-sky-100 text-sky-900' : ''"
                            @click="setViewMode('list')"
                        >
                            List View
                        </button>
                    </div>

                    <div v-if="viewMode === 'card'" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="item in items.data" :key="item.id" class="ss-card">
                            <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                            <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Category 1: {{ item.book.category_tier_1 || 'N/A' }}</p>
                            <p class="text-sm text-slate-600">Category 2: {{ item.book.category_tier_2 || 'N/A' }}</p>
                            <p class="text-sm text-slate-600">Category 3: {{ item.book.category_tier_3 || 'N/A' }}</p>
                            <p class="text-sm text-slate-600">Language: {{ item.book.language || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Type: {{ bookTypeLabel(item.book.book_type) }}</p>
                            <p class="text-sm text-slate-600">ISBN-13: {{ item.book.isbn13 || 'N/A' }}</p>
                            <p class="text-sm text-slate-600">ISBN-10: {{ item.book.isbn10 || 'N/A' }}</p>
                            <p class="text-sm text-slate-600">Office: {{ item.lender.office_location || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Lender: {{ item.lender.name }}</p>
                            <p class="mt-2"><span class="ss-pill">{{ item.status }}</span></p>
                            <p
                                v-if="item.user_context?.has_requested"
                                class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800"
                            >
                                You have already requested this book.
                            </p>
                            <p
                                v-else-if="item.user_context?.on_waitlist"
                                class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900"
                            >
                                You are on the waitlist for this book.
                            </p>
                            <p v-if="item.book.description" class="mt-2 text-sm text-slate-700">{{ item.book.description }}</p>
                            <p v-if="item.lender_comments" class="mt-2 text-sm text-slate-700"><span class="font-semibold text-slate-900">Lender Comments:</span> {{ item.lender_comments }}</p>
                            <a
                                v-if="amazonLink(item.book)"
                                :href="amazonLink(item.book)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ss-btn-secondary mt-3"
                            >
                                View on Amazon
                            </a>
                            <Link
                                v-if="canEditItem(item)"
                                :href="route('books.edit', item.id)"
                                class="ss-btn-secondary mt-2"
                            >
                                Edit Book
                            </Link>
                            <button
                                v-if="isAdminUser() && item.status !== 'pending_verification'"
                                class="ss-btn-secondary mt-2"
                                :disabled="actionForm.processing"
                                @click="moveToPending(item.id)"
                            >
                                Move to Pending
                            </button>
                            <button
                                v-if="isAdminUser() && item.status !== 'removed'"
                                class="ss-btn-danger mt-2"
                                :disabled="actionForm.processing"
                                @click="removeItem(item.id)"
                            >
                                Remove Book
                            </button>
                            <button
                                v-if="canRequestOrWaitlist(item)"
                                @click="requestLoan(item.id)"
                                class="ss-btn-primary mt-3"
                            >
                                {{ requestButtonLabel(item) }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="ss-card overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Book</th>
                                    <th class="px-3 py-2">Details</th>
                                    <th class="px-3 py-2">Lender</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items.data" :key="`list-${item.id}`" class="border-b border-slate-100 align-top">
                                    <td class="px-3 py-3">
                                        <p class="font-semibold text-slate-900">{{ item.book.title }}</p>
                                        <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                                        <p v-if="item.book.description" class="mt-1 text-sm text-slate-700">{{ item.book.description }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-slate-700">
                                        <p>Category 1: {{ item.book.category_tier_1 || 'N/A' }}</p>
                                        <p>Category 2: {{ item.book.category_tier_2 || 'N/A' }}</p>
                                        <p>Category 3: {{ item.book.category_tier_3 || 'N/A' }}</p>
                                        <p>Language: {{ item.book.language || 'Unknown' }}</p>
                                        <p>Type: {{ bookTypeLabel(item.book.book_type) }}</p>
                                        <p>ISBN-13: {{ item.book.isbn13 || 'N/A' }}</p>
                                        <p>ISBN-10: {{ item.book.isbn10 || 'N/A' }}</p>
                                        <p v-if="item.lender_comments" class="mt-1"><span class="font-semibold text-slate-900">Lender Comments:</span> {{ item.lender_comments }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-slate-700">
                                        <p>{{ item.lender.name }}</p>
                                        <p>{{ item.lender.office_location || 'Unknown' }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="ss-pill">{{ item.status }}</span>
                                        <p
                                            v-if="item.user_context?.has_requested"
                                            class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800"
                                        >
                                            You have already requested this book.
                                        </p>
                                        <p
                                            v-else-if="item.user_context?.on_waitlist"
                                            class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900"
                                        >
                                            You are on the waitlist for this book.
                                        </p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                v-if="amazonLink(item.book)"
                                                :href="amazonLink(item.book)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="ss-btn-secondary"
                                            >
                                                Amazon
                                            </a>
                                            <Link
                                                v-if="canEditItem(item)"
                                                :href="route('books.edit', item.id)"
                                                class="ss-btn-secondary"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                v-if="isAdminUser() && item.status !== 'pending_verification'"
                                                class="ss-btn-secondary"
                                                :disabled="actionForm.processing"
                                                @click="moveToPending(item.id)"
                                            >
                                                Pending
                                            </button>
                                            <button
                                                v-if="isAdminUser() && item.status !== 'removed'"
                                                class="ss-btn-danger"
                                                :disabled="actionForm.processing"
                                                @click="removeItem(item.id)"
                                            >
                                                Remove
                                            </button>
                                            <button
                                                v-if="canRequestOrWaitlist(item)"
                                                @click="requestLoan(item.id)"
                                                class="ss-btn-primary"
                                            >
                                                {{ requestButtonLabel(item) }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="ss-card" v-if="items.links && items.links.length > 3">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <template v-for="(link, index) in items.links" :key="`p-${index}`">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    class="rounded-lg border px-3 py-1 text-sm"
                                    :class="link.active ? 'border-sky-500 bg-sky-100 text-sky-900' : 'border-sky-200 bg-white text-slate-700 hover:bg-sky-50'"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1 text-sm text-slate-400"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>

    <div v-else class="mx-auto max-w-6xl p-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-semibold text-slate-900">Check It Out Library Catalog</h1>
            <div class="space-x-2">
                <Link :href="route('login')" class="ss-btn-secondary">Log in</Link>
                <Link :href="route('register')" class="ss-btn-primary">Register</Link>
            </div>
        </div>

        <div class="ss-card mb-4 flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm text-slate-700">Browse by filters after logging in for full faceted experience.</span>
            <div class="space-x-2">
                <Link :href="route('login')" class="ss-btn-secondary">Log in</Link>
                <Link :href="route('register')" class="ss-btn-primary">Register</Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div v-for="item in items.data" :key="item.id" class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                <p class="text-sm text-slate-600">Category 1: {{ item.book.category_tier_1 || 'N/A' }}</p>
                <p class="text-sm text-slate-600">Category 2: {{ item.book.category_tier_2 || 'N/A' }}</p>
                <p class="text-sm text-slate-600">Category 3: {{ item.book.category_tier_3 || 'N/A' }}</p>
                <p class="text-sm text-slate-600">Language: {{ item.book.language || 'Unknown' }}</p>
                <p class="text-sm text-slate-600">Type: {{ bookTypeLabel(item.book.book_type) }}</p>
                <p class="text-sm text-slate-600">ISBN-13: {{ item.book.isbn13 || 'N/A' }}</p>
                <p class="text-sm text-slate-600">ISBN-10: {{ item.book.isbn10 || 'N/A' }}</p>
                <p class="text-sm text-slate-600">Office: {{ item.lender.office_location || 'Unknown' }}</p>
                <p class="text-sm text-slate-600">Lender: {{ item.lender.name }}</p>
                <p class="mt-2"><span class="ss-pill">{{ item.status }}</span></p>
                <p v-if="item.book.description" class="mt-2 text-sm text-slate-700">{{ item.book.description }}</p>
                <p v-if="item.lender_comments" class="mt-2 text-sm text-slate-700"><span class="font-semibold text-slate-900">Lender Comments:</span> {{ item.lender_comments }}</p>
                <a
                    v-if="amazonLink(item.book)"
                    :href="amazonLink(item.book)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ss-btn-secondary mt-3"
                >
                    View on Amazon
                </a>
            </div>
        </div>

        <div class="ss-card mt-4" v-if="items.links && items.links.length > 3">
            <div class="flex flex-wrap items-center justify-center gap-2">
                <template v-for="(link, index) in items.links" :key="`g-p-${index}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1 text-sm"
                        :class="link.active ? 'border-sky-500 bg-sky-100 text-sky-900' : 'border-sky-200 bg-white text-slate-700 hover:bg-sky-50'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1 text-sm text-slate-400"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
