<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/** F08 · ¿Quién puede cubrir este turno? */
final class F08_CandidatesTest extends TestCase
{
    public function test_lista_los_candidatos_de_un_turno_vacio(): void
    {
        $this->get(route('roster.candidates', ['day' => 'lunes', 'shift' => 'manana']))
            ->assertOk()
            ->assertSee('Marta Ferrer');
    }

    public function test_explica_por_que_no_puede_quien_incumple_r02(): void
    {
        // Iván hace la tarde del lunes: el martes por la mañana solo descansaría 8 h.
        $this->post(route('roster.assign'), ['day' => 'lunes', 'shift' => 'tarde', 'employee_id' => 6]);

        $this->get(route('roster.candidates', ['day' => 'martes', 'shift' => 'manana']))
            ->assertOk()
            ->assertSee('No pueden')
            ->assertSee('Iván Sotelo solo descansaría 8 h');
    }

    public function test_explica_por_que_no_puede_quien_incumple_r01(): void
    {
        $this->post(route('roster.assign'), ['day' => 'lunes', 'shift' => 'manana', 'employee_id' => 1]);

        $this->get(route('roster.candidates', ['day' => 'lunes', 'shift' => 'noche']))
            ->assertOk()
            ->assertSee('Marta Ferrer ya tiene el turno de manana el lunes');
    }

    public function test_un_turno_ocupado_no_se_puede_consultar(): void
    {
        $this->post(route('roster.assign'), ['day' => 'lunes', 'shift' => 'manana', 'employee_id' => 1]);

        $this->get(route('roster.candidates', ['day' => 'lunes', 'shift' => 'manana']))
            ->assertSessionHasErrors('shift');
    }
}
