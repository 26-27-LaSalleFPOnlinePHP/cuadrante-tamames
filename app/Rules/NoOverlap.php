<?php

declare(strict_types=1);

namespace App\Rules;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use App\Data\Staff;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** R01 · Un empleado no puede tener dos turnos el mismo día. */
final class NoOverlap implements ValidationRule
{
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
        if ($employee === null || ! in_array($this->day, Shifts::DAYS, true)) {
            return;
        }

        foreach ($this->store->forEmployee($employee->id) as $assigned) {
            // Volver a asignarle el mismo turno no es un solapamiento.
            if ($assigned['day'] === $this->day && $assigned['shift'] !== $this->shift) {
                $fail("{$employee->name} ya tiene el turno de {$assigned['shift']} el {$this->day}.");

                return;
            }
        }
    }
}
