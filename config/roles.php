<?php

/*
|--------------------------------------------------------------------------
| Roles retirados de circulación (ocultar sin eliminar)
|--------------------------------------------------------------------------
|
| Los roles listados aquí dejan de ofrecerse al crear o editar un usuario y
| no pueden asignarse, pero NO se eliminan de la base de datos: sus permisos
| y sus asignaciones actuales quedan intactos.
|
| Esto significa que un usuario que YA tiene uno de estos roles sigue
| operando exactamente igual hasta que un administrador lo reasigne. El
| retiro afecta la asignación de roles nuevos, no las asignaciones vigentes.
|
| Sacar un nombre de esta lista reactiva el rol de inmediato, sin migración
| y sin pérdida de información. Mismo criterio que config/modulos.php.
|
*/

return [
    'retirados' => [
        'coordinador',
        'despachador',
        'gerente',
        'operador',
    ],
];
