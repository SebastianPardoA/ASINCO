<?php
/**
 * detalle_compra.php
 *
 * Presenta el detalle de una compra seleccionada desde el historial
 */

$vistaActiva = 'historial_compras';
$tituloBarraSuperior = 'Detalle de compra';
$subtituloBarraSuperior = 'Información general, recepción y documentos asociados a la compra.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Detalle de compra</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/detalle_compra.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del detalle de la compra -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="detalle-compra" data-detalle-compra>
            <!-- Identificación de la compra y regreso al historial. -->
            <div class="detalle-compra__ruta">
                <a href="historial_compras.php">Compras e historial</a>
                <span>/</span>
                <span>Detalle de compra</span>
            </div>
            <div class="detalle-compra__encabezado">
                <div>
                    <div class="detalle-compra__titulo">
                        <h2 data-detalle-compra-titulo>Cargando compra...</h2>
                        <span class="etiqueta" data-detalle-compra-estado>Cargando</span>
                    </div>
                    <p data-detalle-compra-subtitulo>Cargando información de la compra...</p>
                </div>
                <a class="detalle-compra__volver" href="historial_compras.php">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    <span>Volver al historial</span>
                </a>
            </div>

            <!-- Resumen general cargado desde la compra seleccionada -->
            <section class="detalle-resumen" aria-label="Resumen de la compra" data-detalle-resumen>
                <div class="detalle-resumen__columna"></div>
                <div class="detalle-resumen__columna"></div>
                <div class="detalle-resumen__columna"></div>
                <div class="detalle-resumen__observaciones"></div>
            </section>

            <!-- Contenido inferior con artículos, comentarios y resultados -->
            <div class="detalle-compra__grid">
                <div class="detalle-compra__izquierda">
                    <section class="detalle-panel detalle-articulos">
                        <div class="detalle-panel__titulo"><h3>Artículos de la compra</h3></div>
                        <div class="detalle-articulos__tabla">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Productos</th>
                                        <th>SKU / Código</th>
                                        <th>Cantidad</th>
                                        <th data-detalle-columna-precio>Precio unitario</th>
                                        <th data-detalle-columna-subtotal>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody data-detalle-articulos>
                                    <tr><td colspan="6">Cargando artículos...</td></tr>
                                </tbody>
                                <tfoot data-detalle-articulos-total></tfoot>
                            </table>
                        </div>
                    </section>

                    <section class="detalle-panel detalle-comentarios">
                        <div class="detalle-panel__titulo"><h3>Comentarios de la compra</h3></div>
                        <div data-detalle-comentarios>
                            <p class="detalle-vacio">Cargando comentarios...</p>
                        </div>
                        <form class="detalle-comentarios__nuevo" data-formulario-comentario>
                            <input type="hidden" name="id_comentario" data-comentario-id>
                            <input type="text" name="comentario" maxlength="1000" placeholder="Escribe un comentario..." aria-label="Nuevo comentario" data-comentario-texto required>
                            <button type="button" class="detalle-comentarios__cancelar" data-comentario-cancelar hidden>Cancelar</button>
                            <button type="submit" data-comentario-guardar>Publicar</button>
                        </form>
                        <p class="detalle-comentarios__mensaje" data-comentario-mensaje aria-live="polite"></p>
                    </section>
                </div>

                <div class="detalle-compra__derecha">
                    <section class="detalle-panel detalle-recepcion-resultados">
                        <div class="detalle-panel__titulo"><h3>Recepción y resultados</h3></div>
                        <div data-detalle-recepcion>
                            <p class="detalle-vacio">Cargando recepción...</p>
                        </div>
                        <div class="detalle-calificaciones" data-detalle-calificaciones>
                            <p class="detalle-vacio">Cargando calificaciones...</p>
                        </div>
                    </section>

                    <section class="detalle-panel detalle-documentos">
                        <div class="detalle-panel__titulo"><h3>Documentos de la compra</h3></div>
                        <form class="detalle-documentos__carga" data-documento-form enctype="multipart/form-data">
                            <div class="detalle-documentos__campos">
                                <fieldset class="detalle-documentos__tipo">
                                    <legend>Tipo de documento</legend>
                                    <div class="detalle-documento-selector" data-documento-selector>
                                        <button class="detalle-documento-selector__boton" type="button" data-documento-selector-boton aria-expanded="false" aria-haspopup="listbox">
                                            <span class="detalle-documento-selector__radio" aria-hidden="true"></span>
                                            <span data-documento-tipo-label>Seleccionar tipo</span>
                                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                        </button>
                                        <div class="detalle-documento-selector__panel" data-documento-selector-panel hidden>
                                            <input class="detalle-documento-radio-busqueda" type="search" data-documento-tipo-busqueda placeholder="Buscar tipo de documento" autocomplete="off">
                                            <div class="detalle-documento-radio-opciones" data-documento-tipo-opciones role="listbox"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="id_tipo_documento" data-documento-tipo-valor required>
                                </fieldset>
                                <label class="detalle-documentos__archivo">Archivo
                                    <input type="file" name="documento" accept="application/pdf,.doc,.docx,image/jpeg,image/png,image/webp,image/gif" data-documento-archivo required>
                                </label>
                                <button type="submit" class="detalle-documentos__subir"><i class="bi bi-upload" aria-hidden="true"></i>Subir documento</button>
                            </div>
                            <p class="detalle-documentos__ayuda">PDF, Word o imagen. Tamaño máximo: 10 MB.</p>
                            <p class="detalle-documentos__mensaje" data-documento-mensaje></p>
                        </form>
                        <div class="detalle-documentos__lista" data-detalle-documentos>
                            <p class="detalle-vacio">Cargando documentos...</p>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>

    <script src="../recursos/js/detalle_compra.js?v=20261007-1" defer></script>
</body>
</html>
