<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Management | KFHI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    @vite(['resources/css/child_officer/childdashboard.css'])
</head>
<body class="bg-slate-50 text-slate-800 p-6 min-h-screen font-sans">
    <div class="max-w-7xl mx-auto">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Child Management</h1>
                <p class="text-slate-500 mt-1">Manage and view all registered children</p>
            </div>
            <a href="{{ route('child-officer.children.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-medium transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Register New Child
            </a>
        </header>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Search & Filter Bar -->
            <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap gap-4 items-center justify-between">
                <form method="GET" action="{{ route('child-officer.children.index') }}" class="flex w-full md:w-auto items-center gap-3">
                    <div class="relative w-full md:w-80">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or code..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
                    </div>
                    <button type="submit" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition">Search</button>
                    @if(request('search'))
                        <a href="{{ route('child-officer.children.index') }}" class="text-sm text-rose-600 hover:text-rose-700 font-medium">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Child Code</th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Age / Gender</th>
                            <th class="px-6 py-4">Office</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($children as $child)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $child->child_code }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $child->name_english ?: 'N/A' }}</div>
                                <div class="text-xs text-slate-500">{{ $child->name_korean }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $child->age ?? '-' }} yrs / {{ $child->gender ?: '-' }}</td>
                            <td class="px-6 py-4">{{ $child->office_name ?: '-' }}</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('child-officer.children.show', $child->id) }}" class="text-sky-600 hover:text-sky-800 transition" title="View Profile"><i class="fa-solid fa-eye"></i></a>
                                <a href="{{ route('child-officer.children.edit', $child->id) }}" class="text-amber-600 hover:text-amber-800 transition" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form action="{{ route('child-officer.children.destroy', $child->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this child?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 transition" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                <div class="text-4xl mb-3 text-slate-300"><i class="fa-solid fa-folder-open"></i></div>
                                <p class="text-base font-medium text-slate-600">No children found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($children->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $children->links() }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>
