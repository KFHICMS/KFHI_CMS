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
            <div class="mt-4">
                <label for="qrCameraSelect" class="mb-1 block text-sm font-medium text-slate-700">Camera</label>
                <select id="qrCameraSelect" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" disabled>
                    <option value="">Camera will be detected when scanning starts</option>
                </select>
            </div>
            <p id="qrCameraStatus" class="mt-2 text-sm text-slate-500" role="status" aria-live="polite"></p>
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
            <p id="scanLookupMessage" class="hidden rounded-xl border px-4 py-3 text-sm" role="status" aria-live="polite"></p>

            <div id="scanResultState" class="hidden flex-1 flex flex-col">
                <div class="flex items-start gap-4 mb-6">
                    <div class="h-16 w-16 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-slate-900" id="scannedChildName"></h4>
                        <p class="text-sm text-slate-500" id="scannedChildKoreanName"></p>
                        <p class="text-sm text-slate-500 font-mono mt-1" id="scannedChildCode"></p>
                        <span class="inline-block mt-2 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">Verified child record</span>
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
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 col-span-2">
                        <span class="block text-xs text-slate-500 mb-1">School / Grade</span>
                        <span class="font-semibold text-slate-800" id="scannedChildSchool">-</span>
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
    let scannerStartInProgress = false;
    let scanLookupInProgress = false;

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
    async function startQRScanner() {
        const startButton = document.getElementById('startScanBtn');
        const stopButton = document.getElementById('stopScanBtn');
        const cameraSelect = document.getElementById('qrCameraSelect');
        const cameraStatus = document.getElementById('qrCameraStatus');

        if (scannerStartInProgress || html5QrcodeScanner?.isScanning) {
            return;
        }

        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            cameraStatus.textContent = 'Camera access requires HTTPS or localhost in a browser that supports camera access.';
            cameraStatus.className = 'mt-2 text-sm text-rose-600';
            return;
        }

        if (!window.Html5Qrcode) {
            cameraStatus.textContent = 'The QR scanner could not be loaded. Refresh the page and try again.';
            cameraStatus.className = 'mt-2 text-sm text-rose-600';
            return;
        }

        scannerStartInProgress = true;
        startButton.disabled = true;
        cameraStatus.textContent = 'Requesting camera permission and finding available cameras...';
        cameraStatus.className = 'mt-2 text-sm text-slate-500';

        let permissionStream;

        try {
            permissionStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'user' } },
            });

            permissionStream.getTracks().forEach((track) => track.stop());
            permissionStream = null;

            const cameras = await Html5Qrcode.getCameras();
            if (cameras.length === 0) {
                throw new Error('No cameras were found on this device.');
            }

            const selectedCameraId = cameraSelect.value;
            const selectedCamera = cameras.find((camera) => camera.id === selectedCameraId);
            const cameraScore = (camera) => {
                const label = camera.label.toLowerCase();
                let score = 0;

                if (/virtual|obs|manycam|snap camera|xsplit/.test(label)) {
                    score += 100;
                }
                if (/integrated|built.?in|internal|laptop|webcam|facetime/.test(label)) {
                    score += 10;
                }
                if (/phone|iphone|android|rear|back|environment/.test(label)) {
                    score -= 20;
                }

                return score;
            };
            const recommendedCamera = [...cameras].sort((first, second) => cameraScore(second) - cameraScore(first))[0];
            const camera = selectedCamera || recommendedCamera;

            cameraSelect.replaceChildren(...cameras.map((device, index) => {
                const option = document.createElement('option');
                option.value = device.id;
                option.textContent = device.label || `Camera ${index + 1}`;
                return option;
            }));
            cameraSelect.disabled = cameras.length < 2;
            cameraSelect.value = camera.id;
            const isVirtualCamera = /virtual|obs|manycam|snap camera|xsplit/i.test(camera.label);
            cameraStatus.textContent = isVirtualCamera
                ? `Using virtual camera: ${camera.label}. Select another camera above if needed.`
                : `No virtual camera was detected. Using ${camera.label || 'the selected camera'}; select a virtual camera above if available.`;

            html5QrcodeScanner ??= new Html5Qrcode('qr-reader');
            await html5QrcodeScanner.start(
                camera.id,
                {
                    fps: 15,
                    qrbox: (viewfinderWidth, viewfinderHeight) => {
                        const size = Math.min(
                            Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.9),
                            500
                        );

                        return { width: size, height: size };
                    },
                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                },
                onScanSuccess,
                onScanFailure
            );

            startButton.classList.add('hidden');
            stopButton.classList.remove('hidden');
        } catch (error) {
            permissionStream?.getTracks().forEach((track) => track.stop());
            console.error('Could not start QR scanner:', error);
            cameraStatus.textContent = error.message || 'Could not start the camera. Check browser permissions and try again.';
            cameraStatus.className = 'mt-2 text-sm text-rose-600';
        } finally {
            scannerStartInProgress = false;
            startButton.disabled = false;
        }
    }

    async function stopQRScanner() {
        if (!html5QrcodeScanner?.isScanning) {
            document.getElementById('startScanBtn').classList.remove('hidden');
            document.getElementById('stopScanBtn').classList.add('hidden');
            return;
        }

        try {
            await html5QrcodeScanner.stop();
            document.getElementById('startScanBtn').classList.remove('hidden');
            document.getElementById('stopScanBtn').classList.add('hidden');
        } catch (error) {
            console.error('Could not stop QR scanner:', error);
            document.getElementById('qrCameraStatus').textContent = 'Could not stop the camera. Please try again.';
            document.getElementById('qrCameraStatus').className = 'mt-2 text-sm text-rose-600';
        }
    }

    document.getElementById('qrCameraSelect').addEventListener('change', async () => {
        if (!html5QrcodeScanner?.isScanning) {
            return;
        }

        await stopQRScanner();
        await startQRScanner();
    });

    async function onScanSuccess(decodedText) {
        if (scanLookupInProgress) {
            return;
        }

        scanLookupInProgress = true;
        await stopQRScanner();
        const foundChild = await fetchChildByCode(decodedText.trim());

        if (!foundChild) {
            scanLookupInProgress = false;
        }
    }

    function onScanFailure(error) {
        // continuously called when no QR is found in frame. Ignore.
    }

    async function fetchChildByCode(childCode) {
        try {
            const resp = await fetch(`/child-officer/children?child_code=${encodeURIComponent(childCode)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) {
                throw new Error(`Child lookup failed with status ${resp.status}.`);
            }

            const children = await resp.json();
            if (!Array.isArray(children)) {
                throw new Error('The child lookup returned an unexpected response.');
            }

            const child = children.find((candidate) => candidate.child_code === childCode);
            if (!child) {
                showScanLookupMessage(`No child record matches QR code "${childCode}". Scan a QR code containing the child's code.`);
                return false;
            }

            displayScanResult(child);
            return true;
        } catch (err) {
            console.error('Could not look up scanned child:', err);
            showScanLookupMessage(err.message || 'Could not look up the scanned child. Please try again.');
            return false;
        }
    }

    function showScanLookupMessage(message) {
        const lookupMessage = document.getElementById('scanLookupMessage');
        lookupMessage.textContent = message;
        lookupMessage.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700';
    }

    function displayScanResult(child) {
        document.getElementById('scanLookupMessage').classList.add('hidden');
        document.getElementById('scanIdleState').classList.add('hidden');
        document.getElementById('scanResultState').classList.remove('hidden');

        document.getElementById('scannedChildName').textContent = child.name_english || child.full_name || 'Unknown';
        document.getElementById('scannedChildKoreanName').textContent = child.name_korean || '';
        document.getElementById('scannedChildCode').textContent = child.child_code;

        const ageStr = child.age !== null && child.age !== undefined ? `${child.age} yrs` : '-';
        const genderStr = child.gender || '-';
        document.getElementById('scannedChildDemographics').textContent = `${ageStr} / ${genderStr}`;
        document.getElementById('scannedChildLocation').textContent = `${child.area || '-'} / ${child.office_name || '-'}`;
        document.getElementById('scannedChildSchool').textContent = `${child.school || '-'} / ${child.grade || '-'}`;
        
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
        scanLookupInProgress = false;
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
