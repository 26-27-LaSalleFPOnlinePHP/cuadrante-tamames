<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use App\Data\Staff;
use App\Http\Requests\AssignShiftRequest;
use App\Http\Requests\EmptyShiftRequest;
use App\Services\RosterSummary;
use App\Services\ShiftCandidates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * F03 · El cuadrante: verlo, asignar, quitar y cambiar el título.
 * F05 · El resumen con los avisos R03–R05.
 * F08 · Quién puede cubrir un turno y F09 · relleno automático.
 *
 * Solo depende de la interfaz RosterStore: qué implementación llega lo decide
 * AppServiceProvider (F06).
 */
final class RosterController
{
    public function __construct(private readonly RosterStore $store)
    {
    }

    public function index(): View
    {
        $roster = [];

        foreach ($this->store->all() as $day => $shifts) {
            foreach ($shifts as $shift => $employeeId) {
                $roster[$day][$shift] = $employeeId === null ? null : Staff::find($employeeId);
            }
        }

        $title = $this->store->title();

        return view('roster.index', [
            'roster' => $roster,
            'title' => $title !== '' ? $title : null,
        ]);
    }

    public function assign(AssignShiftRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = Staff::find((int) $data['employee_id']);

        $this->store->assign($data['day'], $data['shift'], $employee->id);

        return redirect()
            ->route('roster.index')
            ->with('status', "{$employee->name} cubre el turno de {$data['shift']} del {$data['day']}.");
    }

    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', Rule::in(Shifts::DAYS)],
            'shift' => ['required', Rule::in(Shifts::names())],
        ]);

        $this->store->remove($data['day'], $data['shift']);

        return redirect()
            ->route('roster.index')
            ->with('status', "El turno de {$data['shift']} del {$data['day']} queda libre.");
    }

    public function title(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:100'],
        ]);

        $this->store->setTitle((string) ($data['title'] ?? ''));

        return redirect()->route('roster.index')->with('status', 'Título actualizado.');
    }

    /** F08 · Quién puede cubrir un turno vacío, y por qué no pueden los demás. */
    public function candidates(EmptyShiftRequest $request): View
    {
        $data = $request->validated();
        $rows = (new ShiftCandidates($this->store))->evaluate($data['day'], $data['shift']);

        return view('roster.candidates', [
            'day' => $data['day'],
            'shift' => $data['shift'],
            'available' => array_values(array_filter($rows, static fn (array $r) => $r['reasons'] === [])),
            'unavailable' => array_values(array_filter($rows, static fn (array $r) => $r['reasons'] !== [])),
        ]);
    }

    /** F09 · Rellena un turno vacío con el primer empleado que cumple todas las reglas. */
    public function autofill(EmptyShiftRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = (new ShiftCandidates($this->store))->first($data['day'], $data['shift']);

        if ($employee === null) {
            return redirect()
                ->route('roster.index')
                ->withErrors([
                    'autofill' => "Nadie puede cubrir el turno de {$data['shift']} del {$data['day']} "
                        . 'sin incumplir alguna regla.',
                ]);
        }

        $this->store->assign($data['day'], $data['shift'], $employee->id);

        return redirect()
            ->route('roster.index')
            ->with(
                'status',
                "Relleno automático: {$employee->name} cubre el turno de {$data['shift']} del {$data['day']}.",
            );
    }

    public function summary(): View
    {
        $summary = new RosterSummary($this->store);
        $title = $this->store->title();

        return view('roster.summary', [
            'title' => $title !== '' ? $title : null,
            'hours' => $summary->hoursPerEmployee(),
            'people' => $summary->peoplePerShift(),
            'overtime' => $summary->overtime(),
            'uncovered' => $summary->uncoveredShifts(),
            'daysWithoutManager' => $summary->daysWithoutManager(),
        ]);
    }
}
