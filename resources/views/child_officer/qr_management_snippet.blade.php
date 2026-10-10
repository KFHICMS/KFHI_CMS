@php
/**
 * Blade snippet for QR Management: Generate and Scan QR codes.
 * Included in childdashboard.blade.php via @include('child_officer.qr_management_snippet')
 */
@endphp

<!-- HTML5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode"></script>

<div id="qrManagementSection" class="hidden mx-auto max-w-7xl space-y-5 px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900" id="qrSectionTitle">QR Management</h2>
            <p class="mt-1 text-sm text-slate-500">Scan child QR codes to mark attendance or generate codes for physical IDs.</p>
        </div>
        <div class="flex gap-2 bg-slate-100 p-1 rounded-xl border border-slate-200 items-center">
            <button type="button" onclick="showDashboard()" class="px-3 py-1.5 text-sm font-semibold rounded-lg text-slate-600 hover:text-slate-800 transition mr-2 border-r border-slate-300">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <button type="button" id="tabScanQR" onclick="switchQRTab('scan')" class="px-4 py-2 text-sm font-semibold rounded-lg bg-white shadow-sm text-emerald-700 transition">Scan QR</button>
            <button type="button" id="tabGenerateQR" onclick="switchQRTab('generate')" class="px-4 py-2 text-sm font-semibold rounded-lg text-slate-500 hover:text-slate-700 transition">Generate QR</button>
        </div>
    </div>

    <!-- SCAN QR TAB -->
    <div id="scanQRTab" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Scanner View -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4"><i class="fa-solid fa-camera mr-2 text-emerald-600"></i> Scanner</h3>
            <div id="qr-reader" class="w-full overflow-hidden rounded-xl border-2 border-dashed border-emerald-200 bg-slate-50"></div>
            <div class="mt-4 text-center">
                <button type="button" id="startScanBtn" onclick="startQRScanner()" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i class="fa-solid fa-play"></i> Start Camera
                </button>
                <button type="button" id="stopScanBtn" onclick="stopQRScanner()" class="hidden inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    <i class="fa-solid fa-stop"></i> Stop Camera
                </button>
            </div>
        </div>

        <!-- Scan Result View -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm flex flex-col">
            <h3 class="font-bold text-slate-900 mb-4"><i class="fa-solid fa-id-card mr-2 text-sky-600"></i> Scanned Identity</h3>

            <div id="scanIdleState" class="flex-1 flex flex-col items-center justify-center text-slate-400 py-10">
                <i class="fa-solid fa-qrcode text-5xl mb-3 opacity-50"></i>
                <p class="text-sm">Awaiting QR scan...</p>
            </div>

            <div id="scanResultState" class="hidden flex-1 flex flex-col">
                <div class="flex items-start gap-4 mb-6">
                    <div class="h-16 w-16 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-slate-900" id="scannedChildName">John Doe</h4>
                        <p class="text-sm text-slate-500 font-mono mt-1" id="scannedChildCode">CH-001</p>
                        <span class="inline-block mt-2 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200" id="scannedChildStatus">Verified</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm mb-6">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs text-slate-500 mb-1">Age / Gender</span>
                        <span class="font-semibold text-slate-800" id="scannedChildDemographics">-</span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="block text-xs text-slate-500 mb-1">Area / Office</span>
                        <span class="font-semibold text-slate-800" id="scannedChildLocation">-</span>
                    </div>
                </div>

                <div class="mt-auto space-y-3">
                    <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Quick Actions</h5>
                    <button type="button" onclick="recordAttendance()" class="w-full flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                        <span><i class="fa-solid fa-check-circle mr-2"></i> Mark Attendance</span>
                        <i class="fa-solid fa-chevron-right opacity-50"></i>
                    </button>
                    <button type="button" onclick="recordBenefit()" class="w-full flex items-center justify-between rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                        <span><i class="fa-solid fa-gift mr-2"></i> Record Benefit</span>
                        <i class="fa-solid fa-chevron-right opacity-50"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- GENERATE QR TAB (Reuses list approach but focuses on QR) -->
    <div id="generateQRTab" class="hidden">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm mb-5">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="qrSearchInput" placeholder="Search child to generate QR..."
                       oninput="loadQRChildren()"
                       class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-print"></i> Print View
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="qrGridContainer">
            <!-- Populated by JS -->
            <div class="col-span-full py-12 text-center text-slate-400">Loading children...</div>
        </div>
    </div>
</div>

