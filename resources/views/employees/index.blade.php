{{-- F01 · Listado de la plantilla, con filtro por rol y orden. --}}
@extends('layout')

@section('title', 'Plantilla')

@section('content')
    <h1>Plantilla</h1>

    <div class="filtros">
        <a href="{{ route('employees.index', ['sort' => $sort]) }}">Todos</a>
        @foreach ($roles as $r)
            <a href="{{ route('employees.index', ['role' => $r, 'sort' => $sort]) }}">{{ $r }}</a>
        @endforeach

        <span class="der">
            Ordenar por:
            <a href="{{ route('employees.index', ['role' => $role, 'sort' => 'name']) }}">nombre</a>
            ·
            <a href="{{ route('employees.index', ['role' => $role, 'sort' => 'hours']) }}">horas</a>
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Contrato</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td><a href="{{ route('employees.show', $employee->id) }}">{{ $employee->name }}</a></td>
                    <td>{{ $employee->role }}</td>
                    <td>{{ $employee->contractHours }} h</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No hay empleados con ese rol.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
