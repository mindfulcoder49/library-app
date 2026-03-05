<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const flash = usePage().props.flash;
</script>

<template>
    <div class="min-h-screen">
        <nav class="border-b border-sky-100 bg-white/95 backdrop-blur">
            <div class="ss-page-shell">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="inline-flex items-center gap-2">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-sky-800" />
                                <span class="hidden text-xs font-semibold uppercase tracking-[0.2em] text-sky-700 lg:inline">Check It Out</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-5 sm:-my-px sm:ms-8 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                            <NavLink :href="route('catalog.index')" :active="route().current('catalog.index')">Browse</NavLink>
                            <NavLink :href="route('books.mine')" :active="route().current('books.mine') || route().current('books.create')">My Books</NavLink>
                            <NavLink :href="route('loans.borrowed')" :active="route().current('loans.borrowed')">Borrowed</NavLink>
                            <NavLink :href="route('loans.requests')" :active="route().current('loans.requests')">Requests</NavLink>
                            <NavLink :href="route('reports.index')" :active="route().current('reports.index')">Reports</NavLink>
                            <NavLink :href="route('help.index')" :active="route().current('help.index')">Help</NavLink>
                            <NavLink :href="route('guidelines')" :active="route().current('guidelines')">Guidelines</NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-sky-100 hover:text-slate-900 focus:outline-none"
                                        >
                                            {{ $page.props.auth.user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 transition hover:bg-sky-50 hover:text-sky-700 focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="border-t border-sky-100 bg-white sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('catalog.index')" :active="route().current('catalog.index')">Browse</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('books.mine')" :active="route().current('books.mine') || route().current('books.create')">My Books</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('loans.borrowed')" :active="route().current('loans.borrowed')">Borrowed</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('loans.requests')" :active="route().current('loans.requests')">Requests</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('reports.index')" :active="route().current('reports.index')">Reports</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('help.index')" :active="route().current('help.index')">Help</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('guidelines')" :active="route().current('guidelines')">Guidelines</ResponsiveNavLink>
                </div>

                <div class="border-t border-sky-100 pb-2 pt-3">
                    <div class="px-4">
                        <div class="text-base font-semibold text-slate-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-slate-600">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-2 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header class="border-b border-sky-100 bg-white/80" v-if="$slots.header">
            <div class="ss-page-shell py-5">
                <slot name="header" />
            </div>
        </header>

        <div v-if="flash.success" class="ss-page-shell mt-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                {{ flash.success }}
            </div>
        </div>
        <div v-if="flash.warning" class="ss-page-shell mt-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
                {{ flash.warning }}
            </div>
        </div>
        <div v-if="flash.error" class="ss-page-shell mt-4">
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                {{ flash.error }}
            </div>
        </div>

        <main>
            <slot />
        </main>
    </div>
</template>
