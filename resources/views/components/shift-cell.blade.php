{{--
    Una celda de la rejilla del cuadrante. Viene hecho: la rejilla lo usa con
    <x-shift-cell>, y tú no tienes que escribir ningún componente.

    Las celdas vacías ofrecen además F08 («¿Quién puede?») y F09 («Rellenar»).

    @props  day, shift, employee (App\Data\Employee|null)
--}}
@props(['day', 'shift', 'employee' => null])

<div {{ $attributes->merge(['class' => 'celda']) }}>
    @if ($employee)
        <p class="nom">{{ $employee->name }}</p>
        <p class="rol">{{ $employee->role }}</p>

        <form method="POST" action="{{ route('roster.remove') }}">
            @csrf
            <input type="hidden" name="day" value="{{ $day }}">
            <input type="hidden" name="shift" value="{{ $shift }}">
            <button>Quitar</button>
        </form>
    @else
        <p class="vacia">—</p>

        <a class="opt" href="{{ route('roster.candidates', ['day' => $day, 'shift' => $shift]) }}">¿Quién puede?</a>

        <form method="POST" action="{{ route('roster.autofill') }}">
            @csrf
            <input type="hidden" name="day" value="{{ $day }}">
            <input type="hidden" name="shift" value="{{ $shift }}">
            <button class="rellenar">Rellenar</button>
        </form>
    @endif
</div>
