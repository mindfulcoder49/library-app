<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    reports: Object,
});
</script>

<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="ss-title">Reports</h2>
        </template>

        <div class="ss-page-shell space-y-6 py-8">
            <div class="ss-card" v-for="(report, reportName) in reports" :key="reportName">
                <h3 class="mb-3 text-lg font-semibold capitalize text-slate-900">{{ reportName.replace('_', ' ') }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-for="(group, groupName) in report" :key="groupName" class="ss-card-soft">
                        <h4 class="font-semibold text-slate-900">{{ groupName }}</h4>
                        <ul class="mt-2 list-inside list-disc text-sm text-slate-700">
                            <li v-for="(count, key) in group" :key="key">{{ key }}: {{ count }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
