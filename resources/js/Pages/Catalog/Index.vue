<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    filters: Object,
    items: Object,
    categories: Array,
    officeLocations: Array,
    facets: Object,
});

const user = usePage().props.auth.user;

const form = useForm({
    q: props.filters.q ?? '',
    title: props.filters.title ?? '',
    author: props.filters.author ?? '',
    category_id: props.filters.category_id ?? '',
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
    form.category_id = '';
    form.office_location_id = '';
    form.language_id = '';
    form.book_type = '';
    search();
};

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
</script>

<template>
    <Head title="Library Catalog" />

    <AuthenticatedLayout v-if="user">
        <template #header>
            <h2 class="ss-title">Browse Library</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <form @submit.prevent="search" class="ss-card grid grid-cols-1 gap-3 md:grid-cols-8">
                <input v-model="form.q" class="ss-input md:col-span-2" placeholder="Search" />
                <input v-model="form.title" class="ss-input" placeholder="Title" />
                <input v-model="form.author" class="ss-input" placeholder="Author" />
                <select v-model="form.availability" class="ss-select">
                    <option value="all">All Books</option>
                    <option value="available">Available</option>
                </select>
                <select v-model="form.book_type" class="ss-select">
                    <option value="">All Types</option>
                    <option value="hard_copy">Hard Copy</option>
                    <option value="online">Online</option>
                </select>
                <button class="ss-btn-primary justify-center md:col-span-2">Apply Search</button>
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
                                        :class="String(form.category_id) === String(facet.id) ? 'bg-sky-100 text-sky-900' : 'text-slate-700'"
                                        @click="setFacet('category_id', String(form.category_id) === String(facet.id) ? '' : String(facet.id))"
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
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="item in items.data" :key="item.id" class="ss-card">
                            <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                            <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                            <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
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
                <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
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
