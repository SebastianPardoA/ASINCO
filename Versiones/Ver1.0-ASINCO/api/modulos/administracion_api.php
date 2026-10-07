<?php
/**
 * administracion_api.php
 *
 * Contiene las operaciones del módulo de administración
 * Permite crear, listar, consultar y actualizar usuarios con consultas preparadas
 */

/* Crea un usuario nuevo a partir de los datos recibidos por POST */
function crearUsuario(PDO $conexion)
{
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $apellido = trim((string) ($_POST['apellido'] ?? ''));
    $correo = trim((string) ($_POST['correo_electronico'] ?? ''));
    $nombreUsuario = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $contrasena = (string) ($_POST['contrasena'] ?? '');
    $confirmacion = (string) ($_POST['contrasena_confirmacion'] ?? '');
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $nombreRol = trim((string) ($_POST['rol'] ?? ''));
    $estado = trim((string) ($_POST['estado'] ?? 'activo'));

    if ($nombre === '' || $apellido === '' || $correo === '' || $nombreUsuario === '' || $contrasena === '') {
        throw new InvalidArgumentException('Completa todos los campos obligatorios.');
    }

    if (strlen($nombre) > 100 || strlen($apellido) > 100) {
        throw new InvalidArgumentException('El nombre y el apellido no pueden superar los 100 caracteres.');
    }

    if (strlen($correo) > 150) {
        throw new InvalidArgumentException('El correo electrónico no puede superar los 150 caracteres.');
    }

    if (strlen($nombreUsuario) > 80) {
        throw new InvalidArgumentException('El nombre de usuario no puede superar los 80 caracteres.');
    }

    if (strlen($telefono) > 30) {
        throw new InvalidArgumentException('El teléfono no puede superar los 30 caracteres.');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
    }

    if (strlen($contrasena) < 8) {
        throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
    }

    if ($contrasena !== $confirmacion) {
        throw new InvalidArgumentException('Las contrasenas no coinciden.');
    }

    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new InvalidArgumentException('El estado seleccionado no es válido.');
    }

    $idRol = null;

    if ($nombreRol !== '') {
        $consultaRol = $conexion->prepare(
            'SELECT id_rol FROM roles WHERE nombre = :nombre AND estado = :estado LIMIT 1'
        );
        $consultaRol->execute([
            ':nombre' => $nombreRol,
            ':estado' => 'activo',
        ]);
        $rol = $consultaRol->fetch();

        if (!$rol) {
            throw new InvalidArgumentException('El rol seleccionado no existe o no está activo.');
        }

        $idRol = (int) $rol['id_rol'];
    }

    $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
    $consultaUsuario = $conexion->prepare(
        'INSERT INTO usuarios
            (nombre, apellido, correo_electronico, nombre_usuario, contrasena_hash, telefono, id_rol, estado)
         VALUES
            (:nombre, :apellido, :correo, :nombre_usuario, :contrasena_hash, :telefono, :id_rol, :estado)'
    );

    $consultaUsuario->execute([
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':correo' => $correo,
        ':nombre_usuario' => $nombreUsuario,
        ':contrasena_hash' => $contrasenaHash,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':id_rol' => $idRol,
        ':estado' => $estado,
    ]);

    return [
        'id_usuario' => (int) $conexion->lastInsertId(),
        'mensaje' => 'Usuario creado correctamente.',
    ];
}

/* Obtiene una página del listado real de usuarios y su rol asignado */
function listarUsuarios(PDO $conexion, int $pagina = 1, int $usuariosPorPagina = 7, string $estado = '', string $busqueda = '')
{
    $pagina = max(1, $pagina);
    $usuariosPorPagina = min(7, max(1, $usuariosPorPagina));
    $desplazamiento = ($pagina - 1) * $usuariosPorPagina;

    if (!in_array($estado, ['', 'activo', 'inactivo'], true)) {
        throw new InvalidArgumentException('El filtro de estado no es válido.');
    }

    $condiciones = [];
    $parametros = [];

    if ($estado !== '') {
        $condiciones[] = 'u.estado = :estado';
        $parametros[':estado'] = $estado;
    }

    $busqueda = trim($busqueda);

    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('El texto de búsqueda es demasiado largo.');
    }

    if ($busqueda !== '') {
        $condiciones[] = "CONCAT(u.nombre, ' ', u.apellido) LIKE :busqueda";
        $parametros[':busqueda'] = '%' . $busqueda . '%';
    }

    $whereSql = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

    $consultaTotal = $conexion->prepare('SELECT COUNT(*) FROM usuarios u' . $whereSql);
    $consultaTotal->execute($parametros);
    $totalUsuarios = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($totalUsuarios / $usuariosPorPagina));

    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $desplazamiento = ($pagina - 1) * $usuariosPorPagina;
    }

    $consulta = $conexion->prepare(
        'SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo_electronico,
            u.nombre_usuario,
            u.estado,
            u.ultimo_acceso,
            r.nombre AS rol_nombre
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         ' . $whereSql . '
         ORDER BY u.nombre ASC, u.apellido ASC, u.id_usuario ASC
         LIMIT :limite OFFSET :desplazamiento'
    );
    if ($estado !== '') {
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
    }
    if ($busqueda !== '') {
        $consulta->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', $usuariosPorPagina, PDO::PARAM_INT);
    $consulta->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
    $consulta->execute();

    $usuarios = [];

    foreach ($consulta->fetchAll() as $usuario) {
        $usuarios[] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'correo_electronico' => $usuario['correo_electronico'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'estado' => $usuario['estado'],
            'rol_nombre' => $usuario['rol_nombre'] ?: 'Sin asignar',
            // Valor temporal hasta implementar el registro real de accesos
            'ultimo_acceso' => $usuario['ultimo_acceso'] ?: '23/08/2026 08:45',
        ];
    }

    return [
        'usuarios' => $usuarios,
        'paginacion' => [
            'pagina_actual' => $pagina,
            'usuarios_por_pagina' => $usuariosPorPagina,
            'total_usuarios' => $totalUsuarios,
            'total_paginas' => $totalPaginas,
        ],
    ];
}

