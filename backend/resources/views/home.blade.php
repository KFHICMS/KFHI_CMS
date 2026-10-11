<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KFHI – Empowering Children, Building a Better Future</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #e6f3eb;
            --card: #e9f5ee;
            --green-900: #0b3d26;
            --green-800: #0a5a33;
            --green-700: #0d6b3b;
            --text: #0f3d26;
            --muted: #4f6f5f;
            --shadow-dark: rgba(20, 90, 55, .16);
            --shadow-light: rgba(255, 255, 255, .9);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { text-decoration: none; color: inherit; }
        .wrap { max-width: 1240px; margin: 0 auto; padding: 18px 20px 56px; }

        /* ---------- navbar ---------- */
        .nav {
            background: linear-gradient(135deg, #0a5a33, #064427);
            border-radius: 22px;
            padding: 16px 30px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 14px 30px rgba(6, 68, 39, .35);
            position: sticky; top: 12px; z-index: 10;
        }
        .brand img { height: 38px; display: block; }
        .brand-text { color: #fff; font-weight: 800; letter-spacing: .5px; font-size: 1.3rem; }
        .nav-links { display: flex; align-items: center; gap: 30px; }
        .nav-links a.link { color: #fff; font-weight: 600; font-size: .95rem; display: flex; align-items: center; gap: 8px; opacity: .95; }
        .nav-links a.link:hover { opacity: 1; text-decoration: underline; }
        .btn-login {
            background: #fff; color: var(--green-800); font-weight: 700; font-size: .95rem;
            padding: 12px 22px; border-radius: 14px; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, .18);
        }
        .btn-login:hover { transform: translateY(-1px); }

        /* ---------- neumorphic card ---------- */
        .card {
            background: var(--card);
            border-radius: 28px;
            box-shadow: 10px 10px 24px var(--shadow-dark), -8px -8px 22px var(--shadow-light);
        }

        /* ---------- hero ---------- */
        .hero { display: grid; grid-template-columns: 1.15fr 1fr; gap: 26px; margin-top: 26px; }
        .hero-main { padding: 52px 48px; position: relative; overflow: hidden; }
        .hero-main::after {
            content: ''; position: absolute; top: 20px; right: 30px; width: 130px; height: 130px;
            background: radial-gradient(circle, rgba(120, 200, 120, .35), transparent 70%); filter: blur(10px);
        }
        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; border-radius: 999px;
            font-size: .78rem; font-weight: 700; letter-spacing: .6px; color: var(--green-800);
            background: var(--card); box-shadow: inset 4px 4px 9px var(--shadow-dark), inset -4px -4px 9px var(--shadow-light);
        }
        .hero-main h1 { font-size: clamp(2.1rem, 4.4vw, 3.3rem); line-height: 1.12; font-weight: 800; margin: 26px 0 18px; color: var(--green-900); }
        .hero-main p { color: var(--muted); font-size: 1.05rem; line-height: 1.65; max-width: 460px; margin: 0 0 32px; }
        .btn-details {
            display: inline-flex; align-items: center; gap: 10px;
            background: #fff; color: var(--green-800); font-weight: 700;
            padding: 15px 26px; border-radius: 14px; box-shadow: 0 8px 18px rgba(20, 90, 55, .18);
        }
        .btn-details:hover { transform: translateY(-1px); }

        /* ---------- stats ---------- */
        .stats { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; }
        .stat { padding: 34px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .stat .icon {
            width: 54px; height: 54px; border-radius: 50%; display: grid; place-items: center;
            color: var(--green-700); font-size: 1.15rem; background: var(--card);
            box-shadow: 5px 5px 12px var(--shadow-dark), -4px -4px 10px var(--shadow-light);
        }
        .stat .num { font-size: 2.7rem; font-weight: 800; margin-top: 8px; color: var(--green-900); line-height: 1.1; }
        .stat .label { font-weight: 600; color: var(--muted); margin-top: 4px; }

        /* ---------- about / contact ---------- */
        section.block { margin-top: 44px; padding: 40px 44px; scroll-margin-top: 100px; }
        section.block h2 { margin: 0 0 14px; font-size: 1.7rem; font-weight: 800; color: var(--green-900); }
        section.block p { color: var(--muted); line-height: 1.7; margin: 0 0 10px; max-width: 760px; }
        .contact-list { list-style: none; padding: 0; margin: 14px 0 0; display: grid; gap: 12px; }
        .contact-list li { display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .contact-list i { color: var(--green-700); width: 20px; text-align: center; }
        footer { text-align: center; color: var(--muted); font-size: .85rem; margin-top: 36px; }

        /* ---------- responsive ---------- */
        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .hero-main { padding: 36px 28px; }
        }
        @media (max-width: 640px) {
            .nav { padding: 14px 18px; }
            .nav-links { gap: 14px; }
            .nav-links a.link span { display: none; }   /* icons only on phones */
            .stats { gap: 16px; }
            .stat { padding: 26px 10px; }
            section.block { padding: 30px 24px; }
        }
    </style>
</head>
<body>
<div class="wrap" id="home">

    <nav class="nav">
        <a class="brand" href="#home">
            {{-- Put your logo file at public/images/kfhi-logo.png (falls back to text if missing) --}}
            <img src="{{ asset('images/kfhi-logo.png') }}" alt="KFHI"
                 onerror="this.replaceWith(Object.assign(document.createElement('span'),{textContent:'KFHI',className:'brand-text'}))">
        </a>
        <div class="nav-links">
            <a class="link" href="#home"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a class="link" href="#about"><i class="fa-solid fa-circle-info"></i><span>About</span></a>
            <a class="link" href="#contact"><i class="fa-solid fa-envelope"></i><span>Contact</span></a>
            <a class="btn-login" href="{{ url('/app') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        </div>
    </nav>

    <main class="hero">
        <div class="card hero-main">
            <span class="badge"><i class="fa-solid fa-heart"></i> NGO ORGANIZATION</span>
            <h1>Empowering Children, Building a Better Future</h1>
            <p>Working together to create meaningful opportunities, protect children, and build stronger communities through sustainable support and meaningful action.</p>
            <a class="btn-details" href="#about">View Details <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="stats">
            <div class="card stat">
                <div class="icon"><i class="fa-solid fa-child-reaching"></i></div>
                <div class="num" data-count="{{ $stats['children'] }}">0</div>
                <div class="label">Children</div>
            </div>
            <div class="card stat">
                <div class="icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="num" data-count="{{ $stats['events'] }}">0</div>
                <div class="label">Events</div>
            </div>
            <div class="card stat">
                <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="num" data-count="{{ $stats['areas'] }}">0</div>
                <div class="label">Areas</div>
            </div>
            <div class="card stat">
                <div class="icon"><i class="fa-solid fa-diagram-project"></i></div>
                <div class="num" data-count="{{ $stats['projects'] }}">0</div>
                <div class="label">Projects</div>
            </div>
        </div>
    </main>

    <section class="card block" id="about">
        <h2>About KFHI</h2>
        <p>Korea Friends of Hope International (KFHI) runs community-based child welfare and development programs. Our field teams work with children and their families to support education, health and wellbeing.</p>
        <p>This system helps our staff identify registered children quickly and securely, while keeping every child's personal information protected and accessible only to authorized KFHI personnel.</p>
    </section>

    <section class="card block" id="contact">
        <h2>Contact</h2>
        <p>Get in touch with the KFHI team.</p>
        {{-- EDIT: replace these placeholders with KFHI's real contact details --}}
        <ul class="contact-list">
            <li><i class="fa-solid fa-envelope"></i> info@example.org</li>
            <li><i class="fa-solid fa-phone"></i> +94 00 000 0000</li>
            <li><i class="fa-solid fa-location-dot"></i> Your office address here</li>
        </ul>
    </section>

    <footer>&copy; {{ date('Y') }} Korea Friends of Hope International. All rights reserved.</footer>
</div>

<script>
    // count-up animation for the four stat numbers
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.dataset.count, 10) || 0;
        if (!target) { el.textContent = '0'; return; }
        var n = 0, step = Math.max(1, Math.ceil(target / 40));
        var t = setInterval(function () {
            n = Math.min(target, n + step);
            el.textContent = n;
            if (n >= target) clearInterval(t);
        }, 30);
    });
</script>
</body>
</html>