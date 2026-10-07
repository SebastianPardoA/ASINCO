<?php
/**
 * detalle_producto.php
 *
 * Muestra la ficha real de un producto, sus imágenes, existencias y unidades
 */

$vistaActiva = 'inventario';
$tituloBarraSuperior = 'Detalle del producto';
$subtituloBarraSuperior = 'Información general, stock y unidades asociadas al producto.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Detalle del producto</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/detalle_producto.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido del detalle del producto conectado a la base de datos -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="detalle-producto">
            <!-- Ruta de navegación y acción para regresar al inventario -->
            <div class="detalle-producto__superior">
                <div class="detalle-producto__ruta"><i class="bi bi-box-seam" aria-hidden="true"></i><span>Inventario / Detalle del producto</span></div>
                <a class="detalle-producto__volver" href="inventario.php"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Volver al Inventario</span></a>
            </div>

            <!-- Ficha general, galería, existencias y calificaciones relacionadas -->
            <section class="detalle-producto__grid">
                <article class="detalle-producto__galeria">
                    <div class="detalle-producto__imagen-principal"><button type="button" class="detalle-producto__carrusel-boton detalle-producto__carrusel-boton--anterior" data-producto-carrusel-anterior aria-label="Imagen anterior" hidden><i class="bi bi-chevron-left" aria-hidden="true"></i></button><img data-producto-imagen-principal src="../recursos/img/sin_imagen_producto.jpg" alt="Imagen del producto"><button type="button" class="detalle-producto__carrusel-boton detalle-producto__carrusel-boton--siguiente" data-producto-carrusel-siguiente aria-label="Imagen siguiente" hidden><i class="bi bi-chevron-right" aria-hidden="true"></i></button><label class="detalle-producto__principal" data-producto-principal hidden><input type="checkbox" data-producto-marcar-principal><span>Principal</span></label></div>
                    <div class="detalle-producto__miniaturas-fila"><div class="detalle-producto__miniaturas" data-producto-miniaturas></div><button type="button" class="detalle-producto__agregar-imagen" data-producto-agregar-imagen aria-label="Agregar imagen" title="Agregar imagen"><i class="bi bi-plus-lg" aria-hidden="true"></i></button></div>
                </article>

                <article class="detalle-producto__info">
                    <span class="detalle-producto__estado" data-producto-estado> Cargando... </span>
                    <h2 data-producto-nombre>Cargando producto...</h2>

                    <dl class="detalle-producto__datos">
                        <div><dt>Código del producto</dt><dd data-producto-codigo>--</dd></div>
                        <div><dt>Categoría</dt><dd data-producto-categoria>--</dd></div>
                    </dl>

                    <div class="detalle-producto__descripcion"><i class="bi bi-card-text" aria-hidden="true"></i><div><h3>Descripción</h3><p data-producto-descripcion>Sin descripción registrada.</p></div></div>

                    <div class="detalle-producto__stocks">
                        <div class="detalle-stock detalle-stock--azul"><i class="bi bi-box-seam" aria-hidden="true"></i><div><span>Stock total</span><strong data-producto-stock-total>0</strong><small data-producto-unidad-medida>Unidades</small></div></div>
                        <div class="detalle-stock detalle-stock--morado"><i class="bi bi-person" aria-hidden="true"></i><div><span>Stock asignado</span><strong data-producto-stock-asignado>0</strong><small data-producto-unidad-medida-asignado>Unidades</small></div></div>
                        <div class="detalle-stock detalle-stock--verde"><i class="bi bi-check-circle" aria-hidden="true"></i><div><span>Stock disponible</span><strong data-producto-stock-disponible>0</strong><small data-producto-unidad-medida-disponible>Unidades</small></div></div>
                    </div>
                </article>

                <aside class="detalle-producto__calificaciones" data-producto-calificaciones>
                    <article class="detalle-calificacion detalle-calificacion--vacia"><h3>Calificación del mejor proveedor</h3><div class="detalle-calificacion__vacio"><span class="detalle-calificacion__vacio-icon"><i class="bi bi-star" aria-hidden="true"></i></span><div><strong>Sin calificaciones registradas</strong><small>Aún no hay evaluaciones disponibles.</small></div></div></article>
                    <article class="detalle-calificacion detalle-calificacion--vacia"><h3>Calificación del mejor transportista</h3><div class="detalle-calificacion__vacio"><span class="detalle-calificacion__vacio-icon"><i class="bi bi-star" aria-hidden="true"></i></span><div><strong>Sin calificaciones registradas</strong><small>Aún no hay evaluaciones disponibles.</small></div></div></article>
                </aside>
            </section>

            <!-- Unidades asociadas al producto y su compra de origen -->
            <section class="detalle-unidades">
                <div class="detalle-unidades__titulo"><h2>Unidades del producto</h2><p>Cada unidad puede rastrearse hasta su compra</p></div>
                <!-- Filtros para consultar las unidades del producto -->
                <div class="detalle-unidades__filtros" aria-label="Filtros de unidades">
                    <label class="detalle-unidades__filtro-busqueda"><span>Buscar unidad o compra</span><div><i class="bi bi-search" aria-hidden="true"></i><input type="search" data-producto-filtro-busqueda placeholder="Ej: EXTRA-031-01 u OC-2026-0101" autocomplete="off"></div></label>
                    <label><span>Usuario asignado</span><select data-producto-filtro-usuario><option value="">Todos los usuarios</option></select></label>
                    <label><span>Fecha de compra</span><input type="date" data-producto-filtro-fecha></label>
                    <label><span>Proveedor</span><select data-producto-filtro-proveedor><option value="">Todos los proveedores</option></select></label>
                    <label><span>Transportista</span><select data-producto-filtro-transportista><option value="">Todos los transportistas</option></select></label>
                    <button type="button" class="detalle-unidades__limpiar" data-producto-limpiar-filtros><i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span>Limpiar filtros</span></button>
                </div>
                <div class="detalle-unidades__tabla"><table><thead><tr><th>Unidad</th><th>Estado</th><th>Asignado a</th><th>Compra</th><th>Fecha de compra</th><th>Precio pagado</th><th>Proveedor</th><th>Transportista</th></tr></thead><tbody data-producto-unidades><tr><td colspan="8">Cargando unidades...</td></tr></tbody></table></div>
                <div class="detalle-unidades__paginacion"><p data-producto-contador>Cargando unidades...</p><div class="detalle-paginas" data-producto-paginacion></div><select aria-label="Unidades por página" data-producto-pagina-tamano><option value="9" selected>9 por página</option><option value="15">15 por página</option><option value="25">25 por página</option></select></div>
            </section>
        </section>

        <!-- Modal para cargar y previsualizar una nueva imagen -->
        <div class="detalle-producto-modal" data-producto-modal-agregar hidden>
            <div class="detalle-producto-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-imagen">
                <div class="detalle-producto-modal__encabezado"><h2 id="titulo-modal-imagen">Agregar imagen</h2><button type="button" data-producto-cerrar-modal-agregar aria-label="Cerrar"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <form data-producto-form-imagen>
                    <label class="detalle-producto-modal__etiqueta" for="productoImagenNueva">Selecciona una imagen</label>
                    <input id="productoImagenNueva" name="imagen" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <small>Formatos permitidos: JPG, PNG, WEBP o GIF. Máximo 5 MB.</small>
                    <div class="detalle-producto-modal__preview"><img data-producto-preview-imagen alt="Vista previa de la imagen" hidden><span data-producto-preview-vacio>La vista previa aparecerá aquí.</span></div>
                    <p class="detalle-producto-modal__mensaje" data-producto-mensaje-imagen></p>
                    <div class="detalle-producto-modal__acciones"><button type="button" class="detalle-producto-modal__cancelar" data-producto-cerrar-modal-agregar>Cancelar</button><button type="submit" class="detalle-producto-modal__guardar"><i class="bi bi-upload" aria-hidden="true"></i><span>Guardar imagen</span></button></div>
                </form>
            </div>
        </div>

        <!-- Modal de confirmación para eliminar una imagen -->
        <div class="detalle-producto-modal" data-producto-modal-eliminar hidden>
            <div class="detalle-producto-modal__contenido detalle-producto-modal__contenido--confirmacion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-eliminar-imagen">
                <div class="detalle-producto-modal__encabezado"><h2 id="titulo-modal-eliminar-imagen">Eliminar imagen</h2><button type="button" data-producto-cerrar-modal-eliminar aria-label="Cerrar"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <p>¿Estás seguro de que quieres eliminar esta imagen?</p>
                <p class="detalle-producto-modal__mensaje" data-producto-mensaje-eliminar></p>
                <div class="detalle-producto-modal__acciones"><button type="button" class="detalle-producto-modal__cancelar" data-producto-cerrar-modal-eliminar>Cancelar</button><button type="button" class="detalle-producto-modal__eliminar" data-producto-confirmar-eliminar><i class="bi bi-trash" aria-hidden="true"></i><span>Sí, eliminar</span></button></div>
            </div>
        </div>

        <!-- Modal para asignar una unidad a un usuario activo -->
        <div class="detalle-producto-modal" data-producto-modal-asignacion hidden>
            <div class="detalle-producto-modal__contenido detalle-producto-modal__contenido--asignacion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-asignacion">
                <div class="detalle-producto-modal__encabezado"><h2 id="titulo-modal-asignacion">Asignar unidad</h2><button type="button" data-producto-cerrar-modal-asignacion aria-label="Cerrar"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <p><span class="detalle-asignacion__unidad"><i class="bi bi-person" aria-hidden="true"></i><span data-producto-unidad-asignacion>Selecciona el usuario que recibirá esta unidad.</span></span></p>
                <form data-producto-form-asignacion>
                    <label class="detalle-asignacion__busqueda" for="buscarUsuarioAsignacion">Buscar usuario</label>
                    <div class="detalle-asignacion__busqueda-control"><i class="bi bi-search" aria-hidden="true"></i><input id="buscarUsuarioAsignacion" type="search" placeholder="Buscar por nombre o apellido" data-producto-buscar-asignacion></div>
                    <div class="detalle-asignacion__lista" data-producto-usuarios-asignacion><p>Cargando usuarios disponibles...</p></div>
                    <p class="detalle-producto-modal__mensaje" data-producto-mensaje-asignacion></p>
                    <div class="detalle-producto-modal__acciones"><button type="button" class="detalle-producto-modal__cancelar" data-producto-cerrar-modal-asignacion>Cancelar</button><button type="submit" class="detalle-producto-modal__guardar"><i class="bi bi-check-lg" aria-hidden="true"></i><span>Guardar</span></button></div>
                </form>
            </div>
        </div>

        <!-- Modal para cambiar el estado de una unidad -->
        <div class="detalle-producto-modal" data-producto-modal-estado-unidad hidden>
            <div class="detalle-producto-modal__contenido detalle-producto-modal__contenido--asignacion" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-estado-unidad">
                <div class="detalle-producto-modal__encabezado"><h2 id="titulo-modal-estado-unidad">Cambiar estado</h2><button type="button" data-producto-cerrar-modal-estado aria-label="Cerrar"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                <p><span class="detalle-asignacion__unidad"><i class="bi bi-box-seam" aria-hidden="true"></i><span data-producto-unidad-estado>Selecciona el estado para esta unidad.</span></span></p>
                <form data-producto-form-estado>
                    <label class="detalle-asignacion__busqueda" for="buscarEstadoUnidad">Buscar estado</label>
                    <div class="detalle-asignacion__busqueda-control"><i class="bi bi-search" aria-hidden="true"></i><input id="buscarEstadoUnidad" type="search" placeholder="Buscar por nombre o descripción" data-producto-buscar-estado></div>
                    <div class="detalle-estado__lista" data-producto-estados-unidad><p>Cargando estados disponibles...</p></div>
                    <p class="detalle-producto-modal__mensaje" data-producto-mensaje-estado></p>
                    <div class="detalle-producto-modal__acciones"><button type="button" class="detalle-producto-modal__cancelar" data-producto-cerrar-modal-estado>Cancelar</button><button type="submit" class="detalle-producto-modal__guardar"><i class="bi bi-check-lg" aria-hidden="true"></i><span>Guardar</span></button></div>
                </form>
            </div>
        </div>

        <?php include 'componentes/footer.php'; ?>
    </main>

    <script src="../recursos/js/detalle_producto.js?v=20261007-1" defer></script>
</body>
</html>
