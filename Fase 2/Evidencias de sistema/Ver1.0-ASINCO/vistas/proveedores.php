<?php
/**
 * proveedores.php
 *
 * Muestra proveedores, compras, artículos, montos y evaluaciones
 */

$vistaActiva = 'proveedores';
$tituloBarraSuperior = 'Proveedores';
$subtituloBarraSuperior = 'Consulta el desempeño de los proveedores registrados.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Proveedores</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/proveedores.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="proveedores-contenido">
            <!-- Buscador de proveedores -->
            <section class="proveedores-panel proveedores-panel--filtros">
                <div class="proveedores-panel__encabezado">
                    <h2>Proveedores registrados</h2>
                    <label class="proveedores-busqueda"><i class="bi bi-search"></i><span class="texto-oculto">Buscar proveedores</span><input type="search" placeholder="Buscar proveedor..." data-proveedores-busqueda></label>
                </div>
            </section>

            <!-- Indicadores calculados desde proveedores, compras y evaluaciones -->
            <section class="proveedores-indicadores" aria-label="Indicadores de proveedores">
                <article class="proveedores-indicador"><div class="proveedores-indicador__icono proveedores-indicador__icono--azul"><i class="bi bi-person-badge"></i></div><div class="proveedores-indicador__datos"><span>Proveedores registrados</span><strong data-proveedores-total>0</strong><small>Desde la base de datos</small></div></article>
                <article class="proveedores-indicador"><div class="proveedores-indicador__icono proveedores-indicador__icono--verde"><i class="bi bi-cart"></i></div><div class="proveedores-indicador__datos"><span>Compras asociadas</span><strong data-proveedores-compras>0</strong><small>Compras registradas</small></div></article>
                <article class="proveedores-indicador"><div class="proveedores-indicador__icono proveedores-indicador__icono--verde-claro"><i class="bi bi-box-seam"></i></div><div class="proveedores-indicador__datos"><span>Artículos adquiridos</span><strong data-proveedores-articulos>0</strong><small>Unidades en compras</small></div></article>
                <article class="proveedores-indicador"><div class="proveedores-indicador__icono proveedores-indicador__icono--amarillo"><i class="bi bi-cash-stack"></i></div><div class="proveedores-indicador__datos"><span>Montos comprados</span><strong data-proveedores-montos>Sin datos</strong><small>Por moneda original</small></div></article>
                <article class="proveedores-indicador"><div class="proveedores-indicador__icono proveedores-indicador__icono--morado"><i class="bi bi-star"></i></div><div class="proveedores-indicador__datos"><span>Calificación promedio</span><strong data-proveedores-calificacion>Sin datos</strong><small>Evaluaciones registradas</small></div></article>
            </section>

            <!-- Tabla de proveedores -->
            <section class="proveedores-panel">
                <div class="proveedores-panel__encabezado"><h2>Todos los proveedores</h2></div>
                <div class="proveedores-tabla"><table>
                    <thead><tr><th>Imagen</th><th>Proveedor</th><th>Compras</th><th>Monto total</th><th>Artículos</th><th>Última compra</th><th>Calificación</th><th>Acciones</th></tr></thead>
                    <tbody data-proveedores-lista><tr><td colspan="8">Cargando proveedores...</td></tr></tbody>
                </table></div>
                <div class="proveedores-paginacion"><p data-proveedores-contador>Cargando proveedores...</p><div class="proveedores-paginas" data-proveedores-paginacion></div></div>
            </section>

            <!-- Rankings calculados con las evaluaciones reales -->
            <section class="proveedores-rankings" aria-label="Ranking de proveedores">
                <article class="proveedores-ranking proveedores-ranking--mejores">
                    <div class="proveedores-ranking__titulo"><span><i class="bi bi-trophy"></i></span><div><h2>Mejores proveedores</h2><small>Según calificación registrada</small></div></div>
                    <div class="proveedores-ranking__tabla">
                        <div class="proveedores-ranking__fila proveedores-ranking__cabecera"><span>Proveedor</span><span>Compras</span><span>Monto total</span><span>Calificación</span></div>
                        <div data-proveedores-ranking-mejores><p class="ranking-vacio">Cargando ranking...</p></div>
                    </div>
                </article>
                <article class="proveedores-ranking proveedores-ranking--peores">
                    <div class="proveedores-ranking__titulo"><span><i class="bi bi-trophy"></i></span><div><h2>Peores proveedores</h2><small>Según calificación registrada</small></div></div>
                    <div class="proveedores-ranking__tabla">
                        <div class="proveedores-ranking__fila proveedores-ranking__cabecera"><span>Proveedor</span><span>Compras</span><span>Monto total</span><span>Calificación</span></div>
                        <div data-proveedores-ranking-peores><p class="ranking-vacio">Cargando ranking...</p></div>
                    </div>
                </article>
            </section>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
    <?php include 'componentes/modal_detalle_entidad.php'; ?>
    <script src="../recursos/js/proveedores.js?v=20261007-1" defer></script>
</body>
</html>
