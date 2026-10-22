<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * F02 · Se lanza cuando se busca un id que no está en la plantilla.
 *
 * Es una excepción de dominio: no sabe nada de HTTP, solo dice que ese
 * empleado no existe. Convertirla en un 404 es trabajo del manejador de
 * excepciones (withExceptions, en bootstrap/app.php).
 */
final class EmployeeNotFoundException extends RuntimeException
{
    public function __construct(public readonly int $employeeId)
    {
        parent::__construct("No existe ningún empleado con id {$employeeId}.");
    }
}
