<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cuadrante de turnos')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <nav class="nav">
        <div class="wrap">
            <strong>Cuadrante de turnos</strong>
            <a href="{{ route('roster.index') }}">Cuadrante</a>
            <a href="{{ route('roster.summary') }}">Resumen</a>
            <a href="{{ route('employees.index') }}">Empleados</a>
        </div>
    </nav>

    @if (session('status'))
        <div class="wrap">
            <p class="flash">{{ session('status') }}</p>
        </div>
    @endif

    <main class="wrap">
        @yield('content')
    </main>
</body>
</html>
