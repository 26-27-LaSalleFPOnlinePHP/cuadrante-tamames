<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use App\Data\Staff;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * F08 y F09 · Un día y un turno que existen y que todavía están vacíos.
 *
 * Lo usan tanto «¿Quién puede cubrir este turno?» como «Relleno automático»:
 * las dos parten de un turno sin nadie.
 */
final class EmptyShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(RosterStore $store): array
    {
        $day = (string) $this->input('day');

        return [
            'day' => ['required', 'string', Rule::in(Shifts::DAYS)],
            'shift' => [
                'bail',
                'required',
                'string',
                Rule::in(Shifts::names()),
                static function (string $attribute, mixed $value, Closure $fail) use ($store, $day): void {
                    $employeeId = $store->all()[$day][$value] ?? null;

                    if ($employeeId !== null) {
                        $name = Staff::find($employeeId)?->name ?? "el empleado {$employeeId}";
                        $fail("El turno de {$value} del {$day} ya lo cubre {$name}: quítalo antes.");
                    }
                },
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'day.required' => 'Elige el día de la semana.',
            'day.in' => 'El día «:input» no existe: tiene que ser de lunes a domingo.',
            'shift.required' => 'Elige el turno.',
            'shift.in' => 'El turno «:input» no existe: tiene que ser manana, tarde o noche.',
        ];
    }
}
