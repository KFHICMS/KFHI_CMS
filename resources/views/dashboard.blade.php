<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard | NGO Child Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .dashboard-scrollbar::-webkit-scrollbar { width: 6px; }
        .dashboard-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="min-h-screen md:flex">
        <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-950/50 md:hidden" aria-hidden="true"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-emerald-950 text-white transition-transform duration-300 md:sticky md:top-0 md:h-screen md:translate-x-0">
            <a href="{{ route('dashboard') }}" class="flex h-20 items-center gap-3 border-b border-white/10 px-7">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400 text-xl">🌱</span>
                <span>
                    <span class="block text-sm font-extrabold tracking-[0.12em]">NGO CHILD</span>
                    <span class="mt-0.5 block text-xs font-medium tracking-wider text-emerald-300">MANAGEMENT SYSTEM</span>
                </span>
            </a>

            <div class="px-5 pt-8">
                <p class="px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-400">Workspace</p>
                <nav class="mt-3 space-y-1.5" aria-label="Main navigation">
                    <a href="{{ route('dashboard') }}" aria-current="page" class="flex items-center gap-3 rounded-xl bg-emerald-800 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/20">
                        <span aria-hidden="true">▦</span> Dashboard
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true">♙</span> User Management
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true">♡</span> Children
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true">▤</span> Programs
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true">✓</span> Activities
                    </a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-emerald-100/80 transition hover:bg-white/10 hover:text-white">
                        <span aria-hidden="true">▥</span> Reports
                    </a>
                </nav>
            </div>

            <div class="mt-auto p-5">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold">AM</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">Admin / Manager</span>
                            <span class="mt-0.5 block text-xs text-emerald-200/70">Management team</span>
                        </span>
                    </div>
                </div>
                <p class="mt-4 px-1 text-xs text-emerald-200/50">Child Management System</p>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-10">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" type="button" aria-label="Open navigation" aria-expanded="false" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 md:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <p class="text-xs font-medium text-slate-400">Workspace / Overview</p>
                        <h1 class="text-lg font-bold tracking-tight text-slate-900">Dashboard</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 sm:inline-flex sm:items-center sm:gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> System overview
                    </span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800">A</span>
                </div>
            </header>

            <div class="mx-auto max-w-7xl space-y-7 px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
                <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-700 px-6 py-7 text-white shadow-xl shadow-emerald-900/10 sm:px-9 sm:py-9">
                    <div class="pointer-events-none absolute -right-10 -top-24 h-72 w-72 rounded-full border-[36px] border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-32 right-40 h-64 w-64 rounded-full bg-emerald-400/10 blur-2xl"></div>
                    <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                        <div>
                            @php
                                $hour = now()->hour;
                                $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                            @endphp
                            <p class="text-sm font-semibold text-emerald-200">Your impact at a glance</p>
                            <h2 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl"><span id="dashboardGreeting">{{ $greeting }}</span>, Admin! <span aria-hidden="true">👋</span></h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-50/80">Here’s your complete NGO system overview. Thank you for helping every child thrive.</p>
                        </div>
                        <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-xs font-medium text-emerald-100/75">Today</p>
                            <p class="mt-1 text-sm font-bold">{{ now()->format('l, M j, Y') }}</p>
                        </div>
                    </div>
                </section>

                <section aria-label="Key statistics" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl">👶</span>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Children</span>
                        </div>
                        <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['total_children']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Total children supported</p>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-xl">📁</span>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Programs</span>
                        </div>
                        <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['active_programs']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Active programs</p>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-xl">👥</span>
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">Team</span>
                        </div>
                        <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['officers']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Field officers</p>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-xl">📊</span>
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700">This month</span>
                        </div>
                        <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($stats['activities']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Activities completed</p>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                    <article class="flex min-h-[390px] flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900">Recent messages</h3>
                                <p class="mt-1 text-sm text-slate-500">Updates from your staff</p>
                            </div>
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $messages->count() }} total</span>
                        </div>

                        <div class="dashboard-scrollbar mt-5 flex-1 space-y-3 overflow-y-auto pr-1">
                            @forelse($messages as $message)
                                @php
                                    $senderName = optional($message->sender)->name ?? 'Unknown User';
                                @endphp
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold uppercase text-emerald-800">{{ substr($senderName, 0, 1) }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                                <h4 class="truncate text-sm font-semibold text-slate-800">{{ $senderName }}</h4>
                                                <time class="text-xs text-slate-400">{{ $message->created_at?->diffForHumans() }}</time>
                                            </div>
                                            <p class="mt-2 whitespace-pre-line break-words text-sm leading-5 text-slate-600">{{ $message->content }}</p>
                                            <div class="mt-3 flex items-center gap-2">
                                                <button type="button" data-reply-button data-receiver-id="{{ $message->sender_id ?? 0 }}" data-user-name="{{ $senderName }}" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Reply</button>
                                                <form action="{{ route('messages.destroy', $message->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" onclick="return confirm('Are you sure you want to delete this message?')">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex h-48 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-5 text-center">
                                    <span class="text-2xl" aria-hidden="true">✉️</span>
                                    <p class="mt-3 text-sm font-semibold text-slate-700">All caught up</p>
                                    <p class="mt-1 text-xs text-slate-500">New messages from staff will appear here.</p>
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
                        <div>
                            <h3 class="font-bold text-slate-900">Quick actions</h3>
                            <p class="mt-1 text-sm text-slate-500">Shortcuts to frequently used tools</p>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/60">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xl text-emerald-700 transition group-hover:bg-emerald-200">+</span>
                                <span><span class="block text-sm font-semibold text-slate-800">Add a child</span><span class="mt-1 block text-xs text-slate-500">Register a child in the system</span></span>
                            </a>
                            <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50/60">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-xl transition group-hover:bg-amber-200">📁</span>
                                <span><span class="block text-sm font-semibold text-slate-800">Create a program</span><span class="mt-1 block text-xs text-slate-500">Plan a new support program</span></span>
                            </a>
                            <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50/60">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-xl transition group-hover:bg-sky-200">📋</span>
                                <span><span class="block text-sm font-semibold text-slate-800">Create an activity</span><span class="mt-1 block text-xs text-slate-500">Record a new program activity</span></span>
                            </a>
                            <a href="#" class="group flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-violet-300 hover:bg-violet-50/60">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-xl transition group-hover:bg-violet-200">👥</span>
                                <span><span class="block text-sm font-semibold text-slate-800">Add an officer</span><span class="mt-1 block text-xs text-slate-500">Invite a team member</span></span>
                            </a>
                        </div>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                        <div>
                            <h3 class="font-bold text-slate-900">Children by category</h3>
                            <p class="mt-1 text-sm text-slate-500">Distribution across support categories</p>
                        </div>
                        <div class="mx-auto mt-5 h-64 max-w-sm"><canvas id="donutChart" aria-label="Children distribution by category" role="img"></canvas></div>
                    </article>

                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                        <div>
                            <h3 class="font-bold text-slate-900">Monthly activities</h3>
                            <p class="mt-1 text-sm text-slate-500">Activity completion overview</p>
                        </div>
                        <div class="mt-5 h-64"><canvas id="barGraph" aria-label="Monthly activities overview" role="img"></canvas></div>
                    </article>
                </section>

                @if (session('success'))
                    <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
                @endif
            </div>
        </main>
    </div>

    <div id="replyModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="replyModalTitle">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Staff message</p>
                    <h3 id="replyModalTitle" class="mt-1 text-lg font-bold text-slate-900">Reply to <span id="replyUserName"></span></h3>
                </div>
                <button type="button" id="closeReplyModal" aria-label="Close reply dialog" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">✕</button>
            </div>
            <form action="{{ route('messages.store') }}" method="POST" class="mt-5">
                @csrf
                <input type="hidden" name="receiver_id" id="replyReceiverId">
                <label for="replyContent" class="mb-2 block text-sm font-medium text-slate-700">Your message</label>
                <textarea id="replyContent" name="content" rows="4" class="w-full resize-y rounded-xl border border-slate-200 p-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10" placeholder="Write your reply..." required></textarea>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="cancelReply" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">Send reply</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const dashboardGreeting = document.getElementById('dashboardGreeting');

        function updateDashboardGreeting() {
            const hour = new Date().getHours();
            const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening');

            dashboardGreeting.textContent = greeting;
        }

        updateDashboardGreeting();
        window.setInterval(updateDashboardGreeting, 60 * 1000);

        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebarToggle = document.getElementById('sidebarToggle');

        function setSidebarOpen(isOpen) {
            sidebar.classList.toggle('-translate-x-full', !isOpen);
            sidebarBackdrop.classList.toggle('hidden', !isOpen);
            sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        }

        sidebarToggle.addEventListener('click', () => {
            setSidebarOpen(sidebarToggle.getAttribute('aria-expanded') !== 'true');
        });
        sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));

        const replyModal = document.getElementById('replyModal');

        function closeReplyModal() {
            replyModal.classList.add('hidden');
            replyModal.classList.remove('flex');
        }

        document.querySelectorAll('[data-reply-button]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('replyReceiverId').value = button.dataset.receiverId;
                document.getElementById('replyUserName').textContent = button.dataset.userName;
                replyModal.classList.remove('hidden');
                replyModal.classList.add('flex');
                document.getElementById('replyContent').focus();
            });
        });

        document.getElementById('closeReplyModal').addEventListener('click', closeReplyModal);
        document.getElementById('cancelReply').addEventListener('click', closeReplyModal);
        replyModal.addEventListener('click', (event) => {
            if (event.target === replyModal) closeReplyModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeReplyModal();
        });

        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: @json($chartData['pie_labels']),
                datasets: [{
                    data: @json($chartData['pie_values']),
                    backgroundColor: ['#047857', '#34d399', '#fbbf24', '#38bdf8'],
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 18 } }
                }
            }
        });

        new Chart(document.getElementById('barGraph'), {
            type: 'bar',
            data: {
                labels: @json($chartData['bar_labels']),
                datasets: [{
                    label: 'Activities completed',
                    data: @json($chartData['bar_values']),
                    backgroundColor: '#059669',
                    hoverBackgroundColor: '#047857',
                    borderRadius: 7,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                    x: { border: { display: false }, grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
