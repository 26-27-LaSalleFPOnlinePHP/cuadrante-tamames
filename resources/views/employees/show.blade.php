{{-- F02 · Ficha de un empleado con sus turnos de la semana. --}}
@extends('layout')

@section('title', $employee->name)

@section('content')
    <h1>{{ $employee->name }}</h1>

    <p>
        Rol: <strong>{{ $employee->role }}</strong> ·
        Contrato: <strong>{{ $employee->contractHours }} h</strong> ·
        Asignadas esta semana: <strong>{{ $hours }} h</strong>
    </p>

    @if ($hours > $employee->contractHours)
        <div class="errors">
            Tiene {{ $hours - $employee->contractHours }} h más de las de su contrato.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Día</th>
                <th>Turno</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shifts as $s)
                <tr>
                    <td>{{ $s['day'] }}</td>
                    <td>{{ $s['shift'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Todavía no tiene turnos asignados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p><a href="{{ route('employees.index') }}">← Volver a la plantilla</a></p>
@endsection
