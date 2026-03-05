<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    officeLocations: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    first_name: '',
    last_name: '',
    employee_id: '',
    email: '',
    office_location_id: '',
    share_location_ids: [],
    is_lender: false,
    is_borrower: true,
    agree_lender_guidelines: false,
    agree_borrower_guidelines: false,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h1 class="mb-4 text-xl font-semibold text-slate-900">Create your account</h1>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="First Name" />
                    <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Last Name" />
                    <TextInput id="last_name" v-model="form.last_name" type="text" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div>
                <InputLabel for="employee_id" value="Employee ID" />
                <TextInput id="employee_id" v-model="form.employee_id" type="text" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.employee_id" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="office_location_id" value="Office Location" />
                <select id="office_location_id" v-model="form.office_location_id" class="mt-1 block w-full ss-select">
                    <option value="">Select office</option>
                    <option v-for="office in props.officeLocations" :key="office.id" :value="office.id">{{ office.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.office_location_id" />
            </div>

            <div>
                <InputLabel for="share_location_ids" value="Office Locations to Share With" />
                <select id="share_location_ids" v-model="form.share_location_ids" multiple class="mt-1 block w-full ss-select">
                    <option v-for="office in props.officeLocations" :key="office.id" :value="office.id">{{ office.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.share_location_ids" />
            </div>

            <div class="space-y-2 ss-card-soft">
                <label class="flex items-center gap-2">
                    <input v-model="form.is_lender" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Lender Role</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="form.agree_lender_guidelines" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Agree to Lender Guidelines</span>
                </label>
                <div v-if="form.is_lender" class="rounded-xl border border-sky-200 bg-white p-3">
                    <p class="text-sm font-semibold text-slate-900">Guidelines for Lenders</p>
                    <ul class="mt-1 list-inside list-disc space-y-1 text-sm text-slate-700">
                        <li>Willing to share the books offered</li>
                        <li>Share only books that are in good shape</li>
                        <li>Share books about Diversity, Inclusion &amp; Equity or written by Diverse &amp; Underrepresented Authors</li>
                        <li>Contact the Borrower in a timely manner and exchange the book</li>
                    </ul>
                </div>
                <InputError :message="form.errors.agree_lender_guidelines" />

                <label class="flex items-center gap-2">
                    <input v-model="form.is_borrower" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Borrower Role</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="form.agree_borrower_guidelines" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Agree to Borrower Guidelines</span>
                </label>
                <div v-if="form.is_borrower" class="rounded-xl border border-sky-200 bg-white p-3">
                    <p class="text-sm font-semibold text-slate-900">Guidelines for Borrowers</p>
                    <ul class="mt-1 list-inside list-disc space-y-1 text-sm text-slate-700">
                        <li>Treat the books borrowed respectfully</li>
                        <li>Agree with the Lender a time and place to exchange the book</li>
                        <li>Return the book on time or request an extension</li>
                    </ul>
                </div>
                <InputError :message="form.errors.agree_borrower_guidelines" />
                <Link :href="route('guidelines')" class="inline-flex text-sm font-semibold text-sky-700 underline hover:text-sky-900">
                    View full Guidelines page
                </Link>
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link :href="route('login')" class="rounded-md text-sm text-slate-600 underline hover:text-slate-900">Already registered?</Link>
                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Register</PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
