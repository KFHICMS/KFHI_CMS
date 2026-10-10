@php
/**
 * Blade snippet for managing children: register, search, list, edit, delete, QR.
 * Included in childdashboard.blade.php via @include('child_officer.manage_children_snippet')
 */
@endphp

<!-- ============================================================ -->
<!-- Register / Edit Child Modal                                   -->
<!-- ============================================================ -->
<div id="childModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-sm">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-bold text-slate-900" id="childModalTitle">Register Child</h2>
                <button type="button" onclick="closeChildModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="childForm" class="p-6">
                <input type="hidden" id="formMethod" value="POST">
                <input type="hidden" id="childId" value="">

                <!-- Personal Info -->
                <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-emerald-700">Personal Information</h4>
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div>
                        <label for="childCode" class="block text-xs font-medium text-slate-700">Child Code <span class="text-rose-500">*</span></label>
                        <input type="text" id="childCode" name="child_code" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="nameEnglish" class="block text-xs font-medium text-slate-700">Child Name (English) <span class="text-rose-500">*</span></label>
                        <input type="text" id="nameEnglish" name="name_english" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="nameKorean" class="block text-xs font-medium text-slate-700">Child Name (Korean)</label>
                        <input type="text" id="nameKorean" name="name_korean" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="alias" class="block text-xs font-medium text-slate-700">Alias</label>
                        <input type="text" id="alias" name="alias" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="dateOfBirth" class="block text-xs font-medium text-slate-700">Birthday</label>
                        <input type="date" id="dateOfBirth" name="date_of_birth" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="gender" class="block text-xs font-medium text-slate-700">Gender</label>
                        <select id="gender" name="gender" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="religion" class="block text-xs font-medium text-slate-700">Religion</label>
                        <input type="text" id="religion" name="religion" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="guardianType" class="block text-xs font-medium text-slate-700">Guardian Type</label>
                        <input type="text" id="guardianType" name="guardian_type" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="caregiver" class="block text-xs font-medium text-slate-700">Caregiver</label>
                        <input type="text" id="caregiver" name="caregiver" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Location & Status -->
                <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-emerald-700">Location & Status</h4>
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div>
                        <label for="area" class="block text-xs font-medium text-slate-700">Area</label>
                        <input type="text" id="area" name="area" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="officeCode" class="block text-xs font-medium text-slate-700">Office Code</label>
                        <input type="text" id="officeCode" name="office_code" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="officeName" class="block text-xs font-medium text-slate-700">Office Name</label>
                        <input type="text" id="officeName" name="office_name" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="serviceState" class="block text-xs font-medium text-slate-700">Service State</label>
                        <input type="text" id="serviceState" name="service_state" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="sponsorState" class="block text-xs font-medium text-slate-700">Sponsor State</label>
                        <input type="text" id="sponsorState" name="sponsor_state" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Education & Interests -->
                <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-emerald-700">Education & Interests</h4>
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <div>
                        <label for="curriculum" class="block text-xs font-medium text-slate-700">Curriculum</label>
                        <input type="text" id="curriculum" name="curriculum" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="grade" class="block text-xs font-medium text-slate-700">Grade</label>
                        <input type="text" id="grade" name="grade" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="favoriteSubject" class="block text-xs font-medium text-slate-700">Favorite Subject</label>
                        <input type="text" id="favoriteSubject" name="favorite_subject" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="passFail" class="block text-xs font-medium text-slate-700">Pass / Fail</label>
                        <select id="passFail" name="pass_fail" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Select</option>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div>
                        <label for="favoriteActivity" class="block text-xs font-medium text-slate-700">Favorite Activity</label>
                        <input type="text" id="favoriteActivity" name="favorite_activity" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="dream" class="block text-xs font-medium text-slate-700">Dream</label>
                        <input type="text" id="dream" name="dream" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="sm:col-span-2 md:col-span-3">
                        <label for="dreamDescription" class="block text-xs font-medium text-slate-700">Dream Description</label>
                        <textarea id="dreamDescription" name="dream_description" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>
                </div>

                <!-- Health & Disability -->
                <h4 class="mb-4 text-sm font-semibold uppercase tracking-wider text-emerald-700">Health & Disability</h4>
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="health" class="block text-xs font-medium text-slate-700">Health</label>
                        <input type="text" id="health" name="health" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="disabilityType" class="block text-xs font-medium text-slate-700">Disability Type</label>
                        <input type="text" id="disabilityType" name="disability_type" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="healthDescription" class="block text-xs font-medium text-slate-700">Health Description</label>
                        <textarea id="healthDescription" name="health_description" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>
                    <div>
                        <label for="disabilityDescription" class="block text-xs font-medium text-slate-700">Disability Description</label>
                        <textarea id="disabilityDescription" name="disability_description" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                    <button type="button" onclick="closeChildModal()" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">Save Child Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QR Code Modal                                                 -->
