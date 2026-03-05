<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    filters: Object,
    items: Object,
    categories: Array,
    officeLocations: Array,
});

const user = usePage().props.auth.user;

const form = useForm({
    q: props.filters.q ?? '',
    title: props.filters.title ?? '',
    author: props.filters.author ?? '',
    category_id: props.filters.category_id ?? '',
    office_location_id: props.filters.office_location_id ?? '',
    availability: props.filters.availability ?? 'available',
});

const search = () => {
    form.get(route('catalog.index'), { preserveState: true, replace: true });
};

const requestLoan = (itemId) => {
    form.post(route('loans.store', itemId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Library Catalog" />

    <AuthenticatedLayout v-if="user">
        <template #header>
            <h2 class="ss-title">Browse Library</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <form @submit.prevent="search" class="ss-card grid grid-cols-1 gap-3 md:grid-cols-6">
                <input v-model="form.q" class="ss-input" placeholder="Search" />
                <input v-model="form.title" class="ss-input" placeholder="Title" />
                <input v-model="form.author" class="ss-input" placeholder="Author" />
                <select v-model="form.category_id" class="ss-select">
                    <option value="">All Categories</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
                <select v-model="form.office_location_id" class="ss-select">
                    <option value="">All Offices</option>
                    <option v-for="office in officeLocations" :key="office.id" :value="office.id">{{ office.name }}</option>
                </select>
                <div class="flex gap-2">
                    <select v-model="form.availability" class="ss-select w-full">
                        <option value="available">Available</option>
                        <option value="all">All Books</option>
                    </select>
                    <button class="ss-btn-primary">Go</button>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="item in items.data" :key="item.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                    <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
                    <p class="text-sm text-slate-600">Office: {{ item.lender.office_location || 'Unknown' }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ item.status }}</span></p>
                    <button
                        v-if="user && item.status === 'available' && item.lender.id !== user.id"
                        @click="requestLoan(item.id)"
                        class="ss-btn-primary mt-3"
                    >
                        Request Book
                    </button>
                </div>
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
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div v-for="item in items.data" :key="item.id" class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                <p class="text-sm text-slate-600">Author(s): {{ item.book.authors.join(', ') || 'Unknown' }}</p>
                <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
                <p class="text-sm text-slate-600">Office: {{ item.lender.office_location || 'Unknown' }}</p>
                <p class="mt-2"><span class="ss-pill">{{ item.status }}</span></p>
            </div>
        </div>
    </div>
</template>
