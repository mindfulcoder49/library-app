<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    items: Array,
});

const user = usePage().props.auth.user;
const canModerate = user?.is_administrator || user?.is_site_owner;
const form = useForm({});

const doAction = (routeName, id) => {
    form.patch(route(routeName, id), { preserveScroll: true });
};
</script>

<template>
    <Head title="My Books" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">My Books in the Library</h2>
        </template>

        <div class="ss-page-shell space-y-4 py-8">
            <div>
                <Link :href="route('books.create')" class="ss-btn-primary">Add Book</Link>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div v-for="item in items" :key="item.id" class="ss-card">
                    <h3 class="text-lg font-semibold text-slate-900">{{ item.book.title }}</h3>
                    <p class="text-sm text-slate-600">Authors: {{ item.book.authors.join(', ') }}</p>
                    <p class="text-sm text-slate-600">Category: {{ item.book.category || 'Uncategorized' }}</p>
                    <p class="mt-2"><span class="ss-pill">{{ item.status }}</span></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button v-if="item.status === 'removed'" @click="doAction('books.reshelve', item.id)" class="ss-btn-secondary">Reshelve</button>
                        <button v-if="item.status !== 'removed'" @click="doAction('books.remove', item.id)" class="ss-btn-danger">Remove</button>
                        <button v-if="item.status === 'pending_verification' && canModerate" @click="doAction('books.verify', item.id)" class="ss-btn-secondary">Verify</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
