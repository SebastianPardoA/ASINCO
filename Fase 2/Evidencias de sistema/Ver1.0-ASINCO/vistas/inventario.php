<?php
/**
 * inventario.php
 *
 * Muestra el inventario real con indicadores, filtros por radio y productos paginados
 */

$vistaActiva = 'inventario';
$tituloBarraSuperior = 'Inventario';
$subtituloBarraSuperior = 'Lista de todos los productos registrados en el sistema.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Inventario</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/inventario.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del inventario -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="inventario-contenido">
            <!-- Indicadores calculados según los productos filtrados -->
            <section class="inventario-indicadores" aria-label="Indicadores de inventario">
                <article class="inventario-indicador"><div class="inventario-indicador__icono inventario-indicador__icono--azul"><i class="bi bi-box-seam" aria-hidden="true"></i></div><div><span>Productos registrados</span><strong data-inventario-productos>0</strong><small>Resultados encontrados</small></div></article>
                <article class="inventario-indicador"><div class="inventario-indicador__icono inventario-indicador__icono--verde"><i class="bi bi-boxes" aria-hidden="true"></i></div><div><span>Stock total</span><strong data-inventario-stock-total>0</strong><small>Unidades registradas</small></div></article>
                <article class="inventario-indicador"><div class="inventario-indicador__icono inventario-indicador__icono--amarillo"><i class="bi bi-box-arrow-up" aria-hidden="true"></i></div><div><span>Stock asignado</span><strong data-inventario-stock-asignado>0</strong><small>Unidades asignadas o en uso</small></div></article>
                <article class="inventario-indicador"><div class="inventario-indicador__icono inventario-indicador__icono--morado"><i class="bi bi-check-square" aria-hidden="true"></i></div><div><span>Stock disponible</span><strong data-inventario-stock-disponible>0</strong><small>Unidades disponibles</small></div></article>
            </section>

            <!-- Filtros con el mismo selector por radio y buscador del historial -->
            <section class="inventario-filtros" aria-label="Filtros del inventario">
                <label class="inventario-filtro-busqueda"><span>Buscar producto</span><div class="inventario-control-busqueda"><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-inventario-busqueda placeholder="Buscar en inventario..." autocomplete="off"></div></label>

                <fieldset class="inventario-filtro-radio"><legend>Categoría</legend><div class="inventario-selector" data-inventario-selector="categoria"><button class="inventario-selector__boton" type="button" data-inventario-selector-boton="categoria" aria-expanded="false" aria-haspopup="listbox"><span class="inventario-selector__radio" aria-hidden="true"></span><span data-inventario-selector-valor="categoria">Todas las categorías</span><i class="bi bi-chevron-down" aria-hidden="true"></i></button><div class="inventario-selector__panel" data-inventario-selector-panel="categoria" hidden><input class="inventario-radio-busqueda" type="search" data-inventario-categoria-busqueda placeholder="Buscar categoría" autocomplete="off"><div class="inventario-radio-opciones" data-inventario-categoria-opciones role="listbox"></div></div></div></fieldset>

                <fieldset class="inventario-filtro-radio"><legend>Proveedor</legend><div class="inventario-selector" data-inventario-selector="proveedor"><button class="inventario-selector__boton" type="button" data-inventario-selector-boton="proveedor" aria-expanded="false" aria-haspopup="listbox"><span class="inventario-selector__radio" aria-hidden="true"></span><span data-inventario-selector-valor="proveedor">Todos los proveedores</span><i class="bi bi-chevron-down" aria-hidden="true"></i></button><div class="inventario-selector__panel" data-inventario-selector-panel="proveedor" hidden><input class="inventario-radio-busqueda" type="search" data-inventario-proveedor-busqueda placeholder="Buscar proveedor" autocomplete="off"><div class="inventario-radio-opciones" data-inventario-proveedor-opciones role="listbox"></div></div></div></fieldset>

                <fieldset class="inventario-filtro-radio"><legend>Estado</legend><div class="inventario-selector" data-inventario-selector="estado"><button class="inventario-selector__boton" type="button" data-inventario-selector-boton="estado" aria-expanded="false" aria-haspopup="listbox"><span class="inventario-selector__radio" aria-hidden="true"></span><span data-inventario-selector-valor="estado">Todos los estados</span><i class="bi bi-chevron-down" aria-hidden="true"></i></button><div class="inventario-selector__panel" data-inventario-selector-panel="estado" hidden><input class="inventario-radio-busqueda" type="search" data-inventario-estado-busqueda placeholder="Buscar estado" autocomplete="off"><div class="inventario-radio-opciones" data-inventario-estado-opciones role="listbox"></div></div></div></fieldset>

                <fieldset class="inventario-filtro-radio"><legend>Stock</legend><div class="inventario-selector" data-inventario-selector="stock"><button class="inventario-selector__boton" type="button" data-inventario-selector-boton="stock" aria-expanded="false" aria-haspopup="listbox"><span class="inventario-selector__radio" aria-hidden="true"></span><span data-inventario-selector-valor="stock">Todos</span><i class="bi bi-chevron-down" aria-hidden="true"></i></button><div class="inventario-selector__panel" data-inventario-selector-panel="stock" hidden><div class="inventario-radio-opciones" data-inventario-stock-opciones role="listbox"></div></div></div></fieldset>

                <button class="inventario-limpiar" type="button" data-inventario-limpiar><i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span>Limpiar filtros</span></button>
            </section>

            <!-- Tabla de productos obtenidos desde la API -->
            <section class="inventario-panel">
                <div class="inventario-tabla"><table><thead><tr><th>Imagen</th><th>Nombre</th><th>Código</th><th>Categoría</th><th>Stock total</th><th>Stock asignado</th><th>Stock disponible</th><th>Acciones</th></tr></thead><tbody data-inventario-lista><tr><td colspan="8">Cargando inventario...</td></tr></tbody></table></div>

                <!-- Paginación -->
                <div class="inventario-paginacion"><p data-inventario-contador>Cargando inventario...</p><div class="inventario-paginas" data-inventario-paginacion aria-label="Paginación del inventario"></div><div class="inventario-tamano-pagina"><select aria-label="Productos por página" data-inventario-pagina-tamano><option value="5">5 por página</option><option value="7" selected>7 por página</option><option value="10">10 por página</option><option value="15">15 por página</option><option value="personalizada">Personalizada</option></select><input type="number" min="1" max="50" value="20" aria-label="Cantidad personalizada por página" data-inventario-pagina-personalizada hidden></div></div>
            </section>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>

    <script src="../recursos/js/inventario.js?v=20261007-1" defer></script>
</body>
</html>
