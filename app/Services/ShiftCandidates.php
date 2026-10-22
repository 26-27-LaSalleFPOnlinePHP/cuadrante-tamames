<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RosterStore;
use App\Data\Employee;
use App\Data\Shifts;
use App\Data\Staff;
use App\Rules\MinimumRest;
use App\Rules\NoOverlap;

/**
 * F08 · ¿Quién puede cubrir este turno? y F09 · Relleno automático.
 *
 * Para un turno vacío, pasa a cada empleado de la plantilla por las MISMAS
 * reglas que usa AssignShiftRequest (R01 NoOverlap y R02 MinimumRest): no
 * repite su lógica, las ejecuta. Además descarta a quien pasaría de las horas
 * de su contrato (R03): en una asignación manual solo avisa, pero aquí se
 * busca a alguien que no incumpla ninguna regla.
 */
final class ShiftCandidates
{
    public function __construct(private readonly RosterStore $store)
    {
    }

    /**
     * Todos los empleados, cada uno con los motivos por los que NO puede
     * cubrir el turno. Lista de motivos vacía = puede cubrirlo.
     *
     * @return list<array{employee: Employee, hours: int, reasons: list<string>}>
     */
    public function evaluate(string $day, string $shift): array
    {
        $rules = [
            new NoOverlap($this->store, $day, $shift),
            new MinimumRest($this->store, $day, $shift),
        ];
        $summary = new RosterSummary($this->store);
        $result = [];

        foreach (Staff::all() as $employee) {
            $reasons = [];
            $fail = function (string $message) use (&$reasons): void {
                $reasons[] = $message;
            };

            foreach ($rules as $rule) {
                $rule->validate('employee_id', $employee->id, $fail);
            }

            $hours = $summary->hoursOf($employee->id);
            $after = $hours + Shifts::hours($shift);

            if ($after > $employee->contractHours) {
                $reasons[] = "{$employee->name} pasaría a {$after} h "
                    . "y su contrato es de {$employee->contractHours} h.";
            }

            $result[] = ['employee' => $employee, 'hours' => $hours, 'reasons' => $reasons];
        }

        return $result;
    }

    /**
     * F08 · Los empleados que pueden cubrir el turno, en el orden de la plantilla.
     *
     * @return list<Employee>
     */
    public function candidates(string $day, string $shift): array
    {
        return array_values(array_map(
            static fn (array $row) => $row['employee'],
            array_filter($this->evaluate($day, $shift), static fn (array $row) => $row['reasons'] === []),
        ));
    }

    /** F09 · El primer empleado de la plantilla que puede cubrir el turno, o null si no hay nadie. */
    public function first(string $day, string $shift): ?Employee
    {
        return $this->candidates($day, $shift)[0] ?? null;
    }
}
