<!DOCTYPE html>
<html>
<head>
    <title>SIPB API Documentation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5 p-4 bg-white rounded shadow">
        <h1 class="mb-4">SIPB Documentation</h1>
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link{{ request()->is('docs/readme') || request()->is('/') ? ' active' : '' }}" href="{{ url('/docs/readme') }}">README</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ request()->is('docs/api') ? ' active' : '' }}" href="{{ url('/docs/api') }}">API Docs</a>
            </li>
        </ul>
        <div>
            <p>Silakan pilih dokumentasi yang ingin dibaca.</p>
            <ul>
                <li><b>README:</b> Penjelasan umum, setup, dan fitur aplikasi.</li>
                <li><b>API Docs:</b> Dokumentasi endpoint, validasi, dan response API.</li>
            </ul>
        </div>
    </div>
</body>
</html>