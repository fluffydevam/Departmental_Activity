<!DOCTYPE html>
<html>
<head>
    <title>Plant Nursery</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav>
        <a href="{{ route('plants.index') }}">Plants</a> |
        <a href="{{ route('dashboard') }}">Dashboard</a> |
        <a href="{{ route('reports.index') }}">Reports</a> |
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </nav>

    <hr>

    @if (session('success'))
        <p style="color: green">{{ session('success') }}</p>
    @endif

    @yield('content')
</body>
</html>
