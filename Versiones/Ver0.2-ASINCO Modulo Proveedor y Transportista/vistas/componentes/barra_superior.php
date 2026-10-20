<?php
/**
 * barra_superior.php
 *
 * Muestra la barra superior reutilizable de las vistas internas del sistema ASINCO
 */

$tituloBarraSuperior = $tituloBarraSuperior ?? 'ASINCO';
$subtituloBarraSuperior = $subtituloBarraSuperior ?? 'Gestión interna del sistema.';
?>

<!-- Barra superior reutilizable con título de vista y acciones generales -->
<header class="barra-superior">
    <div class="barra-superior__titulo">
        <h1><?php echo htmlspecialchars($tituloBarraSuperior); ?></h1>
        <p><?php echo htmlspecialchars($subtituloBarraSuperior); ?></p>
    </div>

    <!-- Acciones generales temporales de la barra superior -->
    <div class="barra-superior__acciones">
        <button class="barra-superior__boton barra-superior__boton--notificacion" type="button" aria-label="Ver notificaciones">
            <i class="bi bi-bell"></i>
            <span>2</span>
        </button>
        <button class="barra-superior__ayuda" type="button">
            <i class="bi bi-question-circle"></i>
            <span>Ayuda</span>
        </button>
    </div>
</header>
