const dashboardGreeting = document.getElementById('dashboardGreeting');

function updateDashboardGreeting() {
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening');

    if(dashboardGreeting) {
        dashboardGreeting.textContent = greeting;
    }
}

updateDashboardGreeting();
window.setInterval(updateDashboardGreeting, 60 * 1000);

const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
const desktopViewport = window.matchMedia('(min-width: 768px)');

function setSidebarOpen(isOpen) {
    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        sidebar.classList.toggle('md:w-0', !isOpen && desktopViewport.matches);
        sidebar.classList.toggle('md:overflow-hidden', !isOpen && desktopViewport.matches);
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.classList.toggle('hidden', !isOpen || desktopViewport.matches);
    }

    if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        sidebarToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    }

    if (sidebarToggleIcon) {
        sidebarToggleIcon.classList.toggle('fa-bars', !isOpen);
        sidebarToggleIcon.classList.toggle('fa-xmark', isOpen);
    }
}

setSidebarOpen(desktopViewport.matches);

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        setSidebarOpen(sidebarToggle.getAttribute('aria-expanded') !== 'true');
    });
}

if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));
}

desktopViewport.addEventListener('change', (event) => setSidebarOpen(event.matches));
