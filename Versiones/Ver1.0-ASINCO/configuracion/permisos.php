<?php
/**
 * permisos.php
 *
 * Punto central para exigir una sesión antes de mostrar vistas internas
 */

require_once __DIR__ . '/sesion.php';

/* Protege la vista actual contra accesos sin autenticación */
exigirSesion();