<!-- ============================================================ -->
<div id="qrModal" class="fixed inset-0 z-[60] hidden overflow-y-auto bg-black/50 backdrop-blur-sm">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-1" id="qrChildName">Child QR Code</h3>
            <p class="text-sm text-slate-500 mb-6" id="qrChildCode"></p>
            <div class="mx-auto flex h-52 w-52 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 mb-6">
                <img id="qrImage" src="" alt="QR Code" class="h-48 w-48 object-contain">
            </div>
            <div class="flex justify-center gap-2">
                <button type="button" onclick="closeQRModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Close</button>
                <button type="button" onclick="printQR()" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"><i class="fa-solid fa-print mr-1"></i>Print</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Manage Children Section (hidden by default, shown by sidebar) -->
<!-- ============================================================ -->
<div id="manageChildrenSection" class="hidden mx-auto max-w-7xl space-y-5 px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Child Directory</h2>
            <p class="mt-1 text-sm text-slate-500">Manage all registered children, view details, and generate QR codes.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="showDashboard()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </button>
            <button type="button" onclick="openChildModal()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                <i class="fa-solid fa-plus"></i> Register Child
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" id="searchInput" placeholder="Search by name, code, area..."
                   oninput="loadChildren()"
                   class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        </div>
        <select id="filterGender" onchange="loadChildren()" class="rounded-xl border border-slate-200 py-2.5 pl-3 pr-8 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="">All Genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>

    <!-- Children Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto dashboard-scrollbar">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Child Code</th>
                        <th class="px-6 py-4 font-semibold">Name (EN / KR)</th>
                        <th class="px-6 py-4 font-semibold">Gender</th>
                        <th class="px-6 py-4 font-semibold">Area</th>
                        <th class="px-6 py-4 font-semibold">Office</th>
                        <th class="px-6 py-4 font-semibold">Grade</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="childrenTableBody" class="divide-y divide-slate-200 bg-white">
                    <tr id="childrenLoadingState"><td colspan="7" class="px-6 py-12 text-center text-slate-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Empty State -->
        <div id="emptyState" class="hidden py-12 text-center">
            <i class="fa-solid fa-children text-4xl text-slate-300 mb-3"></i>
            <p class="text-sm text-slate-500">No children found. Click "Register Child" to add one.</p>
        </div>
        <div id="childrenErrorState" class="hidden py-12 text-center text-sm text-rose-600" role="alert"></div>
    </div>
</div>

