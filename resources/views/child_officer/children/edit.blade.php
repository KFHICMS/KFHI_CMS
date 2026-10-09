<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Child | KFHI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    @vite(['resources/css/child_officer/childdashboard.css'])
</head>
<body class="bg-slate-50 text-slate-800 p-6 min-h-screen font-sans">
    <div class="max-w-5xl mx-auto">
        <header class="flex items-center gap-4 mb-8">
            <a href="{{ route('child-officer.children.index') }}" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition shadow-sm">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Edit Child Profile</h1>
                <p class="text-slate-500 mt-1">Update details for {{ $child->name_english ?? $child->child_code }}</p>
            </div>
        </header>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg mb-6 shadow-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('child-officer.children.update', $child->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-id-card text-emerald-600 mr-2"></i>Basic Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Child Code *</label>
                        <input type="text" name="child_code" value="{{ old('child_code', $child->child_code) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Child Name (English) *</label>
                        <input type="text" name="name_english" value="{{ old('name_english', $child->name_english) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Child Name (Korean)</label>
                        <input type="text" name="name_korean" value="{{ old('name_korean', $child->name_korean) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Alias</label>
                        <input type="text" name="alias" value="{{ old('alias', $child->alias) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Birthday</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $child->date_of_birth ? $child->date_of_birth->format('Y-m-d') : '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none bg-white">
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender', $child->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $child->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Religion</label>
                        <input type="text" name="religion" value="{{ old('religion', $child->religion) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                </div>
            </div>

            <!-- Administrative Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-building text-sky-600 mr-2"></i>Administrative Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Area</label>
                        <input type="text" name="area" value="{{ old('area', $child->area) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Office Code</label>
                        <input type="text" name="office_code" value="{{ old('office_code', $child->office_code) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Office Name</label>
                        <input type="text" name="office_name" value="{{ old('office_name', $child->office_name) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Service State</label>
                        <input type="text" name="service_state" value="{{ old('service_state', $child->service_state) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Sponsor State</label>
                        <input type="text" name="sponsor_state" value="{{ old('sponsor_state', $child->sponsor_state) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                </div>
            </div>

            <!-- Support / Guardian -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-people-roof text-amber-600 mr-2"></i>Guardian & Support</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Guardian Type</label>
                        <input type="text" name="guardian_type" value="{{ old('guardian_type', $child->guardian_type) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Caregiver Name</label>
                        <input type="text" name="caregiver" value="{{ old('caregiver', $child->caregiver) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                </div>
            </div>

            <!-- Education -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-graduation-cap text-indigo-600 mr-2"></i>Education & Interests</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Curriculum</label>
                        <input type="text" name="curriculum" value="{{ old('curriculum', $child->curriculum) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Grade</label>
                        <input type="text" name="grade" value="{{ old('grade', $child->grade) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Favorite Subject</label>
                        <input type="text" name="favorite_subject" value="{{ old('favorite_subject', $child->favorite_subject) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Pass/Fail Status</label>
                        <input type="text" name="pass_fail" value="{{ old('pass_fail', $child->pass_fail) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Dream Profession</label>
                        <input type="text" name="dream" value="{{ old('dream', $child->dream) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Dream Description</label>
                        <textarea name="dream_description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">{{ old('dream_description', $child->dream_description) }}</textarea>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Favorite Activity</label>
                        <input type="text" name="favorite_activity" value="{{ old('favorite_activity', $child->favorite_activity) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                </div>
            </div>

            <!-- Health & Disability -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-notes-medical text-rose-500 mr-2"></i>Health & Disability</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Health Status</label>
                        <input type="text" name="health" value="{{ old('health', $child->health) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Disability Type</label>
                        <input type="text" name="disability_type" value="{{ old('disability_type', $child->disability_type) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Health Description</label>
                        <textarea name="health_description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">{{ old('health_description', $child->health_description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Disability Description</label>
                        <textarea name="disability_description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition outline-none">{{ old('disability_description', $child->disability_description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 pb-10">
                <a href="{{ route('child-officer.children.index') }}" class="px-6 py-2.5 rounded-lg text-slate-600 font-medium hover:bg-slate-200 transition mr-4">Cancel</a>
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-2.5 rounded-lg font-bold shadow-md transition transform hover:-translate-y-0.5">
                    Update Child Details
                </button>
            </div>
        </form>
    </div>
</body>
</html>
