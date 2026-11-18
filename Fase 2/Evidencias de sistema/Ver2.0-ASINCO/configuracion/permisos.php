<?php
/**
 * permisos.php
 *
 * Centraliza las restricciones de acceso por rol para vistas y API
 */

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/base_datos.php';

/* Actualiza el rol de la sesión para que los cambios se vean de inmediato */
function sincronizarRolUsuarioSesion(PDO $conexion = null): string
{
    $usuario = obtenerUsuarioSesion();
    $idUsuario = (int) ($usuario['id_usuario'] ?? 0);
    if ($idUsuario < 1) {
        return 'Sin asignar';
    }

    $conexion = $conexion ?? obtenerConexionBaseDatos();
    $consulta = $conexion->prepare(
        'SELECT r.nombre
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.id_usuario = :id_usuario
         LIMIT 1'
    );
    $consulta->execute([':id_usuario' => $idUsuario]);
    $rol = $consulta->fetchColumn();
    $rolNombre = is_string($rol) && trim($rol) !== '' ? trim($rol) : 'Sin asignar';
    $_SESSION['usuario']['rol_nombre'] = $rolNombre;

    return $rolNombre;
}

/* Devuelve un rol válido o null cuando la cuenta no tiene uno asignado */
function normalizarRolAcceso($rol = null): ?string
{
    $rol = trim((string) ($rol ?? obtenerUsuarioSesion()['rol_nombre'] ?? ''));
    return in_array($rol, ['Administrador', 'Supervisor', 'Usuario'], true) ? $rol : null;
}

/* Comprueba si la cuenta tiene un rol reconocido y asignado */
function usuarioTieneRolAsignado($rol = null): bool
{
    return normalizarRolAcceso($rol) !== null;
}

/* Comprueba el acceso a una vista y sus pantallas de detalle asociadas */
function usuarioPuedeVerVista(string $vista, $rol = null): bool
{
    $rol = normalizarRolAcceso($rol);
    if ($rol === null) {
        return false;
    }
    if ($rol === 'Administrador') {
        return true;
    }

    $vistasBase = [
        'detalle_compra' => 'historial_compras',
        'detalle_producto' => 'inventario',
        'ingresar_productos' => 'registro_compra',
    ];
    $vista = $vistasBase[$vista] ?? $vista;
    $vistasValidas = ['dashboard', 'inventario', 'registro_compra', 'historial_compras', 'proveedores', 'metodos_despacho', 'administracion'];
    if (!in_array($vista, $vistasValidas, true)) {
        return false;
    }

    if ($vista === 'administracion') {
        return false;
    }
    if ($vista === 'dashboard') {
        return $rol === 'Supervisor';
    }

    return true;
}

/* Comprueba permisos generales de los módulos de la API */
function usuarioPuedeUsarModuloApi(string $modulo, $rol = null): bool
{
    $modulosValidos = ['administracion', 'dashboard', 'inventario', 'historial_compras', 'registro_compra', 'proveedores', 'transportistas'];
    if (!in_array($modulo, $modulosValidos, true)) {
        return false;
    }

    $rol = normalizarRolAcceso($rol);
    if ($rol === null) {
        return false;
    }
    if ($modulo === 'administracion') {
        return $rol === 'Administrador';
    }
    if ($modulo === 'dashboard') {
        return in_array($rol, ['Administrador', 'Supervisor'], true);
    }

    return true;
}

/* Define una pantalla inicial disponible según el rol */
function obtenerVistaInicioSesion($rol = null): ?string
{
    $rol = normalizarRolAcceso($rol);
    if ($rol === null) {
        return null;
    }
    return $rol === 'Usuario' ? 'inventario.php' : 'dashboard.php';
}

/* Protege una vista antes de que se envíe contenido al navegador */
function exigirAccesoVista(string $vista): void
{
    exigirSesion();

    try {
        $rol = sincronizarRolUsuarioSesion();
    } catch (Throwable $error) {
        $rol = obtenerUsuarioSesion()['rol_nombre'] ?? null;
    }

    if (!usuarioTieneRolAsignado($rol)) {
        destruirSesionSistema();
        header('Location: ../index.php?mensaje=rol_no_asignado');
        exit;
    }

    if (!usuarioPuedeVerVista($vista, $rol)) {
        header('Location: ' . obtenerVistaInicioSesion($rol) . '?mensaje=acceso_denegado');
        exit;
    }
}
