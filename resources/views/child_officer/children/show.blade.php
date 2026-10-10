<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $child->name_english ?? $child->child_code }} | Child Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    @vite(['resources/css/child_officer/childdashboard.css'])
</head>
<body class="bg-slate-50 text-slate-800 p-6 min-h-screen font-sans">
    <div class="max-w-6xl mx-auto">
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('child-officer.children.index') }}" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Child Profile</h1>
                    <p class="text-slate-500 mt-1">Detailed information and QR Code</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('child-officer.children.edit', $child->id) }}" class="bg-amber-100 text-amber-700 hover:bg-amber-200 px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fa-solid fa-pen"></i> Edit Profile
                </a>
                <button onclick="window.print()" class="bg-slate-800 text-white hover:bg-slate-900 px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Print Profile
                </button>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Key Info & QR Code -->
            <div class="space-y-6">
                <!-- QR Code Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col items-center text-center">
                    <div class="w-40 h-40 bg-slate-50 border border-slate-200 p-2 rounded-xl mb-4">
                        <img src="{{ $qrCodeUrl }}" alt="QR Code for {{ $child->child_code }}" class="w-full h-full object-contain">
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $child->child_code }}</h2>
                    <p class="text-sm text-slate-500 mb-4">Scan to view complete profile</p>
                    <a href="{{ $qrCodeUrl }}" download="QR_{{ $child->child_code }}.png" target="_blank" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-4 py-2 rounded-lg text-sm font-semibold transition inline-flex items-center gap-2 w-full justify-center">
                        <i class="fa-solid fa-download"></i> Download QR Code
                    </a>
                </div>

                <!-- Basic Info Summary Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Identity Overview</h3>
                    <ul class="space-y-4">
                        <li>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">English Name</span>
                            <span class="text-slate-900 font-medium">{{ $child->name_english ?: 'N/A' }}</span>
                        </li>
                        <li>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Korean Name</span>
                            <span class="text-slate-900 font-medium">{{ $child->name_korean ?: 'N/A' }}</span>
                        </li>
                        <li>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Age / Gender</span>
                            <span class="text-slate-900 font-medium">{{ $child->age ?? '-' }} yrs / {{ $child->gender ?: 'N/A' }}</span>
                        </li>
                        <li>
                            <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Religion</span>
                            <span class="text-slate-900 font-medium">{{ $child->religion ?: 'N/A' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Detailed Sections -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Admin & Status Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-building text-sky-600 mr-2"></i>Administrative Details</h2>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Office</span>
                            <span class="text-slate-900 font-medium">{{ $child->office_name ?: 'N/A' }} ({{ $child->office_code ?: '-' }})</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Area</span>
                            <span class="text-slate-900 font-medium">{{ $child->area ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Service State</span>
                            <span class="inline-block mt-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full">{{ $child->service_state ?: 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Sponsor State</span>
                            <span class="inline-block mt-1 px-2.5 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">{{ $child->sponsor_state ?: 'Unknown' }}</span>
                        </div>
                        <div class="col-span-2 mt-2 pt-4 border-t border-slate-100 grid grid-cols-2 gap-6">
                            <div>
                                <span class="block text-xs font-semibold text-slate-500 uppercase">Guardian Type</span>
                                <span class="text-slate-900 font-medium">{{ $child->guardian_type ?: 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-slate-500 uppercase">Caregiver</span>
                                <span class="text-slate-900 font-medium">{{ $child->caregiver ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Education & Dream -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-graduation-cap text-indigo-600 mr-2"></i>Education & Interests</h2>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4">
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Curriculum</span>
                            <span class="text-slate-900 font-medium">{{ $child->curriculum ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Grade</span>
                            <span class="text-slate-900 font-medium">{{ $child->grade ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Pass/Fail Status</span>
                            <span class="text-slate-900 font-medium">{{ $child->pass_fail ?: 'N/A' }}</span>
                        </div>
                        <div class="col-span-2 md:col-span-3 pt-2">
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Dream Profession</span>
                            <span class="text-slate-900 font-medium">{{ $child->dream ?: 'N/A' }}</span>
                            <p class="text-slate-600 text-sm mt-1 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ $child->dream_description ?: 'No description provided.' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Favorite Subject</span>
                            <span class="text-slate-900 font-medium">{{ $child->favorite_subject ?: 'N/A' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Favorite Activity</span>
                            <span class="text-slate-900 font-medium">{{ $child->favorite_activity ?: 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Health & Disability -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-notes-medical text-rose-500 mr-2"></i>Health & Disability</h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Health Status</span>
                            <span class="text-slate-900 font-medium">{{ $child->health ?: 'N/A' }}</span>
                            <p class="text-slate-600 text-sm mt-1">{{ $child->health_description ?: 'No additional health details provided.' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Disability Type</span>
                            <span class="text-slate-900 font-medium">{{ $child->disability_type ?: 'None' }}</span>
                            <p class="text-slate-600 text-sm mt-1">{{ $child->disability_description ?: 'No disability details provided.' }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
