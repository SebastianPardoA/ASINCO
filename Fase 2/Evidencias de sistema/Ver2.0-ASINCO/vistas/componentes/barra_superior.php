<?php
/**
 * barra_superior.php
 *
 * Muestra la barra superior reutilizable de las vistas internas del sistema ASINCO
 */

$tituloBarraSuperior = $tituloBarraSuperior ?? 'ASINCO';
$subtituloBarraSuperior = $subtituloBarraSuperior ?? 'Gestión interna del sistema.';
?>

<!-- Barra superior reutilizable con el título de la vista -->
<header class="barra-superior">
    <div class="barra-superior__titulo">
        <h1><?php echo htmlspecialchars($tituloBarraSuperior); ?></h1>
        <p><?php echo htmlspecialchars($subtituloBarraSuperior); ?></p>
    </div>
</header>
