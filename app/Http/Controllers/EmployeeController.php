<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\RosterStore;
use App\Data\Shifts;
use App\Data\Staff;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** F01 · Listado de la plantilla y F02 · ficha de cada empleado. */
final class EmployeeController
{
    public function __construct(private readonly RosterStore $store)
    {
    }

    public function index(Request $request): View
    {
        $role = $request->input('role');
        $role = in_array($role, Staff::ROLES, true) ? $role : null;

        $sort = $request->input('sort') === 'hours' ? 'hours' : 'name';

        $employees = Staff::byRole($role);

        usort(
            $employees,
            static fn ($a, $b) => $sort === 'hours'
                ? [$a->contractHours, $a->name] <=> [$b->contractHours, $b->name]
                : strcmp($a->name, $b->name),
        );

        return view('employees.index', [
            'employees' => $employees,
            'roles' => Staff::ROLES,
            'role' => $role,
            'sort' => $sort,
        ]);
    }

    public function show(int $id): View
    {
        $employee = Staff::findOrFail($id);

        $shifts = $this->store->forEmployee($employee->id);
        $hours = array_sum(array_map(static fn (array $s) => Shifts::hours($s['shift']), $shifts));

        return view('employees.show', [
            'employee' => $employee,
            'shifts' => $shifts,
            'hours' => $hours,
        ]);
    }
}
