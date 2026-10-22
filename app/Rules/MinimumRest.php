<?php

declare(strict_types=1);

namespace App\Rules;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use App\Data\Staff;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * R02 · Descanso mínimo de 12 horas entre el final de un turno y el principio
 * del siguiente.
 *
 * CALCÚLALO a partir de App\Data\Shifts. Si escribes a mano qué combinaciones
 * fallan, funcionará hoy y dejará de funcionar en cuanto cambie una hora de la
 * tabla — y cuatro de las optativas reutilizan este cálculo.
 */
final class MinimumRest implements ValidationRule
{
    public const MINIMUM_MINUTES = 12 * 60;

    private const MINUTES_PER_DAY = 24 * 60;

    /**
     * Ya viene hecho: AssignShiftRequest te pasa el cuadrante actual y el día y
     * el turno que se quieren asignar. En validate() recibes el id del empleado.
     */
    public function __construct(
        private readonly RosterStore $store,
        private readonly string $day,
        private readonly string $shift,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $employee = Staff::find((int) $value);

        // Si el día o el turno no existen ya lo dicen las reglas de day y shift.
        if (
            $employee === null
            || ! in_array($this->day, Shifts::DAYS, true)
            || ! in_array($this->shift, Shifts::names(), true)
        ) {
            return;
        }

        foreach ($this->store->forEmployee($employee->id) as $assigned) {
            // El mismo día ya lo controla R01.
            if ($assigned['day'] === $this->day) {
                continue;
            }

            $rest = self::restMinutes($assigned['day'], $assigned['shift'], $this->day, $this->shift);

            if ($rest < self::MINIMUM_MINUTES) {
                $fail(sprintf(
                    '%s solo descansaría %s h entre el turno de %s del %s y el de %s del %s (mínimo %d h).',
                    $employee->name,
                    rtrim(rtrim(number_format(max($rest, 0) / 60, 1, ',', ''), '0'), ','),
                    $assigned['shift'],
                    $assigned['day'],
                    $this->shift,
                    $this->day,
                    intdiv(self::MINIMUM_MINUTES, 60),
                ));

                return;
            }
        }
    }

    /**
     * Minutos de descanso entre dos turnos de la semana, en el orden en que
     * ocurren: desde el final del primero hasta el principio del segundo.
     * Negativo si se solapan. Todo sale de la tabla de App\Data\Shifts.
     */
    public static function restMinutes(string $dayA, string $shiftA, string $dayB, string $shiftB): int
    {
        $startA = self::absoluteStart($dayA, $shiftA);
        $startB = self::absoluteStart($dayB, $shiftB);

        [$first, $second] = $startA <= $startB
            ? [[$dayA, $shiftA], [$dayB, $shiftB]]
            : [[$dayB, $shiftB], [$dayA, $shiftA]];

        $firstEnd = Shifts::dayIndex($first[0]) * self::MINUTES_PER_DAY + Shifts::endsAt($first[1]);

        return self::absoluteStart($second[0], $second[1]) - $firstEnd;
    }

    /** Minuto de la semana (lunes 00:00 = 0) en que empieza un turno. */
    private static function absoluteStart(string $day, string $shift): int
    {
        return Shifts::dayIndex($day) * self::MINUTES_PER_DAY + Shifts::startsAt($shift);
    }
}
