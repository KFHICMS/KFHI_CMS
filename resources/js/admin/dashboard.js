const dashboardGreeting = document.getElementById('dashboardGreeting');

function updateDashboardGreeting() {
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening');

    dashboardGreeting.textContent = greeting;
}

if (dashboardGreeting) {
    updateDashboardGreeting();
    window.setInterval(updateDashboardGreeting, 60 * 1000);
}

const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
const desktopViewport = window.matchMedia('(min-width: 768px)');

function setSidebarOpen(isOpen) {
    sidebar.classList.toggle('-translate-x-full', !isOpen);
    sidebar.classList.toggle('md:w-0', !isOpen && desktopViewport.matches);
    sidebar.classList.toggle('md:overflow-hidden', !isOpen && desktopViewport.matches);
    sidebarBackdrop.classList.toggle('hidden', !isOpen || desktopViewport.matches);
    sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    sidebarToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    sidebarToggleIcon.classList.toggle('fa-bars', !isOpen);
    sidebarToggleIcon.classList.toggle('fa-xmark', isOpen);
}

setSidebarOpen(desktopViewport.matches);

sidebarToggle.addEventListener('click', () => {
    setSidebarOpen(sidebarToggle.getAttribute('aria-expanded') !== 'true');
});
sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));
desktopViewport.addEventListener('change', (event) => setSidebarOpen(event.matches));

const replyModal = document.getElementById('replyModal');

function closeReplyModal() {
    if (!replyModal) return;
    replyModal.classList.add('hidden');
    replyModal.classList.remove('flex');
}

if (replyModal) {
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
}

const chartDataElement = document.getElementById('dashboard-chart-data');

if (chartDataElement) {
    const chartData = JSON.parse(chartDataElement.textContent);

    new window.Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: chartData.pie_labels,
        datasets: [{
            data: chartData.pie_values,
            backgroundColor: ['#047857', '#34d399', '#fbbf24', '#38bdf8'],
            borderColor: '#ffffff',
            borderWidth: 4,
            hoverOffset: 6
        }]
    },
    options: {
        devicePixelRatio: Math.max(window.devicePixelRatio || 1, 2),
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#475569',
                    font: { family: 'Inter, ui-sans-serif, system-ui, sans-serif', size: 13, weight: '500' },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    pointStyleWidth: 10,
                    padding: 18
                }
            }
        }
    }
    });

    new window.Chart(document.getElementById('barGraph'), {
    type: 'bar',
    data: {
        labels: chartData.bar_labels,
        datasets: [{
            label: 'Activities completed',
            data: chartData.bar_values,
            backgroundColor: '#059669',
            hoverBackgroundColor: '#047857',
            borderRadius: 7,
            maxBarThickness: 38
        }]
    },
    options: {
        devicePixelRatio: Math.max(window.devicePixelRatio || 1, 2),
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
            x: { border: { display: false }, grid: { display: false } }
        }
    }
    });
}
