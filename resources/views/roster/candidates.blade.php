{{--
    F08 · ¿Quién puede cubrir este turno?

    Arriba, quien puede (con un botón que asigna pasando por el Form Request
    normal); abajo, quien no puede y por qué, con los mismos mensajes que
    daría la validación.
--}}
@extends('layout')

@section('title', '¿Quién puede cubrir este turno?')

@section('content')
    <h1>¿Quién puede cubrir el turno de {{ $shift }} del {{ $day }}?</h1>

    @if ($available === [])
        <div class="errors">Nadie puede cubrir este turno sin incumplir alguna regla.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Rol</th>
                    <th>Horas (ahora / contrato)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($available as $row)
                    <tr>
                        <td>{{ $row['employee']->name }}</td>
                        <td>{{ $row['employee']->role }}</td>
                        <td>{{ $row['hours'] }} / {{ $row['employee']->contractHours }} h</td>
                        <td>
                            <form method="POST" action="{{ route('roster.assign') }}">
                                @csrf
                                <input type="hidden" name="day" value="{{ $day }}">
                                <input type="hidden" name="shift" value="{{ $shift }}">
                                <input type="hidden" name="employee_id" value="{{ $row['employee']->id }}">
                                <button class="primario">Asignar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <form method="POST" action="{{ route('roster.autofill') }}" class="asignar">
            @csrf
            <input type="hidden" name="day" value="{{ $day }}">
            <input type="hidden" name="shift" value="{{ $shift }}">
            <button class="primario">Rellenar con el primero ({{ $available[0]['employee']->name }})</button>
        </form>
    @endif

    @if ($unavailable !== [])
        <h2>No pueden</h2>

        <table>
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unavailable as $row)
                    <tr>
                        <td>{{ $row['employee']->name }}</td>
                        <td>{{ implode(' ', $row['reasons']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p><a href="{{ route('roster.index') }}">← Volver al cuadrante</a></p>
@endsection
