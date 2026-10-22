<?php

declare(strict_types=1);

namespace App\Stores;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use Illuminate\Contracts\Session\Session;

/**
 * El cuadrante semanal guardado en la sesión del usuario.
 *
 * Todo lo que se monta sigue ahí al recargar la página, mientras dure la
 * sesión (con la configuración por defecto de Laravel, en la tabla `sessions`
 * de la base de datos, asociada al navegador por una cookie). Cada navegador
 * tiene su propio cuadrante: no se comparte entre usuarios.
 *
 * Usa dos claves de sesión:
 *
 *  · `roster.week`  — el mapa día → turno → id de empleado (o `null`), con la
 *    misma forma que describe {@see RosterStore::all()}:
 *
 *        ['lunes' => ['manana' => 3, 'tarde' => null, 'noche' => 7], 'martes' => [...], ...]
 *
 *  · `roster.title` — el título libre de la semana, como cadena.
 *
 * Con la sesión vacía (la primera visita, o tras caducar la sesión) no hay que
 * inicializar nada: all() devuelve igualmente la semana completa, con los 7
 * días y sus 3 turnos a `null`; forEmployee() devuelve una lista vacía, y
 * title() la cadena vacía. La sesión solo se escribe al asignar, quitar o
 * cambiar el título.
 *
 * Los días y turnos salen siempre de {@see Shifts}: si la tabla cambia, los
 * huecos nuevos aparecen como `null` y los que ya no existen se descartan.
 *
 * Como manda el contrato, esta clase no valida reglas de negocio (R01, R02):
 * eso lo hace el Form Request antes de llamar a assign().
 */
final class SessionRosterStore implements RosterStore
{
    /** Clave de sesión donde se guarda el cuadrante. */
    private const WEEK_KEY = 'roster.week';

    /** Clave de sesión donde se guarda el título de la semana. */
    private const TITLE_KEY = 'roster.title';

    /**
     * No se crea a mano: el contenedor la construye al pedir un RosterStore
     * (ver AppServiceProvider) y le inyecta la sesión de la petición actual.
     */
    public function __construct(private readonly Session $session)
    {
    }

    /**
     * El cuadrante completo de la semana, leído de `roster.week`.
     *
     * Devuelve siempre los 7 días con sus 3 turnos; un turno sin cubrir es
     * `null`. Con la sesión vacía, todos los turnos son `null`.
     *
     * @return array<string, array<string, int|null>>
     */
    public function all(): array
    {
        $saved = (array) $this->session->get(self::WEEK_KEY, []);
        $week = [];

        foreach (Shifts::DAYS as $day) {
            foreach (Shifts::names() as $shift) {
                $id = $saved[$day][$shift] ?? null;
                $week[$day][$shift] = $id === null ? null : (int) $id;
            }
        }

        return $week;
    }

    /**
     * Asigna un empleado a un turno y guarda la semana en `roster.week`.
     *
     * Si el turno ya tenía a alguien, lo sustituye. No valida R01 ni R02.
     */
    public function assign(string $day, string $shift, int $employeeId): void
    {
        $week = $this->all();
        $week[$day][$shift] = $employeeId;

        $this->session->put(self::WEEK_KEY, $week);
    }

    /**
     * Deja vacío un turno en `roster.week`.
     *
     * Si ya estaba vacío, no hace nada (ni siquiera escribe en la sesión).
     */
    public function remove(string $day, string $shift): void
    {
        $week = $this->all();

        if (! isset($week[$day][$shift])) {
            return;
        }

        $week[$day][$shift] = null;
        $this->session->put(self::WEEK_KEY, $week);
    }

    /**
     * Los turnos que tiene asignados un empleado, en orden de lunes a domingo.
     *
     * Lista vacía si no tiene ninguno o si la sesión está vacía.
     *
     * @return list<array{day: string, shift: string}>
     */
    public function forEmployee(int $employeeId): array
    {
        $shifts = [];

        foreach ($this->all() as $day => $dayShifts) {
            foreach ($dayShifts as $shift => $id) {
                if ($id === $employeeId) {
                    $shifts[] = ['day' => $day, 'shift' => $shift];
                }
            }
        }

        return $shifts;
    }

    /**
     * El título de la semana guardado en `roster.title`.
     *
     * Cadena vacía si todavía no se ha puesto ninguno. Es texto libre del
     * usuario: quien lo muestre debe escaparlo ({{ }} en Blade).
     */
    public function title(): string
    {
        return (string) $this->session->get(self::TITLE_KEY, '');
    }

    /** Guarda el título de la semana en `roster.title`, tal cual llega. */
    public function setTitle(string $title): void
    {
        $this->session->put(self::TITLE_KEY, $title);
    }
}
