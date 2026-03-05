<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
    languages: Array,
});
const page = usePage();
const importPreview = page.props.flash?.import_preview;

const form = useForm({
    title: '',
    isbn10: '',
    isbn13: '',
    description: '',
    category_id: '',
    language_id: '',
    book_type: 'hard_copy',
    lender_comments: '',
    expected_return_date: '',
    authors: [{ first_name: '', last_name: '' }],
});

const importForm = useForm({
    csv_file: null,
    dry_run: false,
});

const addAuthor = () => {
    form.authors.push({ first_name: '', last_name: '' });
};

const submit = () => {
    form.post(route('books.store'));
};

const onCsvSelected = (event) => {
    importForm.csv_file = event.target.files[0];
};

const submitCsv = () => {
    importForm.dry_run = false;
    importForm.post(route('books.import-csv'), {
        forceFormData: true,
    });
};

const previewCsv = () => {
    importForm.dry_run = true;
    importForm.post(route('books.import-csv'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Add Book" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Add New Book to Shelf</h2>
        </template>

        <div class="mx-auto max-w-4xl space-y-6 py-8 sm:px-6 lg:px-8">
            <section class="ss-card">
                <h3 class="text-lg font-semibold text-slate-900">Bulk Upload via CSV</h3>
                <p class="mt-2 text-sm text-slate-700">
                    Upload a CSV/TSV with columns:
                    <span class="font-medium">ISBN-Emp, ISBN, Title, Author, Language, Book Type, Category 1-3, Description, Lender Comments, Lender ID, Office Locations, Status</span>. For non-admin users, uploaded books are always assigned to your own account and CSV Lender ID is ignored.
                </p>
                <p class="mt-1 text-sm text-slate-700">Office locations can be comma-separated (example: <span class="font-medium">OCB, CCB, JAB</span>).</p>
                <div class="mt-3">
                    <Link :href="route('books.import-template')" class="text-sm font-medium text-cyan-700 hover:text-cyan-800">
                        Download CSV template
                    </Link>
                </div>

                <form @submit.prevent="submitCsv" class="mt-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <input
                        type="file"
                        accept=".csv,.txt,text/csv,text/plain"
                        class="block w-full rounded border border-slate-300 bg-white text-sm file:mr-4 file:rounded file:border-0 file:bg-cyan-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-cyan-500"
                        @change="onCsvSelected"
                    />
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="ss-btn-secondary disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="importForm.processing || !importForm.csv_file"
                            @click="previewCsv"
                        >
                            {{ importForm.processing ? 'Working...' : 'Preview (Dry Run)' }}
                        </button>
                        <button
                            type="submit"
                            class="ss-btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="importForm.processing || !importForm.csv_file"
                        >
                            {{ importForm.processing ? 'Importing...' : 'Import CSV' }}
                        </button>
                    </div>
                </form>
                <p v-if="importForm.errors.csv_file" class="mt-2 text-sm text-red-600">{{ importForm.errors.csv_file }}</p>
                <p v-if="importForm.errors.dry_run" class="mt-2 text-sm text-red-600">{{ importForm.errors.dry_run }}</p>

                <div v-if="importPreview" class="mt-5 rounded border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ importPreview.is_dry_run ? 'Dry Run Preview' : 'Last Import Summary' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-700">
                        Imported: {{ importPreview.imported }} | Failed: {{ importPreview.failed }}
                    </p>

                    <div v-if="importPreview.errors?.length" class="mt-3 rounded border border-amber-200 bg-amber-50 p-3">
                        <p class="text-sm font-medium text-amber-900">Row Errors</p>
                        <ul class="mt-1 list-disc pl-5 text-xs text-amber-900">
                            <li v-for="(error, index) in importPreview.errors" :key="index">{{ error }}</li>
                        </ul>
                    </div>

                    <div v-if="importPreview.sample_rows?.length" class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-100 text-slate-800">
                                <tr>
                                    <th class="px-2 py-1">Row</th>
                                    <th class="px-2 py-1">ISBN-Emp</th>
                                    <th class="px-2 py-1">Title</th>
                                    <th class="px-2 py-1">Author</th>
                                    <th class="px-2 py-1">Lender ID</th>
                                    <th class="px-2 py-1">Status</th>
                                    <th class="px-2 py-1">Offices</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sample in importPreview.sample_rows" :key="sample.row" class="border-t border-slate-100 align-top">
                                    <td class="px-2 py-1">{{ sample.row }}</td>
                                    <td class="px-2 py-1">{{ sample.isbn_emp }}</td>
                                    <td class="px-2 py-1">{{ sample.title }}</td>
                                    <td class="px-2 py-1">{{ sample.author }}</td>
                                    <td class="px-2 py-1">{{ sample.lender_id }}</td>
                                    <td class="px-2 py-1">{{ sample.status }}</td>
                                    <td class="px-2 py-1">{{ sample.offices?.join(', ') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

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
                        {{ form.processing ? 'Submitting...' : 'Submit Book' }}
                    </button>
                    <Link :href="route('books.mine')" class="ss-btn-secondary">Cancel</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
