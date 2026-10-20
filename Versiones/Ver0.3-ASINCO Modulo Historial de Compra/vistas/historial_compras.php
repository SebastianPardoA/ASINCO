<?php
/**
 * historial_compras.php
 *
 * Muestra el historial de compras
 */

$vistaActiva = 'historial_compras';
$tituloBarraSuperior = 'Compras e historial';
$subtituloBarraSuperior = 'Consulta y seguimiento del historial de compras realizadas.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Historial de compras</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/historial_compras.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del historial conectado a la base de datos -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="historial-contenido">
            <!-- Indicadores calculados con las compras filtradas -->
            <section class="historial-indicadores" aria-label="Indicadores del historial de compras">
                <article class="historial-indicador">
                    <div class="historial-indicador__icono historial-indicador__icono--azul"><i class="bi bi-cart" aria-hidden="true"></i></div>
                    <div><span>Compras registradas</span><strong data-historial-compras>0</strong><small>Resultados encontrados</small></div>
                </article>
                <article class="historial-indicador">
                    <div class="historial-indicador__icono historial-indicador__icono--verde"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <div><span>Productos adquiridos</span><strong data-historial-articulos>0</strong><small>Unidades incluidas</small></div>
                </article>
                <article class="historial-indicador">
                    <div class="historial-indicador__icono historial-indicador__icono--morado"><i class="bi bi-bag-check" aria-hidden="true"></i></div>
                    <div><span>Compras recibidas</span><strong data-historial-recibidas>0</strong><small>Estado recibido</small></div>
                </article>
                <article class="historial-indicador">
                    <div class="historial-indicador__icono historial-indicador__icono--amarillo"><i class="bi bi-currency-dollar" aria-hidden="true"></i></div>
                    <div><span>Total gastado</span><strong data-historial-montos>Sin datos</strong><small>Montos expresados en CLP</small></div>
                </article>
            </section>

            <!-- Filtros, identificador, fecha y radios con búsqueda -->
            <section class="historial-filtros" aria-label="Filtros del historial">
                <label class="historial-filtro-busqueda">
                    <span>Buscar compra por ID</span>
                    <div class="historial-control-busqueda">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-historial-busqueda placeholder="Ej: OC-2026-0001 o Benjamin Jeria" autocomplete="off">
                    </div>
                </label>

                <label class="historial-filtro-fecha">
                    <span>Fecha</span>
                    <div class="historial-control-fecha">
                        <input type="date" data-historial-fecha aria-label="Filtrar por fecha de compra">
                    </div>
                </label>

                <fieldset class="historial-filtro-radio">
                    <legend>Proveedor</legend>
                    <div class="historial-selector" data-historial-selector="proveedor">
                        <button class="historial-selector__boton" type="button" data-historial-selector-boton="proveedor" aria-expanded="false" aria-haspopup="listbox">
                            <span class="historial-selector__radio" aria-hidden="true"></span>
                            <span data-historial-selector-valor="proveedor">Todos los proveedores</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="historial-selector__panel" data-historial-selector-panel="proveedor" hidden>
                            <input class="historial-radio-busqueda" type="search" data-historial-proveedor-busqueda placeholder="Buscar proveedor" autocomplete="off">
                            <div class="historial-radio-opciones" data-historial-proveedor-opciones role="listbox"></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="historial-filtro-radio">
                    <legend>Transportista</legend>
                    <div class="historial-selector" data-historial-selector="transportista">
                        <button class="historial-selector__boton" type="button" data-historial-selector-boton="transportista" aria-expanded="false" aria-haspopup="listbox">
                            <span class="historial-selector__radio" aria-hidden="true"></span>
                            <span data-historial-selector-valor="transportista">Todos los transportistas</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="historial-selector__panel" data-historial-selector-panel="transportista" hidden>
                            <input class="historial-radio-busqueda" type="search" data-historial-transportista-busqueda placeholder="Buscar transportista" autocomplete="off">
                            <div class="historial-radio-opciones" data-historial-transportista-opciones role="listbox"></div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="historial-filtro-radio">
                    <legend>Estado</legend>
                    <div class="historial-selector" data-historial-selector="estado">
                        <button class="historial-selector__boton" type="button" data-historial-selector-boton="estado" aria-expanded="false" aria-haspopup="listbox">
                            <span class="historial-selector__radio" aria-hidden="true"></span>
                            <span data-historial-selector-valor="estado">Todos los estados</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="historial-selector__panel" data-historial-selector-panel="estado" hidden>
                            <div class="historial-radio-opciones historial-radio-opciones--estado" data-historial-estado-opciones role="listbox"></div>
                        </div>
                    </div>
                </fieldset>

                <button class="historial-limpiar" type="button" data-historial-limpiar>
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span>Limpiar filtros</span>
                </button>
            </section>

            <!-- Tabla de compras obtenidas desde la API -->
            <section class="historial-panel">
                <div class="historial-panel__encabezado">
                    <h2>Historial de compras</h2>
                </div>

                <div class="historial-tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>ID compra</th><th>Fecha <i class="bi bi-chevron-down" aria-hidden="true"></i></th><th>Proveedor</th><th>Transportista</th>
                                <th>Productos</th><th>Total</th><th>Estado</th><th>Responsable</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody data-historial-lista>
                            <tr><td colspan="9">Cargando compras...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación generada según las compras -->
                <div class="historial-paginacion">
                    <p data-historial-contador>Cargando compras...</p>
                    <div class="historial-paginas" data-historial-paginacion aria-label="Paginación del historial"></div>
                    <div class="historial-tamano-pagina">
                        <select aria-label="Compras por página" data-historial-pagina-tamano>
                            <option value="5">5 por página</option>
                            <option value="7" selected>7 por página</option>
                            <option value="10">10 por página</option>
                            <option value="15">15 por página</option>
                            <option value="personalizada">Personalizada</option>
                        </select>
                        <input type="number" min="1" max="50" value="20" aria-label="Cantidad personalizada por página" data-historial-pagina-personalizada hidden>
                    </div>
                </div>
            </section>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>

    <script src="../recursos/js/historial_compras.js" defer></script>
</body>
</html>
