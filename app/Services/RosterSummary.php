<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RosterStore;
use App\Data\Employee;
use App\Data\Shifts;
use App\Data\Staff;

/**
 * F05 · Las reglas que solo avisan (R03, R04, R05).
 *
 * Nunca bloquean una asignación: un cuadrante se monta poco a poco y mientras
 * tanto está incompleto. Por eso viven aquí y no en el Form Request.
 */
final class RosterSummary
{
    public function __construct(private readonly RosterStore $store)
    {
    }

    /**
     * Horas asignadas a cada empleado de la plantilla, incluidos los que tienen 0.
     *
     * @return list<array{employee: Employee, hours: int}>
     */
    public function hoursPerEmployee(): array
    {
        return array_map(
            fn (Employee $e) => ['employee' => $e, 'hours' => $this->hoursOf($e->id)],
            Staff::all(),
        );
    }

    /**
     * R03 · Empleados con más horas asignadas que las de su contrato.
     *
     * @return list<array{employee: Employee, hours: int}>
     */
    public function overtime(): array
    {
        return array_values(array_filter(
            $this->hoursPerEmployee(),
            static fn (array $row) => $row['hours'] > $row['employee']->contractHours,
        ));
    }

    /**
     * Cuántas personas hay en cada turno de cada día.
     *
     * @return array<string, array<string, int>>
     */
    public function peoplePerShift(): array
    {
        $count = [];

        foreach ($this->store->all() as $day => $shifts) {
            foreach ($shifts as $shift => $employeeId) {
                $count[$day][$shift] = $employeeId === null ? 0 : 1;
            }
        }

        return $count;
    }

    /**
     * R04 · Turnos que no tienen a nadie asignado.
     *
     * @return list<array{day: string, shift: string}>
     */
    public function uncoveredShifts(): array
    {
        $uncovered = [];

        foreach ($this->peoplePerShift() as $day => $shifts) {
            foreach ($shifts as $shift => $people) {
                if ($people === 0) {
                    $uncovered[] = ['day' => $day, 'shift' => $shift];
                }
            }
        }

        return $uncovered;
    }

    /**
     * R05 · Días en los que ningún turno lo cubre un responsable.
     *
     * @return list<string>
     */
    public function daysWithoutManager(): array
    {
        $days = [];

        foreach ($this->store->all() as $day => $shifts) {
            $hasManager = false;

            foreach ($shifts as $employeeId) {
                if ($employeeId !== null && Staff::find($employeeId)?->role === 'responsable') {
                    $hasManager = true;
                    break;
                }
            }

            if (! $hasManager) {
                $days[] = $day;
            }
        }

        return $days;
    }

    /** Horas asignadas a un empleado en la semana. */
    public function hoursOf(int $employeeId): int
    {
        return array_sum(array_map(
            static fn (array $s) => Shifts::hours($s['shift']),
            $this->store->forEmployee($employeeId),
        ));
    }
}
