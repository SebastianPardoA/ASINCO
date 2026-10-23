<?php
/**
 * metodos_despacho.php
 *
 * Muestra las empresas transportistas registradas y sus compras asociadas
 */

$vistaActiva = 'metodos_despacho';
$tituloBarraSuperior = 'Transportistas';
$subtituloBarraSuperior = 'Consulta las empresas que transportan las compras registradas.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Transportistas</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/metodo_despacho.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="despachos-contenido">
            <!-- Buscador de transportistas -->
            <section class="despachos-panel despachos-panel--filtros">
                <div class="despachos-panel__encabezado">
                    <h2>Transportistas registrados</h2>
                    <label class="despachos-busqueda"><i class="bi bi-search"></i><span class="texto-oculto">Buscar transportistas</span><input type="search" placeholder="Buscar transportista..." data-transportistas-busqueda></label>
                </div>
            </section>

            <!-- Indicadores calculados desde transportistas, compras y evaluaciones -->
            <section class="despachos-indicadores" aria-label="Indicadores de transportistas">
                <article class="despachos-indicador"><div class="despachos-indicador__icono despachos-indicador__icono--morado"><i class="bi bi-truck"></i></div><div><span>Transportistas registrados</span><strong data-transportistas-total>0</strong><small>Desde la base de datos</small></div></article>
                <article class="despachos-indicador"><div class="despachos-indicador__icono despachos-indicador__icono--verde"><i class="bi bi-box-seam"></i></div><div><span>Compras asociadas</span><strong data-transportistas-compras>0</strong><small>Con transportista asignado</small></div></article>
                <article class="despachos-indicador"><div class="despachos-indicador__icono despachos-indicador__icono--amarillo"><i class="bi bi-coin"></i></div><div><span>Costos de envíos</span><strong data-transportistas-costos>Sin datos</strong><small>Por moneda registrada</small></div></article>
                <article class="despachos-indicador"><div class="despachos-indicador__icono despachos-indicador__icono--verde"><i class="bi bi-star"></i></div><div><span>Calificación promedio</span><strong data-transportistas-calificacion>Sin datos</strong><small>Evaluaciones registradas</small></div></article>
            </section>

            <!-- Tabla de empresas transportistas -->
            <section class="despachos-panel">
                <div class="despachos-panel__encabezado"><h2>Todos los transportistas</h2></div>
                <div class="despachos-tabla"><table>
                    <thead><tr><th>Imagen</th><th>Transportista</th><th>Compras</th><th>Costo de envíos</th><th>Última compra</th><th>Estado reciente</th><th>Calificación</th><th>Acciones</th></tr></thead>
                    <tbody data-transportistas-lista><tr><td colspan="8">Cargando transportistas...</td></tr></tbody>
                </table></div>
                <div class="despachos-paginacion"><p data-transportistas-contador>Cargando transportistas...</p><div class="despachos-paginas" data-transportistas-paginacion></div></div>
            </section>

            <!-- Rankings calculados con las evaluaciones -->
            <section class="despachos-rankings" aria-label="Ranking de transportistas">
                <article class="despachos-ranking despachos-ranking--mejores">
                    <div class="despachos-ranking__titulo"><span><i class="bi bi-trophy"></i></span><div><h2>Mejores transportistas</h2><small>Según calificación registrada</small></div></div>
                    <ol data-transportistas-ranking-mejores><li class="ranking-vacio">Cargando ranking...</li></ol>
                </article>
                <article class="despachos-ranking despachos-ranking--peores">
                    <div class="despachos-ranking__titulo"><span><i class="bi bi-trophy"></i></span><div><h2>Peores transportistas</h2><small>Según calificación registrada</small></div></div>
                    <ol data-transportistas-ranking-peores><li class="ranking-vacio">Cargando ranking...</li></ol>
                </article>
            </section>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
    <?php include 'componentes/modal_detalle_entidad.php'; ?>
    <script src="../recursos/js/metodo_despacho.js?v=20261007-1" defer></script>
</body>
</html>