<script>
    let html5QrcodeScanner = null;

    function showQRManagement(defaultTab = 'scan') {
        document.getElementById('dashboardOverviewSection').classList.add('hidden');
        document.getElementById('manageChildrenSection').classList.add('hidden');
        document.getElementById('qrManagementSection').classList.remove('hidden');

        switchQRTab(defaultTab);
    }

    function switchQRTab(tab) {
        const scanTab = document.getElementById('scanQRTab');
        const genTab = document.getElementById('generateQRTab');
        const btnScan = document.getElementById('tabScanQR');
        const btnGen = document.getElementById('tabGenerateQR');

        if (tab === 'scan') {
            scanTab.classList.remove('hidden');
            genTab.classList.add('hidden');
            btnScan.className = 'px-4 py-2 text-sm font-semibold rounded-lg bg-white shadow-sm text-emerald-700 transition';
            btnGen.className = 'px-4 py-2 text-sm font-semibold rounded-lg text-slate-500 hover:text-slate-700 transition';
            // Stop scanner if switching away, but here we are switching to it.
        } else {
            scanTab.classList.add('hidden');
            genTab.classList.remove('hidden');
            btnScan.className = 'px-4 py-2 text-sm font-semibold rounded-lg text-slate-500 hover:text-slate-700 transition';
            btnGen.className = 'px-4 py-2 text-sm font-semibold rounded-lg bg-white shadow-sm text-emerald-700 transition';
            stopQRScanner(); // stop camera if active
            loadQRChildren();
        }
    }

    /* ────────── Scanner Logic ────────── */
    function startQRScanner() {
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
        }

        document.getElementById('startScanBtn').classList.add('hidden');
        document.getElementById('stopScanBtn').classList.remove('hidden');

        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            alert("Error starting scanner. Ensure camera permissions are granted.");
            console.error(err);
            stopQRScanner();
        });
    }

    function stopQRScanner() {
        if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
            html5QrcodeScanner.stop().then(() => {
                document.getElementById('startScanBtn').classList.remove('hidden');
                document.getElementById('stopScanBtn').classList.add('hidden');
            }).catch(err => console.error(err));
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        // We expect the QR code to contain the child_code
        // Pause scanning to process
        stopQRScanner();

        // Fetch child data from backend
        fetchChildByCode(decodedText);
    }

    function onScanFailure(error) {
        // continuously called when no QR is found in frame. Ignore.
    }

    async function fetchChildByCode(childCode) {
        try {
            const resp = await fetch(`/child-officer/children?search=${encodeURIComponent(childCode)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const children = await resp.json();

            // Find exact match just in case search returned multiples (like similar aliases)
            const child = children.find(c => c.child_code === childCode) || children[0];

            if (child) {
                displayScanResult(child);
            } else {
                alert('QR Code valid, but no matching child found in the system for code: ' + childCode);
            }
        } catch (err) {
            console.error(err);
            alert("Failed to fetch child data.");
        }
    }

    function displayScanResult(child) {
        document.getElementById('scanIdleState').classList.add('hidden');
        document.getElementById('scanResultState').classList.remove('hidden');

        document.getElementById('scannedChildName').textContent = child.name_english || 'Unknown';
        document.getElementById('scannedChildCode').textContent = child.child_code;

        const ageStr = child.age ? `${child.age} yrs` : '-';
        const genderStr = child.gender ? child.gender.substring(0, 1) : '-';
        document.getElementById('scannedChildDemographics').textContent = `${ageStr} / ${genderStr}`;
        document.getElementById('scannedChildLocation').textContent = `${child.area || '-'} / ${child.office_name || '-'}`;

        // Store child ID globally for quick actions
        window.currentScannedChildId = child.id;
    }

    /* Quick Action Dummies */
    function recordAttendance() {
        if (!window.currentScannedChildId) return;
        alert(`Attendance marked successfully for child ID: ${window.currentScannedChildId}`);
        // Reset view for next scan
        document.getElementById('scanResultState').classList.add('hidden');
        document.getElementById('scanIdleState').classList.remove('hidden');
        startQRScanner();
    }

    function recordBenefit() {
        if (!window.currentScannedChildId) return;
        alert(`Benefit recording screen would open for child ID: ${window.currentScannedChildId}`);
    }

    /* ────────── Generate QR Grid Logic ────────── */
    async function loadQRChildren() {
        const search = document.getElementById('qrSearchInput').value;
        const container = document.getElementById('qrGridContainer');

        try {
            const resp = await fetch(`/child-officer/children?search=${encodeURIComponent(search)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const children = await resp.json();

            if (!children || children.length === 0) {
                container.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400">No children found matching the search.</div>';
                return;
            }

            container.innerHTML = children.map(c => `
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-center flex flex-col items-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(c.child_code)}"
                         alt="QR" class="w-32 h-32 mb-3 border border-slate-100 p-1 rounded-lg">
                    <h4 class="font-bold text-slate-900 truncate w-full">${escapeHtml(c.name_english || '-')}</h4>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">${escapeHtml(c.child_code)}</p>
                    <button type="button" data-child-code="${escapeHtml(c.child_code)}" data-child-name="${escapeHtml(c.name_english)}" class="mt-3 w-full rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 print:hidden">
                        Expand & Print
                    </button>
                </div>
            `).join('');

            container.querySelectorAll('button[data-child-code]').forEach((button) => {
                button.addEventListener('click', () => openQRModal(button.dataset.childCode, button.dataset.childName));
            });

        } catch (err) {
            console.error(err);
            container.innerHTML = '<div class="col-span-full py-12 text-center text-rose-400">Failed to load children.</div>';
        }
    }
</script>
