<?php
/**
 * modal_detalle_entidad.php
 *
 * Componente reutilizable para mostrar el detalle de un proveedor o transportista seleccionado desde un listado
 */
?>

<!-- Modal reutilizable para detalles de proveedores y transportistas -->
<div class="modal-detalle" data-detalle-modal hidden>
    <section class="modal-detalle__contenido" role="dialog" aria-modal="true" aria-labelledby="detalleEntidadTitulo">
        <header class="modal-detalle__encabezado">
            <div>
                <p class="modal-detalle__etiqueta" data-detalle-tipo>Detalle</p>
                <div class="modal-detalle__titulo-linea">
                    <h2 id="detalleEntidadTitulo" data-detalle-titulo>Detalle de entidad</h2>
                    <span class="modal-detalle__calificacion" data-detalle-calificacion></span>
                </div>
            </div>
            <button class="modal-detalle__cerrar" type="button" aria-label="Cerrar detalle" data-detalle-cerrar>
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>
        <div class="modal-detalle__cuerpo">
            <div class="modal-detalle__bloque-datos">
                <h3 class="modal-detalle__titulo-seccion" data-detalle-principales-titulo>Datos de la entidad</h3>
                <div class="modal-detalle__principales-panel">
                    <div class="modal-detalle__principales-estructura">
                        <div class="modal-detalle__principales" data-detalle-principales></div>
                        <div class="modal-detalle__imagen-contenedor">
                            <img class="modal-detalle__imagen" data-detalle-imagen alt="" hidden>
                            <div class="modal-detalle__imagen-fallback" data-detalle-imagen-fallback aria-hidden="true"></div>
                            <button class="modal-detalle__editar-imagen" type="button" data-editar-entidad-campo="imagen" aria-label="Editar imagen" hidden>
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-detalle__bloque-datos modal-detalle__bloque-datos--relevantes">
                <h3 class="modal-detalle__titulo-seccion">Datos relevantes</h3>
                <div class="modal-detalle__informacion" data-detalle-informacion></div>
            </div>
        </div>
        <footer class="modal-detalle__pie">
            <button class="modal-detalle__boton modal-detalle__boton--volver" type="button" data-detalle-volver>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Volver</span>
            </button>
            <button class="modal-detalle__boton modal-detalle__boton--guardar" type="button" data-detalle-guardar>
                <span>Guardar</span>
                <i class="bi bi-check2" aria-hidden="true"></i>
            </button>
        </footer>
    </section>
</div>

<!-- Modal para editar un campo del proveedor o transportista -->
<div class="modal-sistema modal-edicion-transportista" data-edicion-transportista-modal hidden>
    <div class="modal-sistema__contenido modal-edicion-transportista__contenido" role="dialog" aria-modal="true" aria-labelledby="editarEntidadTitulo">
        <div class="modal-sistema__encabezado">
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
            <h2 id="editarEntidadTitulo" data-edicion-entidad-titulo>Editar campo</h2>
        </div>
        <div class="modal-edicion-transportista__cuerpo" data-edicion-entidad-contenido></div>
        <div class="modal-sistema__acciones">
            <button class="modal-sistema__boton modal-sistema__boton--secundario" type="button" data-edicion-entidad-cancelar>Cancelar</button>
            <button class="modal-sistema__boton modal-sistema__boton--principal" type="button" data-edicion-entidad-aceptar>Aceptar</button>
        </div>
    </div>
</div>

<!-- Modal de confirmación para guardar cambios de proveedores o transportistas -->
<div class="modal-sistema modal-confirmacion-transportista" data-confirmacion-transportista-modal hidden>
    <div class="modal-sistema__contenido" role="dialog" aria-modal="true" aria-labelledby="confirmarEntidadTitulo">
        <div class="modal-sistema__encabezado">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            <h2 id="confirmarEntidadTitulo">Guardar cambios</h2>
        </div>
        <p data-confirmacion-entidad-mensaje>¿Estás seguro de que deseas guardar los cambios?</p>
        <div class="modal-sistema__acciones">
            <button class="modal-sistema__boton modal-sistema__boton--secundario" type="button" data-confirmacion-entidad-cancelar>Cancelar</button>
            <button class="modal-sistema__boton modal-sistema__boton--principal" type="button" data-confirmacion-entidad-aceptar>Sí, guardar</button>
        </div>
    </div>
</div>
