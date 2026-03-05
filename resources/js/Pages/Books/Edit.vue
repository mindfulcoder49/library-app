<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
    languages: Array,
    item: Object,
});

const form = useForm({
    title: props.item.book.title ?? '',
    isbn10: props.item.book.isbn10 ?? '',
    isbn13: props.item.book.isbn13 ?? '',
    description: props.item.book.description ?? '',
    category_id: props.item.book.category_id ?? '',
    language_id: props.item.book.language_id ?? '',
    book_type: props.item.book.book_type ?? 'hard_copy',
    lender_comments: props.item.lender_comments ?? '',
    expected_return_date: props.item.expected_return_date ?? '',
    authors: props.item.book.authors?.length ? props.item.book.authors : [{ first_name: '', last_name: '' }],
});

const addAuthor = () => {
    form.authors.push({ first_name: '', last_name: '' });
};

const submit = () => {
    form.patch(route('books.update', props.item.id));
};
</script>

<template>
    <Head title="Edit Book" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Edit Book</h2>
        </template>

        <div class="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="ss-card space-y-4">
                <input v-model="form.title" class="w-full ss-input" placeholder="Title" required />
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <input v-model="form.isbn10" class="ss-input" placeholder="ISBN-10" />
                    <input v-model="form.isbn13" class="ss-input" placeholder="ISBN-13" />
                </div>
                <textarea v-model="form.description" class="w-full ss-input" rows="4" placeholder="Description"></textarea>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <select v-model="form.category_id" class="ss-input">
                        <option value="">Category</option>
                        <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <select v-model="form.language_id" class="ss-input">
                        <option value="">Language</option>
                        <option v-for="language in props.languages" :key="language.id" :value="language.id">{{ language.name }}</option>
                    </select>
                </div>
                <select v-model="form.book_type" class="w-full ss-input">
                    <option value="hard_copy">Hard Copy</option>
                    <option value="online">Online</option>
                </select>
                <input v-model="form.expected_return_date" type="date" class="w-full ss-input" />
                <textarea v-model="form.lender_comments" class="w-full ss-input" rows="2" placeholder="Lender comments"></textarea>

                <div class="space-y-2 rounded border p-3">
                    <p class="font-semibold">Author(s)</p>
                    <div v-for="(author, index) in form.authors" :key="index" class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <input v-model="author.first_name" class="ss-input" placeholder="First name" />
                        <input v-model="author.last_name" class="ss-input" placeholder="Last name" required />
                    </div>
                    <button type="button" @click="addAuthor" class="ss-btn-secondary">Add Author</button>
                </div>

                <div class="flex gap-3">
                    <button class="ss-btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="route('books.mine')" class="ss-btn-secondary">Cancel</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
