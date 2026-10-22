<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/** F09 · Relleno automático. */
final class F09_AutofillTest extends TestCase
{
    public function test_rellena_con_el_primer_empleado_de_la_plantilla(): void
    {
        $this->post(route('roster.autofill'), ['day' => 'lunes', 'shift' => 'manana'])
            ->assertRedirect(route('roster.index'))
            ->assertSessionHas('status', fn (string $s) => str_contains($s, 'Marta Ferrer'));
    }

    public function test_se_salta_a_quien_incumpliria_una_regla(): void
    {
        // Marta ya trabaja el lunes por la tarde (R01): el siguiente es Jordi.
        $this->post(route('roster.assign'), ['day' => 'lunes', 'shift' => 'tarde', 'employee_id' => 1]);

        $this->post(route('roster.autofill'), ['day' => 'lunes', 'shift' => 'manana'])
            ->assertSessionHas('status', fn (string $s) => str_contains($s, 'Jordi Vidal'));
    }

    public function test_no_rellena_un_turno_que_ya_esta_cubierto(): void
    {
        $this->post(route('roster.assign'), ['day' => 'lunes', 'shift' => 'manana', 'employee_id' => 3]);

        $this->post(route('roster.autofill'), ['day' => 'lunes', 'shift' => 'manana'])
            ->assertSessionHasErrors('shift');
    }
}
