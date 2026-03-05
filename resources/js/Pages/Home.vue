<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Check It Out" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="relative isolate overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(14,165,233,0.28),transparent_40%),radial-gradient(circle_at_85%_15%,rgba(37,99,235,0.30),transparent_35%),linear-gradient(120deg,#020617_0%,#0b1120_45%,#0f172a_100%)]"></div>
            <div class="absolute -left-28 top-20 h-64 w-64 rounded-full border border-cyan-300/20"></div>
            <div class="absolute -right-20 top-40 h-80 w-80 rounded-full border border-blue-300/20"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 sm:px-8 lg:px-10">
                <header class="flex items-center justify-between">
                    <div class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-200">State Street | Check It Out</div>
                    <nav v-if="canLogin" class="flex items-center gap-3 text-sm">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-full border border-cyan-300/40 px-4 py-2 text-cyan-100 transition hover:bg-cyan-400/10"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="rounded-full border border-white/20 px-4 py-2 transition hover:bg-white/10">Log in</Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-full bg-cyan-400 px-4 py-2 font-semibold text-slate-950 transition hover:bg-cyan-300"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </header>

                <main class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-12 lg:gap-8">
                    <section class="lg:col-span-7">
                        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.24em] text-blue-200">Belonging Through Shared Reading</p>
                        <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl">
                            Build a stronger DEI culture with a global, employee-powered library.
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-relaxed text-slate-200/90 sm:text-lg">
                            Check It Out helps State Street teams share impactful books, spark discussion, and broaden perspectives across offices and virtual communities.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link
                                :href="route('catalog.index')"
                                class="rounded-full bg-blue-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-400"
                            >
                                Browse Catalog
                            </Link>
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('books.create')"
                                class="rounded-full border border-cyan-200/50 px-5 py-3 text-sm font-semibold text-cyan-50 transition hover:bg-cyan-200/10"
                            >
                                Add or Import Books
                            </Link>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-white/15 bg-white/5 p-6 backdrop-blur lg:col-span-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-200">How It Works</p>
                        <div class="mt-5 space-y-4 text-sm text-slate-100/90">
                            <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4">
                                <p class="font-semibold text-white">1. Share</p>
                                <p class="mt-1">Employees list books from their own shelves, including DEI and leadership titles.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4">
                                <p class="font-semibold text-white">2. Borrow</p>
                                <p class="mt-1">Colleagues request available copies and coordinate handoffs by office location.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4">
                                <p class="font-semibold text-white">3. Grow</p>
                                <p class="mt-1">Track circulation and build a richer, more inclusive learning culture together.</p>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>
