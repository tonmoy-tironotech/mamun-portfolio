<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="app-base" content="{{ request()->getBaseUrl() }}" />
<meta name="robots" content="noindex, nofollow" />
<title inertia>Admin</title>
@vite(['resources/css/admin.css', 'resources/js/app.js'])
@inertiaHead
</head>
<body>
@inertia
</body>
</html>
