<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_10%,rgba(14,165,233,0.14),transparent_30%),radial-gradient(circle_at_90%_0%,rgba(37,99,235,0.16),transparent_28%)]"></div>

        <div class="mb-6">
            <Link href="/" class="inline-flex items-center gap-3">
                <ApplicationLogo class="h-16 w-16 fill-current text-sky-700" />
                <div class="hidden sm:block">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Check It Out</p>
                    <p class="text-2xl font-semibold text-slate-900">Check It Out</p>
                </div>
            </Link>
        </div>

        <div class="w-full overflow-hidden rounded-2xl border border-sky-100 bg-white p-6 shadow-[0_18px_42px_rgba(2,6,23,0.1)] sm:max-w-md">
            <slot />
        </div>
    </div>
</template>
