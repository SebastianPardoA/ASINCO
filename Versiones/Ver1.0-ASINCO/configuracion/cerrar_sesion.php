<?php
/**
 * cerrar_sesion.php
 *
 * Procesa el cierre seguro de la sesión actual y retorna al login
 */

require_once __DIR__ . '/sesion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    destruirSesionSistema();
    header('Location: ../index.php?mensaje=sesion_cerrada');
    exit;
}

header('Location: ../index.php');
exit;
