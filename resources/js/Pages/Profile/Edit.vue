<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-slate-900"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="ss-card"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :office-locations="officeLocations"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="ss-card"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="ss-card"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