/* Obtiene todos los datos editables y de auditoria de un usuario */
function obtenerUsuario(PDO $conexion, int $idUsuario)
{
    if ($idUsuario < 1) {
        throw new InvalidArgumentException('El usuario solicitado no es válido.');
    }

    $consulta = $conexion->prepare(
        'SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo_electronico,
            u.nombre_usuario,
            u.telefono,
            u.id_rol,
            r.nombre AS rol_nombre,
            u.estado,
            u.ultimo_acceso,
            u.fecha_creacion,
            u.fecha_actualizacion
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.id_usuario = :id_usuario
         LIMIT 1'
    );
    $consulta->execute([':id_usuario' => $idUsuario]);
    $usuario = $consulta->fetch();

    if (!$usuario) {
        throw new InvalidArgumentException('El usuario solicitado no existe.');
    }

    return [
        'id_usuario' => (int) $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido' => $usuario['apellido'],
        'correo_electronico' => $usuario['correo_electronico'],
        'nombre_usuario' => $usuario['nombre_usuario'],
        'telefono' => $usuario['telefono'] ?: '',
        'id_rol' => $usuario['id_rol'] !== null ? (int) $usuario['id_rol'] : null,
        'rol_nombre' => $usuario['rol_nombre'] ?: '',
        'estado' => $usuario['estado'],
        'ultimo_acceso' => $usuario['ultimo_acceso'] ?: '23/08/2026 08:45',
        'fecha_creacion' => $usuario['fecha_creacion'],
        'fecha_actualizacion' => $usuario['fecha_actualizacion'],
    ];
}

/* Actualiza los datos permitidos de un usuario sin modificar su id ni contraseña */
function actualizarUsuario(PDO $conexion)
{
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $apellido = trim((string) ($_POST['apellido'] ?? ''));
    $correo = trim((string) ($_POST['correo_electronico'] ?? ''));
    $nombreUsuario = trim((string) ($_POST['nombre_usuario'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $nombreRol = trim((string) ($_POST['rol'] ?? ''));
    $estado = trim((string) ($_POST['estado'] ?? ''));
    $contrasenaNueva = (string) ($_POST['contrasena_nueva'] ?? '');
    $confirmacionNueva = (string) ($_POST['contrasena_confirmacion_nueva'] ?? '');

    if ($idUsuario < 1 || $nombre === '' || $apellido === '' || $correo === '' || $nombreUsuario === '') {
        throw new InvalidArgumentException('Completa todos los campos obligatorios.');
    }

    if (strlen($nombre) > 100 || strlen($apellido) > 100) {
        throw new InvalidArgumentException('El nombre y el apellido no pueden superar los 100 caracteres.');
    }

    if (strlen($correo) > 150) {
        throw new InvalidArgumentException('El correo electrónico no puede superar los 150 caracteres.');
    }

    if (strlen($nombreUsuario) > 80) {
        throw new InvalidArgumentException('El nombre de usuario no puede superar los 80 caracteres.');
    }

    if (strlen($telefono) > 30) {
        throw new InvalidArgumentException('El teléfono no puede superar los 30 caracteres.');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
    }

    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        throw new InvalidArgumentException('El estado seleccionado no es válido.');
    }

    if ($contrasenaNueva !== '') {
        if (strlen($contrasenaNueva) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }

        if ($contrasenaNueva !== $confirmacionNueva) {
            throw new InvalidArgumentException('Las contrasenas no coinciden.');
        }
    }

    $idRol = null;

    if ($nombreRol !== '') {
        $consultaRol = $conexion->prepare(
            'SELECT id_rol FROM roles WHERE nombre = :nombre AND estado = :estado LIMIT 1'
        );
        $consultaRol->execute([':nombre' => $nombreRol, ':estado' => 'activo']);
        $rol = $consultaRol->fetch();

        if (!$rol) {
            throw new InvalidArgumentException('El rol seleccionado no existe o no está activo.');
        }

        $idRol = (int) $rol['id_rol'];
    }

    $campos = [
        'nombre = :nombre',
        'apellido = :apellido',
        'correo_electronico = :correo',
        'nombre_usuario = :nombre_usuario',
        'telefono = :telefono',
        'id_rol = :id_rol',
        'estado = :estado',
    ];
    $parametros = [
        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':correo' => $correo,
        ':nombre_usuario' => $nombreUsuario,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':id_rol' => $idRol,
        ':estado' => $estado,
        ':id_usuario' => $idUsuario,
    ];

    if ($contrasenaNueva !== '') {
        $campos[] = 'contrasena_hash = :contrasena_hash';
        $parametros[':contrasena_hash'] = password_hash($contrasenaNueva, PASSWORD_DEFAULT);
    }

    $consulta = $conexion->prepare(
        'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id_usuario = :id_usuario'
    );
    $consulta->execute($parametros);

    return ['mensaje' => 'Usuario actualizado correctamente.'];
}

/* Elimina definitivamente un usuario por su identificador */
function eliminarUsuario(PDO $conexion)
{
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);

    if ($idUsuario < 1) {
        throw new InvalidArgumentException('El usuario solicitado no es válido.');
    }

    $consulta = $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = :id_usuario');
    $consulta->execute([':id_usuario' => $idUsuario]);

    if ($consulta->rowCount() === 0) {
        throw new InvalidArgumentException('El usuario solicitado no existe.');
    }

    return ['mensaje' => 'Usuario eliminado correctamente.'];
}
