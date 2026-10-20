<?php
/**
 * iniciar_sesion.php
 *
 * Autentica usuarios activos mediante nombre de usuario y contraseña
 * Guarda en la sesión solo los datos necesarios para la interfaz
 */

require_once __DIR__ . '/sesion.php';

/* Valida las credenciales y crea la sesión del usuario autenticado */
function autenticarUsuario(PDO $conexion, string $nombreUsuario, string $contrasena)
{
    $consulta = $conexion->prepare(
        'SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.nombre_usuario,
            u.contrasena_hash,
            u.estado,
            r.nombre AS rol_nombre
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.nombre_usuario = :nombre_usuario
           AND u.estado = :estado
         LIMIT 1'
    );
    $consulta->execute([
        ':nombre_usuario' => $nombreUsuario,
        ':estado' => 'activo',
    ]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
        return false;
    }

    iniciarSesionSistema();
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id_usuario' => (int) $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'apellido' => $usuario['apellido'],
        'nombre_usuario' => $usuario['nombre_usuario'],
        'rol_nombre' => $usuario['rol_nombre'] ?: 'Sin asignar',
    ];

    $actualizarAcceso = $conexion->prepare('UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id_usuario = :id_usuario');
    $actualizarAcceso->execute([':id_usuario' => (int) $usuario['id_usuario']]);

    return true;
}
