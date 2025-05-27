<!DOCTYPE html>
<html>
<head>
    <title>SIPB Documentation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tab-link.active {
            background: #2563eb !important;
            color: #fff !important;
        }
        .prose pre {
            background: #f8f9fa;
            padding: 1em;
            border-radius: 6px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5 p-4 bg-white rounded shadow">
        @yield('content')
    </div>
</body>
</html>