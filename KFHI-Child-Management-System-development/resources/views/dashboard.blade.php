<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Child Management System - Dashboard</title>
    <!-- Tailwind CSS CDN for rapid layout styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-64 bg-emerald-900 text-white flex flex-col transition-all duration-300 ease-in-out">
            <div class="p-5 text-lg font-bold tracking-wider border-b border-emerald-800 flex items-center justify-between">
                <span class="truncate">NGO CHILD MGMT</span>
            </div>
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 rounded bg-emerald-800 text-white font-medium">Dashboard</a>
                <a href="#" class="block px-4 py-2.5 rounded hover:bg-emerald-800 text-emerald-200">User Management</a>
                <a href="#" class="block px-4 py-2.5 rounded hover:bg-emerald-800 text-emerald-200">Children</a>
                <a href="#" class="block px-4 py-2.5 rounded hover:bg-emerald-800 text-emerald-200">Programs</a>
                <a href="#" class="block px-4 py-2.5 rounded hover:bg-emerald-800 text-emerald-200">Activities</a>
                <a href="#" class="block px-4 py-2.5 rounded hover:bg-emerald-800 text-emerald-200">Reports</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col overflow-y-auto">
            
            <!-- Top Navbar -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8">
                <div class="flex items-center space-x-4">
                    <!-- Sidebar Toggle Button -->
                    <button id="sidebarToggle" class="text-gray-600 hover:text-emerald-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-600">Admin / Manager</span>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-8 space-y-6">
                
                <!-- Greeting -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Good Morning, Admin! 👋</h2>
                    <p class="text-sm text-gray-500">Here's your complete NGO system overview.</p>
                </div>

                <!-- Stat Cards Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['total_children']) }}</p>
                            <p class="text-sm text-gray-400 mt-1">Total Children</p>
                        </div>
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">👶</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['active_programs'] }}</p>
                            <p class="text-sm text-gray-400 mt-1">Active Programs</p>
                        </div>
                        <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">📁</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['officers'] }}</p>
                            <p class="text-sm text-gray-400 mt-1">Officers</p>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">👥</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">{{ $stats['activities'] }}</p>
                            <p class="text-sm text-gray-400 mt-1">Activities This Month</p>
                        </div>
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">📊</div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">QUICK ACTIONS</h3>
                    <p class="text-xs text-gray-400 mb-4">Frequently used administrative actions</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="#" class="p-4 rounded-xl border border-dashed border-gray-300 hover:border-emerald-500 flex flex-col items-center justify-center text-emerald-600 hover:bg-emerald-50 transition">
                            <span class="text-xl font-bold">+</span>
                            <span class="text-sm font-medium mt-1">Add Child</span>
                        </a>
                        <a href="#" class="p-4 rounded-xl border border-dashed border-gray-300 hover:border-emerald-500 flex flex-col items-center justify-center text-emerald-600 hover:bg-emerald-50 transition">
                            <span class="text-xl font-bold">📁</span>
                            <span class="text-sm font-medium mt-1">Create Program</span>
                        </a>
                        <a href="#" class="p-4 rounded-xl border border-dashed border-gray-300 hover:border-emerald-500 flex flex-col items-center justify-center text-emerald-600 hover:bg-emerald-50 transition">
                            <span class="text-xl font-bold">📋</span>
                            <span class="text-sm font-medium mt-1">Create Activity</span>
                        </a>
                        <a href="#" class="p-4 rounded-xl border border-dashed border-gray-300 hover:border-emerald-500 flex flex-col items-center justify-center text-emerald-600 hover:bg-emerald-50 transition">
                            <span class="text-xl font-bold">👥</span>
                            <span class="text-sm font-medium mt-1">Add Officer</span>
                        </a>
                    </div>
                </div>

                <!-- CHARTS SECTION (Donut Chart & Bar Graph) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Donut Chart Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                        <div class="w-full flex justify-between items-center mb-4">
                            <h3 class="text-md font-bold text-gray-800">Children Distribution by Category</h3>
                        </div>
                        <div class="w-64 h-64">
                            <canvas id="donutChart"></canvas>
                        </div>
                    </div>

                    <!-- Bar Graph Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center">
                        <div class="w-full flex justify-between items-center mb-4">
                            <h3 class="text-md font-bold text-gray-800">Monthly Activities Overview</h3>
                        </div>
                        <div class="w-full h-64">
                            <canvas id="barGraph"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- JavaScript for Sidebar Toggle & Charts -->
    <script>
        // Sidebar Toggle Logic
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-ml-64');
        });

        // Donut Chart
        const donutCtx = document.getElementById('donutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: @json($chartData['pie_labels']),
                datasets: [{
                    data: @json($chartData['pie_values']),
                    backgroundColor: ['#059669', '#34d399', '#fbbf24', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Bar Graph
        const barCtx = document.getElementById('barGraph').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: @json($chartData['bar_labels']),
                datasets: [{
                    label: 'Activities Completed',
                    data: @json($chartData['bar_values']),
                    backgroundColor: '#059669',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>