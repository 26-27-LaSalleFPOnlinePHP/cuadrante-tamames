{{--
    Página de error 404 propia, dentro del layout común.

    La usan dos caminos: el manejador de bootstrap/app.php cuando se pide un
    empleado que no existe (pasa $message), y Laravel para cualquier otra URL
    que no exista, como /empleados/abc (sin $message).
--}}
@extends('layout')

@section('title', 'No encontrado')

@section('content')
    <h1>No encontrado</h1>

    <p>{{ $message ?? 'La página que buscas no existe.' }}</p>

    <p><a href="{{ route('employees.index') }}">← Volver a la plantilla</a></p>
@endsection
