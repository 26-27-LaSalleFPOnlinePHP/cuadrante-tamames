{{--
    F05 · Resumen de la semana: horas por empleado y los avisos R03–R05.
    Son avisos, no errores: nunca impiden asignar.
--}}
@extends('layout')

@section('title', 'Resumen de la semana')

@section('content')
    <h1>Resumen · {{ $title ?? 'Semana sin título' }}</h1>

    @if ($overtime === [] && $uncovered === [] && $daysWithoutManager === [])
        <p class="flash">El cuadrante cuadra: no hay ningún aviso.</p>
    @else
        <div class="errors">
            <ul>
                @foreach ($overtime as $row)
                    <li>
                        R03 · {{ $row['employee']->name }} tiene {{ $row['hours'] }} h asignadas
                        y su contrato es de {{ $row['employee']->contractHours }} h.
                    </li>
                @endforeach

                @foreach ($daysWithoutManager as $day)
                    <li>R05 · El {{ $day }} no hay ningún responsable.</li>
                @endforeach

                @if ($uncovered !== [])
                    <li>
                        R04 · {{ count($uncovered) }} turnos sin cubrir:
                        {{ implode(', ', array_map(static fn ($s) => $s['day'] . ' ' . $s['shift'], $uncovered)) }}.
                    </li>
                @endif
            </ul>
        </div>
    @endif

    <h2>Personas en cada turno</h2>

    <table>
        <thead>
            <tr>
                <th></th>
                @foreach (\App\Data\Shifts::DAYS as $day)
                    <th>{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (\App\Data\Shifts::names() as $shift)
                <tr>
                    <th>{{ $shift }}</th>
                    @foreach (\App\Data\Shifts::DAYS as $day)
                        <td>{{ $people[$day][$shift] }}{{ $people[$day][$shift] === 0 ? ' ⚠' : '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Horas por empleado</h2>

    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Rol</th>
                <th>Asignadas</th>
                <th>Contrato</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($hours as $row)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $row['employee']->id) }}">{{ $row['employee']->name }}</a>
                    </td>
                    <td>{{ $row['employee']->role }}</td>
                    <td>{{ $row['hours'] }} h{{ $row['hours'] > $row['employee']->contractHours ? ' ⚠' : '' }}</td>
                    <td>{{ $row['employee']->contractHours }} h</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
