<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\RosterStore;
use App\Data\Employee;
use App\Data\Shifts;
use App\Data\Staff;
use App\Rules\MinimumRest;
use App\Rules\NoOverlap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * F04 · Toda la validación de una asignación vive aquí, no en el controlador.
 */
final class AssignShiftRequest extends FormRequest
{
    /**
     * Esta aplicación no tiene usuarios, así que no hay nada que autorizar.
     * Devolver true aquí es correcto EN ESTA PRÁCTICA; en una con login, no.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Además de lo evidente — que el día, el turno y el empleado existan — las
     * dos reglas bloqueantes (R01 y R02) se implementan como reglas propias.
     *
     * @return array<string, mixed>
     */
    public function rules(RosterStore $store): array
    {
        $day = (string) $this->input('day');
        $shift = (string) $this->input('shift');

        return [
            'day' => ['required', 'string', Rule::in(Shifts::DAYS)],
            'shift' => ['required', 'string', Rule::in(Shifts::names())],
            'employee_id' => [
                'bail',
                'required',
                'integer',
                Rule::in(array_map(static fn (Employee $e) => $e->id, Staff::all())),
                new NoOverlap($store, $day, $shift),
                new MinimumRest($store, $day, $shift),
            ],
        ];
    }

    /**
     * Cuando se rechaza una asignación el usuario tiene que entender por qué:
     * «Marta ya tiene el turno de tarde el martes» sirve; «asignación no válida» no.
     *
     * Los mensajes de R01 y R02 los construyen las propias reglas, porque
     * necesitan el nombre del empleado y el turno con el que choca.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'day.required' => 'Elige el día de la semana.',
            'day.in' => 'El día «:input» no existe: tiene que ser de lunes a domingo.',
            'shift.required' => 'Elige el turno.',
            'shift.in' => 'El turno «:input» no existe: tiene que ser manana, tarde o noche.',
            'employee_id.required' => 'Elige a qué empleado le asignas el turno.',
            'employee_id.integer' => 'El empleado elegido no es válido.',
            'employee_id.in' => 'No hay ningún empleado con id :input en la plantilla.',
        ];
    }
}
