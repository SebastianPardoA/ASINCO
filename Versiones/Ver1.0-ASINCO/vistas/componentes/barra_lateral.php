<?php
/**
 * barra_lateral.php
 *
 * Muestra el menú lateral principal del sistema ASINCO
 */

require_once __DIR__ . '/../../configuracion/permisos.php';

$vistaActiva = $vistaActiva ?? '';
$usuarioSesion = obtenerUsuarioSesion();
$nombreUsuarioSesion = trim(($usuarioSesion['nombre'] ?? '') . ' ' . ($usuarioSesion['apellido'] ?? ''));
$rolUsuarioSesion = $usuarioSesion['rol_nombre'] ?? 'Sin asignar';
$inicialesUsuarioSesion = strtoupper(substr((string) ($usuarioSesion['nombre'] ?? ''), 0, 1) . substr((string) ($usuarioSesion['apellido'] ?? ''), 0, 1));

/* Indica si la opción recibida corresponde a la vista activa */
function esVistaActiva($vistaActual, $vistaComparar)
{
    return $vistaActual === $vistaComparar ? 'barra-lateral__enlace--activo' : '';
}
?>

<!-- Aplica el estado guardado del menú antes de dibujarlo para evitar saltos al cambiar de vista -->
<script>
    if (localStorage.getItem('asinco_menu_lateral_contraido') === 'true') {
        document.body.classList.add('menu-lateral-contraido');
    }
</script>

<!-- Menú lateral reutilizable del sistema -->
<aside class="barra-lateral" id="barraLateral">
    <div class="barra-lateral__marca">
        <div class="barra-lateral__logo" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="barra-lateral__texto">
            <strong>ASINCO</strong>
            <small>Inventario y compras</small>
        </div>
        <button class="barra-lateral__alternar" type="button" aria-label="Contraer menú lateral" aria-expanded="true" data-menu-lateral-boton></button>
    </div>

    <!-- Navegación principal entre vistas del sistema -->
    <nav class="barra-lateral__nav" aria-label="Navegación principal">
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'dashboard'); ?>" href="dashboard.php" title="Dashboard">
            <span class="barra-lateral__icono"><i class="bi bi-house-door"></i></span>
            <span class="barra-lateral__texto">Dashboard</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'inventario'); ?>" href="inventario.php" title="Inventario">
            <span class="barra-lateral__icono"><i class="bi bi-box-seam"></i></span>
            <span class="barra-lateral__texto">Inventario</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'registro_compra'); ?>" href="registro_compra.php" title="Registro de compra">
            <span class="barra-lateral__icono"><i class="bi bi-cart-plus"></i></span>
            <span class="barra-lateral__texto">Registro de compra</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'historial_compras'); ?>" href="historial_compras.php" title="Historial de compras">
            <span class="barra-lateral__icono"><i class="bi bi-clock-history"></i></span>
            <span class="barra-lateral__texto">Historial de compras</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'proveedores'); ?>" href="proveedores.php" title="Proveedores">
            <span class="barra-lateral__icono"><i class="bi bi-people"></i></span>
            <span class="barra-lateral__texto">Proveedores</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'metodos_despacho'); ?>" href="metodos_despacho.php" title="Transportistas">
            <span class="barra-lateral__icono"><i class="bi bi-truck"></i></span>
            <span class="barra-lateral__texto">Transportistas</span>
        </a>
        <a class="barra-lateral__enlace <?php echo esVistaActiva($vistaActiva, 'administracion'); ?>" href="administracion.php" title="Administración">
            <span class="barra-lateral__icono"><i class="bi bi-gear"></i></span>
            <span class="barra-lateral__texto">Administración</span>
        </a>
    </nav>

    <!-- Resumen del usuario autenticado y acceso al cierre de sesión -->
    <div class="barra-lateral__usuario">
        <div class="barra-lateral__avatar"><?php echo htmlspecialchars($inicialesUsuarioSesion, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="barra-lateral__texto">
            <strong><?php echo htmlspecialchars($nombreUsuarioSesion, ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($rolUsuarioSesion, ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
        <button class="barra-lateral__salir barra-lateral__texto" type="button" aria-label="Cerrar sesión" data-cerrar-sesion-boton>&#8964;</button>
    </div>
</aside>

<!-- Modal de confirmación antes de destruir la sesión actual -->
<div class="modal-sistema" data-cerrar-sesion-modal hidden>
    <div class="modal-sistema__contenido" role="dialog" aria-modal="true" aria-labelledby="cerrarSesionTitulo">
        <div class="modal-sistema__encabezado">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <h2 id="cerrarSesionTitulo">Cerrar sesión</h2>
        </div>
        <p>¿Estás seguro de que deseas cerrar la sesión?</p>
        <div class="modal-sistema__acciones">
            <button type="button" class="modal-sistema__boton modal-sistema__boton--secundario" data-cerrar-sesion-cancelar>Cancelar</button>
            <form action="../configuracion/cerrar_sesion.php" method="post">
                <button type="submit" class="modal-sistema__boton modal-sistema__boton--principal">Sí, cerrar sesión</button>
            </form>
        </div>
    </div>
</div>

<script src="../recursos/js/global.js?v=20261007-1" defer></script>
