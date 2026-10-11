<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base" content="{{ url('/api') }}">
    <title>KFHI Child Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="{{ asset('css/kfhi.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app"></div>
    <div id="toasts" class="toast-container position-fixed top-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/kfhi/api.js') }}"></script>
    <script src="{{ asset('js/kfhi/ui.js') }}"></script>
    {{-- Page modules: each registers itself in KFHI.pages. Add new modules here. --}}
    <script src="{{ asset('js/kfhi/pages/dashboard.js') }}"></script>
    <script src="{{ asset('js/kfhi/pages/children.js') }}"></script>
    <script src="{{ asset('js/kfhi/app.js') }}"></script>
</body>
</html>