<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    officeLocations: {
        type: Array,
        default: () => [],
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    first_name: user.first_name ?? '',
    last_name: user.last_name ?? '',
    employee_id: user.employee_id ?? '',
    email: user.email,
    office_location_id: user.office_location_id ?? '',
    share_location_ids: user.share_locations?.map((office) => office.id) ?? [],
    is_lender: !!user.is_lender,
    is_borrower: !!user.is_borrower,
    agree_lender_guidelines: !!user.agree_lender_guidelines,
    agree_borrower_guidelines: !!user.agree_borrower_guidelines,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-slate-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Update your account profile, office preferences, and sharing roles.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="First Name" />
                    <TextInput id="first_name" type="text" class="mt-1 block w-full" v-model="form.first_name" required />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Last Name" />
                    <TextInput id="last_name" type="text" class="mt-1 block w-full" v-model="form.last_name" required />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div>
                <InputLabel for="employee_id" value="Employee ID" />
                <TextInput id="employee_id" type="text" class="mt-1 block w-full" v-model="form.employee_id" required />
                <InputError class="mt-2" :message="form.errors.employee_id" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="office_location_id" value="Office Location" />
                <select id="office_location_id" v-model="form.office_location_id" class="mt-1 block w-full ss-select">
                    <option value="">Select office</option>
                    <option v-for="office in officeLocations" :key="office.id" :value="office.id">{{ office.name }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.office_location_id" />
            </div>

            <div>
                <InputLabel for="share_location_ids" value="Office Locations to Share With" />
                <select id="share_location_ids" v-model="form.share_location_ids" multiple class="mt-1 block w-full ss-select">
                    <option v-for="office in officeLocations" :key="office.id" :value="office.id">{{ office.name }}</option>
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
                <InputError :message="form.errors.agree_lender_guidelines" />

                <label class="flex items-center gap-2">
                    <input v-model="form.is_borrower" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Borrower Role</span>
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="form.agree_borrower_guidelines" type="checkbox" class="rounded border-sky-300 text-sky-700 focus:ring-sky-500" />
                    <span>Agree to Borrower Guidelines</span>
                </label>
                <InputError :message="form.errors.agree_borrower_guidelines" />
                <Link :href="route('guidelines')" class="inline-flex text-sm font-semibold text-sky-700 underline hover:text-sky-900">
                    View full Guidelines page
                </Link>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-900">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-slate-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
