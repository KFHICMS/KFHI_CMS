<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Child Officer Dashboard | KFHI Child Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    {{-- Assuming same assets or you can create specific ones --}}
    @vite(['resources/css/child_officer/childdashboard.css', 'resources/js/child_officer/childdashboard.js'])
    
    <script>
        // Simple script for sidebar dropdown toggles
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.classList.add('rotate-180');
            } else {
                el.classList.add('hidden');
                if(icon) icon.classList.remove('rotate-180');
            }
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen md:flex">
        <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-950/50 md:hidden" aria-hidden="true"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col overflow-y-auto bg-emerald-950 text-white transition-transform duration-300 dashboard-scrollbar md:sticky md:top-0 md:h-screen md:flex-none md:translate-x-0 md:transition-[width] md:duration-300">
            <a href="#" class="flex h-20 items-center gap-3 border-b border-white/10 px-7 shrink-0">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400 text-xl text-emerald-950">
                    <i class="fa-solid fa-seedling"></i>
                </span>
                <span>
                    <span class="block text-sm font-extrabold tracking-[0.12em]">KFHI CHILD</span>
                    <span class="mt-0.5 block text-xs font-medium tracking-wider text-emerald-300">MANAGEMENT SYSTEM</span>
                </span>
            </a>

            <div class="px-5 pt-8 pb-4">
                <p class="px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-400">Workspace</p>
                <nav class="mt-3 space-y-1.5" aria-label="Main navigation">
                    <a href="#" aria-current="page" class="flex items-center gap-3 rounded-xl bg-emerald-800 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/20">
                        <span aria-hidden="true" class="inline-flex h-4 w-4 items-center justify-center"><i class="fa-solid fa-gauge-high text-xs"></i></span> Dashboard
                    </a>
                    
                    <!-- Child Management -->
                    <div>
                        <button type="button" onclick="toggleDropdown('child-management-menu')" class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true" class="inline-flex h-4 w-4 items-center justify-center"><i class="fa-solid fa-children text-xs"></i></span> Child Management
                            </div>
                            <i id="child-management-menu-icon" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i>
                        </button>
                        <div id="child-management-menu" class="mt-1 space-y-1 pl-11 hidden">
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Register Child</a>
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Search Child</a>
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Edit Child</a>
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">View Child (Profile)</a>
                        </div>
                    </div>

                    <!-- QR Management -->
                    <div>
                        <button type="button" onclick="toggleDropdown('qr-management-menu')" class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true" class="inline-flex h-4 w-4 items-center justify-center"><i class="fa-solid fa-qrcode text-xs"></i></span> QR Management
                            </div>
                            <i id="qr-management-menu-icon" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i>
                        </button>
                        <div id="qr-management-menu" class="mt-1 space-y-1 pl-11 hidden">
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Generate QR</a>
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Scan QR</a>
                        </div>
                    </div>

                    <!-- Follow Ups -->
                    <div>
                        <button type="button" onclick="toggleDropdown('follow-ups-menu')" class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true" class="inline-flex h-4 w-4 items-center justify-center"><i class="fa-solid fa-clipboard-check text-xs"></i></span> Follow Ups
                            </div>
                            <i id="follow-ups-menu-icon" class="fa-solid fa-chevron-down text-[10px] transition-transform"></i>
                        </button>
                        <div id="follow-ups-menu" class="mt-1 space-y-1 pl-11 hidden">
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Pending</a>
                            <a href="#" class="block rounded-lg px-3 py-2 text-sm font-medium text-emerald-200/70 transition hover:bg-white/5 hover:text-white">Updates</a>
                        </div>
                    </div>

                    <!-- Reports -->
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true" class="inline-flex h-4 w-4 items-center justify-center"><i class="fa-solid fa-chart-pie text-xs"></i></span> Reports
                    </a>
                </nav>
            </div>

            <div class="mt-auto p-5 shrink-0">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold">CO</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">Child Officer</span>
                            <span class="mt-0.5 block text-xs text-emerald-200/70">Field team</span>
                        </span>
                    </div>
                </div>
                <p class="mt-4 px-1 text-xs text-emerald-200/50">Child Management System</p>
            </div>
        </aside>

        <main class="min-w-0 flex-1 flex flex-col h-screen overflow-hidden">
            <header class="shrink-0 sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-10">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50">
                        <i id="sidebarToggleIcon" class="fa-solid fa-bars" aria-hidden="true"></i>
                    </button>
                    <div>
                        <p class="text-xs font-medium text-slate-400">Workspace / Child Officer</p>
                        <h1 class="text-lg font-bold tracking-tight text-slate-900">Dashboard</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:inline-flex sm:items-center sm:gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Online
                    </span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800">C</span>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto dashboard-scrollbar">
                <div class="mx-auto max-w-7xl space-y-7 px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
                    <!-- Welcome Section -->
                    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-700 px-6 py-7 text-white shadow-xl shadow-emerald-900/10 sm:px-9 sm:py-9">
                        <div class="pointer-events-none absolute -right-10 -top-24 h-72 w-72 rounded-full border-[36px] border-white/5"></div>
                        <div class="pointer-events-none absolute -bottom-32 right-40 h-64 w-64 rounded-full bg-emerald-400/10 blur-2xl"></div>
                        <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                            <div>
                                @php
                                    $hour = now()->hour;
                                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                                @endphp
                                <p class="text-sm font-semibold text-emerald-200">Your daily field overview</p>
                                <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl"><span id="dashboardGreeting">{{ $greeting }}</span>, Officer! <span aria-hidden="true" class="inline-block text-emerald-100"><i class="fa-solid fa-hand-wave"></i></span></h2>
                                <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-50/80">Manage children profiles, track your follow-ups, and generate quick QR reports from here.</p>
                            </div>
                            <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                                <p class="text-xs font-medium text-emerald-100/75">Today</p>
                                <p class="mt-1 text-sm font-bold">{{ now()->format('l, M j, Y') }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Key Statistics for Child Officer -->
                    <section aria-label="Key statistics" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl text-emerald-700"><i class="fa-solid fa-child"></i></span>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Assigned</span>
                            </div>
                            <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['assigned_children'] ?? 42) }}</p>
                            <p class="mt-1 text-sm text-slate-500">Children under your care</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-700"><i class="fa-solid fa-clipboard-list"></i></span>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Pending</span>
                            </div>
                            <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['pending_followups'] ?? 8) }}</p>
                            <p class="mt-1 text-sm text-slate-500">Follow-ups this week</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-xl text-sky-700"><i class="fa-solid fa-qrcode"></i></span>
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">Scanned</span>
                            </div>
                            <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['qr_scanned'] ?? 156) }}</p>
                            <p class="mt-1 text-sm text-slate-500">QRs scanned this month</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-xl text-violet-700"><i class="fa-solid fa-file-contract"></i></span>
                                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700">Reports</span>
                            </div>
                            <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['reports_submitted'] ?? 12) }}</p>
                            <p class="mt-1 text-sm text-slate-500">Reports submitted</p>
                        </article>
                    </section>

                    <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                        <!-- Quick Actions based on workflow -->
                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
                            <div>
                                <h3 class="font-bold text-slate-900">Child Management Shortcuts</h3>
                                <p class="mt-1 text-sm text-slate-500">Quickly access essential tools from your workflow</p>
                            </div>
                            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/60">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-xl text-emerald-700 transition group-hover:bg-emerald-200"><i class="fa-solid fa-user-plus"></i></span>
                                    <span><span class="block text-sm font-semibold text-slate-800">Register Child</span><span class="mt-1 block text-xs text-slate-500">Add a new child to the system</span></span>
                                </a>
                                <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/60">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-xl text-amber-700 transition group-hover:bg-amber-200"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <span><span class="block text-sm font-semibold text-slate-800">Search Child</span><span class="mt-1 block text-xs text-slate-500">Find profiles and update records</span></span>
                                </a>
                                <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50/60">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-xl text-sky-700 transition group-hover:bg-sky-200"><i class="fa-solid fa-qrcode"></i></span>
                                    <span><span class="block text-sm font-semibold text-slate-800">Scan QR Code</span><span class="mt-1 block text-xs text-slate-500">Track attendance and participation</span></span>
                                </a>
                                <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-violet-300 hover:bg-violet-50/60">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-xl text-violet-700 transition group-hover:bg-violet-200"><i class="fa-solid fa-file-circle-check"></i></span>
                                    <span><span class="block text-sm font-semibold text-slate-800">Submit Report</span><span class="mt-1 block text-xs text-slate-500">Fill in latest activity or status reports</span></span>
                                </a>
                            </div>
                        </article>

                        <!-- Pending Follow Ups Mini List -->
                        <article class="flex min-h-[390px] flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-bold text-slate-900">Upcoming Follow-ups</h3>
                                    <p class="mt-1 text-sm text-slate-500">Tasks requiring your attention</p>
                                </div>
                                <span class="rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">3 Pending</span>
                            </div>

                            <div class="dashboard-scrollbar mt-5 flex-1 space-y-3 overflow-y-auto pr-1">
                                <!-- Dummy Data for visual -->
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-slate-800">Umar Faizer</h4>
                                            <p class="mt-0.5 text-xs text-slate-500">Health Checkup</p>
                                        </div>
                                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-600">Today</span>
                                    </div>
                                    <a href="#" class="mt-3 inline-block rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">View Profile</a>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-slate-800">Shahid Bhai</h4>
                                            <p class="mt-0.5 text-xs text-slate-500">Education Support Delivery</p>
                                        </div>
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-600">Tomorrow</span>
                                    </div>
                                    <a href="#" class="mt-3 inline-block rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">View Profile</a>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0">
                                            <h4 class="truncate text-sm font-semibold text-slate-800">pugalu</h4>
                                            <p class="mt-0.5 text-xs text-slate-500">Guardian Meeting</p>
                                        </div>
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600">In 3 days</span>
                                    </div>
                                    <a href="#" class="mt-3 inline-block rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">View Profile</a>
                                </div>
                            </div>
                        </article>
                    </section>

                    @if (session('success'))
                        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