<script>
    const BASE_URL = '/child-officer/children';
    const CSRF_TOKEN = '{{ csrf_token() }}';

    function escapeHtml(value) {
        const escapedCharacters = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return String(value ?? '').replace(/[&<>"']/g, (character) => escapedCharacters[character]);
    }

    /* ────────── Section Toggling ────────── */
    function showManageChildren() {
        document.getElementById('dashboardOverviewSection').classList.add('hidden');
        const qrSec = document.getElementById('qrManagementSection');
        if (qrSec) qrSec.classList.add('hidden');
        document.getElementById('manageChildrenSection').classList.remove('hidden');
        loadChildren();
    }
    function showDashboard() {
        document.getElementById('manageChildrenSection').classList.add('hidden');
        const qrSec = document.getElementById('qrManagementSection');
        if (qrSec) qrSec.classList.add('hidden');
        document.getElementById('dashboardOverviewSection').classList.remove('hidden');
    }

    /* ────────── Child Modal ────────── */
    function openChildModal() {
        document.getElementById('childModalTitle').textContent = 'Register New Child';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('childId').value = '';
        document.getElementById('childForm').reset();
        document.getElementById('childModal').classList.remove('hidden');
    }
    function closeChildModal() {
        document.getElementById('childModal').classList.add('hidden');
    }

    /* ────────── QR Modal ────────── */
    function openQRModal(childCode, childName) {
        document.getElementById('qrChildName').textContent = childName;
        document.getElementById('qrChildCode').textContent = childCode;
        document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(childCode)}`;
        document.getElementById('qrModal').classList.remove('hidden');
    }
    function closeQRModal() {
        document.getElementById('qrModal').classList.add('hidden');
    }
    function printQR() {
        const img = document.getElementById('qrImage').src;
        const name = document.getElementById('qrChildName').textContent;
        const code = document.getElementById('qrChildCode').textContent;
        const w = window.open('', '_blank');

        if (!w) {
            alert('Please allow pop-ups to print the QR code.');
            return;
        }

        w.document.title = `QR - ${code}`;
        w.document.body.style.cssText = 'text-align:center;font-family:sans-serif;padding:40px';

        const heading = w.document.createElement('h2');
        heading.textContent = name;

        const codeLabel = w.document.createElement('p');
        codeLabel.textContent = code;

        const image = w.document.createElement('img');
        image.src = img;
        image.alt = `QR code for ${code}`;
        image.style.width = '250px';
        image.style.height = '250px';

        w.document.body.replaceChildren(heading, codeLabel, image);
        window.setTimeout(() => w.print(), 500);
    }

    /* ────────── Load Children (AJAX) ────────── */
    async function loadChildren() {
        const search = document.getElementById('searchInput').value;
        const gender = document.getElementById('filterGender').value;
        let url = `${BASE_URL}?search=${encodeURIComponent(search)}`;
        if (gender) url += `&gender=${encodeURIComponent(gender)}`;

        const tbody = document.getElementById('childrenTableBody');
        const emptyState = document.getElementById('emptyState');
        const errorState = document.getElementById('childrenErrorState');

        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">Loading...</td></tr>';
        emptyState.classList.add('hidden');
        errorState.classList.add('hidden');

        try {
            const resp = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!resp.ok) {
                throw new Error(`Request failed with status ${resp.status}`);
            }

            const data = await resp.json();

            if (!Array.isArray(data)) {
                throw new Error('The server returned an unexpected child list response.');
            }

            renderChildren(data);
        } catch (err) {
            console.error('Failed to load children:', err);
            tbody.innerHTML = '';
            emptyState.classList.add('hidden');
            errorState.textContent = `Could not load children. ${err.message}`;
            errorState.classList.remove('hidden');
        }
    }

    function renderChildren(children) {
        const tbody = document.getElementById('childrenTableBody');
        const emptyState = document.getElementById('emptyState');
        const errorState = document.getElementById('childrenErrorState');

        if (!children || children.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
            errorState.classList.add('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        errorState.classList.add('hidden');
        tbody.innerHTML = children.map(c => `
            <tr class="hover:bg-slate-50/50 transition">
                <td class="whitespace-nowrap px-6 py-4 font-medium text-emerald-700">${escapeHtml(c.child_code)}</td>
                <td class="px-6 py-4">
                    <div class="font-semibold text-slate-900">${escapeHtml(c.name_english)}</div>
                    <div class="text-xs text-slate-500">${escapeHtml(c.name_korean)}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-600">${escapeHtml(c.gender ?? '-')}</td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-600">${escapeHtml(c.area ?? '-')}</td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-600">${escapeHtml(c.office_name ?? '-')}</td>
                <td class="whitespace-nowrap px-6 py-4 text-slate-600">${escapeHtml(c.grade ?? '-')}</td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" title="View QR" data-action="view-qr" data-child-code="${escapeHtml(c.child_code)}" data-child-name="${escapeHtml(c.name_english)}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-sky-50 hover:text-sky-600"><i class="fa-solid fa-qrcode"></i></button>
                        <button type="button" title="Edit" data-action="edit-child" data-child="${escapeHtml(JSON.stringify(c))}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-amber-50 hover:text-amber-600"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" title="Delete" data-action="delete-child" data-child-id="${escapeHtml(c.id)}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');

        tbody.querySelectorAll('[data-action="view-qr"]').forEach((button) => {
            button.addEventListener('click', () => openQRModal(button.dataset.childCode, button.dataset.childName));
        });
        tbody.querySelectorAll('[data-action="edit-child"]').forEach((button) => {
            button.addEventListener('click', () => editChild(JSON.parse(button.dataset.child)));
        });
        tbody.querySelectorAll('[data-action="delete-child"]').forEach((button) => {
            button.addEventListener('click', () => deleteChild(button.dataset.childId));
        });
    }

    /* ────────── Edit Child ────────── */
    function editChild(child) {
        document.getElementById('childModalTitle').textContent = 'Edit Child';
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('childId').value = child.id;

        // Map all fields
        const fieldMap = {
            'childCode': 'child_code',
            'nameEnglish': 'name_english',
            'nameKorean': 'name_korean',
            'alias': 'alias',
            'dateOfBirth': 'date_of_birth',
            'gender': 'gender',
            'religion': 'religion',
            'area': 'area',
            'officeCode': 'office_code',
            'officeName': 'office_name',
            'serviceState': 'service_state',
            'sponsorState': 'sponsor_state',
            'guardianType': 'guardian_type',
            'caregiver': 'caregiver',
            'curriculum': 'curriculum',
            'grade': 'grade',
            'favoriteSubject': 'favorite_subject',
            'passFail': 'pass_fail',
            'dream': 'dream',
            'dreamDescription': 'dream_description',
            'favoriteActivity': 'favorite_activity',
            'health': 'health',
            'healthDescription': 'health_description',
            'disabilityType': 'disability_type',
            'disabilityDescription': 'disability_description',
        };

        for (const [elemId, key] of Object.entries(fieldMap)) {
            const el = document.getElementById(elemId);
            if (el) {
                let val = child[key] ?? '';
                // Handle date fields — take just the date portion
                if (key === 'date_of_birth' && val) val = val.split('T')[0];
                el.value = val;
            }
        }

        document.getElementById('childModal').classList.remove('hidden');
    }

    /* ────────── Delete Child ────────── */
    async function deleteChild(id) {
        if (!confirm('Are you sure you want to delete this child record?')) return;
        try {
            await fetch(`${BASE_URL}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            loadChildren();
        } catch (err) {
            alert('Failed to delete: ' + err.message);
        }
    }

    /* ────────── Form Submit (Create / Update) ────────── */
    document.getElementById('childForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const method = document.getElementById('formMethod').value;
        const childId = document.getElementById('childId').value;

        let url = BASE_URL;
        let httpMethod = 'POST';

        if (method === 'PUT' && childId) {
            url = `${BASE_URL}/${childId}`;
            httpMethod = 'POST';          // Use POST + _method=PUT for Laravel
            formData.append('_method', 'PUT');
        }

        try {
            const resp = await fetch(url, {
                method: httpMethod,
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (resp.ok) {
                closeChildModal();
                loadChildren();
            } else {
                const errorBody = await resp.json().catch(() => ({}));

                if (errorBody.errors) {
                    const messages = Object.values(errorBody.errors).flat().join('\n');
                    alert(`Validation errors:\n${messages}`);
                } else {
                    alert(`Error: ${errorBody.message || `Request failed with status ${resp.status}`}`);
                }
            }
        } catch (err) {
            alert('Network error: ' + err.message);
        }
    });

</script>
