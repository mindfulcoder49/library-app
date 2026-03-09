<script setup>
import { computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categoryTier1: Array,
    categoryTier2: Array,
    categoryTier3: Array,
    languages: Array,
    item: Object,
});

const form = useForm({
    title: props.item.book.title ?? '',
    isbn10: props.item.book.isbn10 ?? '',
    isbn13: props.item.book.isbn13 ?? '',
    description: props.item.book.description ?? '',
    category_1_id: props.item.book.category_1_id ?? '',
    category_1_name: '',
    category_2_id: props.item.book.category_2_id ?? '',
    category_2_name: '',
    category_3_id: props.item.book.category_3_id ?? '',
    category_3_name: '',
    language_id: props.item.book.language_id ?? '',
    language_name: props.item.book.language_name ?? '',
    book_type: props.item.book.book_type ?? 'hard_copy',
    lender_comments: props.item.lender_comments ?? '',
    expected_return_date: props.item.expected_return_date ?? '',
    authors: props.item.book.authors?.length ? props.item.book.authors : [{ first_name: '', last_name: '' }],
});

const addAuthor = () => {
    form.authors.push({ first_name: '', last_name: '' });
};

const filteredTier2 = computed(() => {
    if (!form.category_1_id) return props.categoryTier2 ?? [];
    return (props.categoryTier2 ?? []).filter((item) => String(item.parent_id) === String(form.category_1_id));
});

const filteredTier3 = computed(() => {
    if (!form.category_2_id) return props.categoryTier3 ?? [];
    return (props.categoryTier3 ?? []).filter((item) => String(item.parent_id) === String(form.category_2_id));
});

watch(
    () => form.category_1_id,
    () => {
        if (!filteredTier2.value.some((item) => String(item.id) === String(form.category_2_id))) {
            form.category_2_id = '';
        }
        if (!filteredTier3.value.some((item) => String(item.id) === String(form.category_3_id))) {
            form.category_3_id = '';
        }
    }
);

watch(
    () => form.category_2_id,
    () => {
        if (!filteredTier3.value.some((item) => String(item.id) === String(form.category_3_id))) {
            form.category_3_id = '';
        }
    }
);

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
                <div class="space-y-3 rounded border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-900">Category</p>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <select v-model="form.category_1_id" class="ss-input">
                            <option value="">Category 1 (existing)</option>
                            <option v-for="category in props.categoryTier1" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <input v-model="form.category_1_name" class="ss-input" placeholder="Category 1 (new name)" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <select v-model="form.category_2_id" class="ss-input">
                            <option value="">Category 2 (existing)</option>
                            <option v-for="category in filteredTier2" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <input v-model="form.category_2_name" class="ss-input" placeholder="Category 2 (new name)" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <select v-model="form.category_3_id" class="ss-input">
                            <option value="">Category 3 (existing)</option>
                            <option v-for="category in filteredTier3" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <input v-model="form.category_3_name" class="ss-input" placeholder="Category 3 (new name)" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <select v-model="form.language_id" class="ss-input">
                        <option value="">Language (existing)</option>
                        <option v-for="language in props.languages" :key="language.id" :value="language.id">{{ language.name }}</option>
                    </select>
                    <input v-model="form.language_name" class="ss-input" placeholder="Language (new name)" />
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
