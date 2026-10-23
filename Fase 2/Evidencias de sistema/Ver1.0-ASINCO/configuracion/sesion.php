<?php
/**
 * sesión.php
 *
 * Centraliza el inicio, consulta y cierre de sesiones autenticadas
 * También protege las vistas internas cuando no existe una sesión activa
 */

/* Inicia una sesión segura una sola vez por solicitud */
function iniciarSesionSistema()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/* Comprueba si existe un usuario autenticado en la sesión actual */
function haySesionActiva()
{
    iniciarSesionSistema();
    return isset($_SESSION['usuario']['id_usuario']);
}

/* Devuelve los datos del usuario autenticado o un arreglo vacio */
function obtenerUsuarioSesion()
{
    iniciarSesionSistema();
    return $_SESSION['usuario'] ?? [];
}

/* Redirige al login cuando se intenta abrir una vista sin autenticación */
function exigirSesion()
{
    if (!haySesionActiva()) {
        header('Location: ../index.php?mensaje=sesion_requerida');
        exit;
    }
}

/* Elimina todos los datos de la sesión y revoca su identificador */
function destruirSesionSistema()
{
    iniciarSesionSistema();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }

    session_destroy();
}
