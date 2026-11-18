<?php
/**
 * registro_compra.php
 *
 * Muestra la vista principal del módulo de registro de compra
 */

require_once __DIR__ . '/../configuracion/permisos.php';
exigirAccesoVista('registro_compra');

$vistaActiva = 'registro_compra';
$tituloBarraSuperior = 'Registro de compra';
$subtituloBarraSuperior = 'Gestiona los productos solicitados antes de preparar y registrar la compra.';
$pasoRegistroCompra = $_GET['paso'] ?? 'ingresar';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Registro de compra</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/registro_compra.css?v=20261112-8">
    <script src="../recursos/js/registro_compra.js?v=20261112-19" defer></script>
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del primer paso del registro de compra -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <?php if ($pasoRegistroCompra === 'preparar') : ?>
        <section class="registro-compra">
            <!-- Estado textual del segundo paso del flujo -->
            <p class="registro-subpaso"><span>Paso 2 de 3</span> - Preparar la compra</p>

            <!-- Indicador visual del avance del registro de compra -->
            <ol class="registro-pasos" aria-label="Pasos del registro de compra">
                <li class="registro-pasos__item registro-pasos__item--completo">
                    <span><i class="bi bi-check-lg"></i></span>
                    <strong>Ingresar productos</strong>
                </li>
                <li class="registro-pasos__item registro-pasos__item--activo">
                    <span>2</span>
                    <strong>Preparar compra</strong>
                </li>
                <li class="registro-pasos__item">
                    <span>3</span>
                    <strong>Registrar la compra</strong>
                </li>
            </ol>

            <div class="registro-preparar__grid">
                <div class="registro-preparar__columna">
                    <!-- Controles generales de proveedor, transportista y costo -->
                    <section class="registro-panel preparar-controles">
                        <label>
                            <span>Proveedor general <strong>*</strong></span>
                            <button class="preparar-selector-solo-lectura" type="button" data-selector-proveedor aria-label="Mostrar proveedores sugeridos" aria-required="true" aria-expanded="true">
                                <span data-selector-proveedor-texto>Seleccionar</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </label>
                        <label>
                            <span>Transportista <strong>*</strong></span>
                            <button class="preparar-selector-solo-lectura" type="button" data-selector-despacho aria-label="Mostrar transportistas" aria-expanded="false">
                                <span data-selector-despacho-texto>Seleccionar</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </label>
                        <label>
                            <span>Costo de envío</span>
                            <input type="number" min="0" step="0.01" value="0" data-costo-envio>
                        </label>
                    </section>

                    <!-- Lista de productos incluidos en la compra -->
                    <section class="registro-panel preparar-lista">
                        <header class="preparar-lista__titulo">
                            <div>
                                <h2>Lista de productos solicitados</h2>
                                <p data-preparar-productos-texto>0 productos</p>
                            </div>
                        </header>

                        <div class="registro-tabla preparar-tabla">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Unidad</th>
                                        <th>Precio Unitario</th>
                                    </tr>
                                </thead>
                                <tbody data-preparar-productos>
                                    <tr><td colspan="6">No hay productos en la solicitud.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Indicaciones para preparar la compra -->
                    <div class="registro-alerta-info">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Selecciona el proveedor y el transportista e ingresa el precio unitario de cada producto.</p>
                    </div>
                    <p class="registro-formulario__mensaje" data-preparar-mensaje role="status" aria-live="polite"></p>

                    <!-- Acciones para volver o continuar con la compra -->
                    <footer class="registro-preparar__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <div class="registro-preparar__total">
                            <span data-preparar-total-texto>Total estimado</span>
                            <strong data-preparar-total>$0</strong>
                        </div>
                        <a class="registro-boton registro-boton--primario" href="registro_compra.php?paso=registrar" data-preparar-continuar>
                            <span>Continuar al siguiente paso</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </footer>
                </div>

                <!-- Panel de proveedores sugeridos -->
                <aside class="registro-panel proveedores-sugeridos panel-sugerencias" data-panel-proveedores>
                    <header class="registro-panel__titulo registro-panel__titulo--azul proveedores-sugeridos__titulo">
                        <i class="bi bi-check-square"></i>
                        <div>
                            <h2>Proveedores sugeridos</h2>
                            <p>Recomendaciones basadas en compras anteriores</p>
                        </div>
                    </header>

                    <button class="proveedores-sugeridos__nuevo" type="button" data-abrir-modal-proveedor>Registrar nuevo proveedor</button>

                    <label class="proveedores-sugeridos__busqueda">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" placeholder="¿No lo encuentras? Buscar proveedor..." aria-label="Buscar en todos los proveedores" autocomplete="off" data-buscar-proveedores>
                        <button type="button" aria-label="Limpiar búsqueda de proveedores" data-limpiar-busqueda-proveedores hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </label>
                    <p class="proveedores-sugeridos__estado" data-estado-proveedores aria-live="polite">Hasta 5 recomendaciones. Busca para ver otros proveedores.</p>
                    <div class="proveedores-sugeridos__lista" data-proveedores-sugeridos-lista aria-live="polite"></div>
                    <nav class="sugerencias-paginacion" data-paginacion-proveedores aria-label="Páginas de resultados de proveedores" hidden>
                        <button type="button" data-pagina-anterior-proveedores aria-label="Página anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                        <span data-pagina-info-proveedores></span>
                        <button type="button" data-pagina-siguiente-proveedores aria-label="Página siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                    </nav>

                    <a class="proveedores-sugeridos__detalle" href="proveedores.php">
                        <i class="bi bi-list"></i>
                        <span>Ver detalle de proveedores</span>
                    </a>
                </aside>

                <!-- Panel de transportistas disponibles -->
                <aside class="registro-panel proveedores-sugeridos panel-sugerencias" data-panel-despachos hidden>
                    <header class="registro-panel__titulo registro-panel__titulo--azul proveedores-sugeridos__titulo">
                        <i class="bi bi-check-square"></i>
                        <div>
                            <h2>Transportistas sugeridos</h2>
                            <p>Mejor calificación y experiencia en compras anteriores.</p>
                        </div>
                    </header>

                    <button class="proveedores-sugeridos__nuevo" type="button" data-abrir-modal-despacho>Registrar nuevo transportista</button>

                    <label class="proveedores-sugeridos__busqueda">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" placeholder="¿No lo encuentras? Buscar transportista..." aria-label="Buscar en todos los transportistas" autocomplete="off" data-buscar-transportistas>
                        <button type="button" aria-label="Limpiar búsqueda de transportistas" data-limpiar-busqueda-transportistas hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </label>
                    <p class="proveedores-sugeridos__estado" data-estado-transportistas aria-live="polite">Hasta 5 recomendaciones. Busca para ver otros transportistas.</p>
                    <div class="proveedores-sugeridos__lista" data-transportistas-sugeridos-lista aria-live="polite"></div>
                    <nav class="sugerencias-paginacion" data-paginacion-transportistas aria-label="Páginas de resultados de transportistas" hidden>
                        <button type="button" data-pagina-anterior-transportistas aria-label="Página anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                        <span data-pagina-info-transportistas></span>
                        <button type="button" data-pagina-siguiente-transportistas aria-label="Página siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                    </nav>

                    <a class="proveedores-sugeridos__detalle" href="metodos_despacho.php">
                        <i class="bi bi-list"></i>
                        <span>Ver transportistas</span>
                    </a>
                </aside>
            </div>

            <!-- Modal para registrar un proveedor en el catálogo -->
            <div class="registro-modal" data-modal-proveedor hidden>
                <section class="registro-modal__dialogo" role="dialog" aria-modal="true" aria-labelledby="tituloModalProveedor">
                    <header class="registro-modal__encabezado">
                        <div>
                            <h2 id="tituloModalProveedor">Registrar nuevo proveedor</h2>
                            <p>Se guardará en el catálogo y quedará seleccionado para esta compra.</p>
                        </div>
                        <button type="button" data-cerrar-modal-proveedor aria-label="Cerrar formulario de proveedor"><i class="bi bi-x-lg"></i></button>
                    </header>

                    <form class="registro-modal__formulario" data-formulario-proveedor>
                        <!-- Zona desplazable que contiene solamente los campos del proveedor -->
                        <div class="registro-modal__campos">
                            <label>
                                <span>Nombre comercial <strong>*</strong></span>
                                <input type="text" name="nombre" maxlength="150" placeholder="Ej. Componentes Chile" required data-nombre-proveedor>
                            </label>
                            <label>
                                <span>Razón social <strong>*</strong></span>
                                <input type="text" name="razon_social" maxlength="200" placeholder="Ej. Componentes Chile SpA" required>
                            </label>
                            <label>
                                <span>RUT <strong>*</strong></span>
                                <input type="text" name="rut" maxlength="20" placeholder="Ej. 76.123.456-7" required>
                            </label>
                            <label>
                                <span>Correo electrónico</span>
                                <input type="email" name="correo_electronico" maxlength="150" placeholder="contacto@proveedor.cl">
                            </label>
                            <label>
                                <span>Teléfono</span>
                                <input type="tel" name="telefono" maxlength="30" placeholder="+56 9 1234 5678">
                            </label>
                            <label class="registro-modal__campo-completo">
                                <span>Dirección</span>
                                <input type="text" name="direccion" maxlength="255" placeholder="Dirección comercial del proveedor">
                            </label>
                            <p class="registro-formulario__mensaje" data-proveedor-nuevo-mensaje data-mensaje-entidad-nueva role="status" aria-live="polite"></p>
                        </div>

                        <!-- Pie fijo separado de los campos desplazables -->
                        <footer class="registro-modal__acciones">
                            <button class="registro-boton registro-boton--gris" type="button" data-cerrar-modal-proveedor>Cancelar</button>
                            <button class="registro-boton registro-boton--primario" type="submit"><i class="bi bi-plus-lg"></i><span>Registrar proveedor</span></button>
                        </footer>
                    </form>
                </section>
            </div>

            <!-- Modal para registrar un transportista en el catálogo -->
            <div class="registro-modal" data-modal-despacho hidden>
                <section class="registro-modal__dialogo" role="dialog" aria-modal="true" aria-labelledby="tituloModalDespacho">
                    <header class="registro-modal__encabezado">
                        <div>
                            <h2 id="tituloModalDespacho">Registrar nuevo transportista</h2>
                            <p>Se guardará en el catálogo y quedará seleccionado para esta compra.</p>
                        </div>
                        <button type="button" data-cerrar-modal-despacho aria-label="Cerrar formulario de transportista"><i class="bi bi-x-lg"></i></button>
                    </header>

                    <form class="registro-modal__formulario" data-formulario-despacho>
                        <!-- Zona desplazable que contiene solamente los campos del despacho -->
                        <div class="registro-modal__campos">
                            <label>
                                <span>Nombre del transportista <strong>*</strong></span>
                                <input type="text" name="nombre" maxlength="150" placeholder="Ej. ChileExpress" required>
                            </label>
                            <label>
                                <span>Correo electrónico</span>
                                <input type="email" name="correo_electronico" maxlength="150" placeholder="contacto@transportista.cl">
                            </label>
                            <label>
                                <span>Teléfono</span>
                                <input type="tel" name="telefono" maxlength="30" placeholder="+56 9 1234 5678">
                            </label>
                            <label class="registro-modal__campo-completo">
                                <span>Descripción</span>
                                <textarea name="descripcion" maxlength="255" placeholder="Tipo de servicio, cobertura u otra información"></textarea>
                            </label>
                            <p class="registro-formulario__mensaje" data-transportista-nuevo-mensaje data-mensaje-entidad-nueva role="status" aria-live="polite"></p>
                        </div>

                        <!-- Pie fijo separado de los campos desplazables -->
                        <footer class="registro-modal__acciones">
                            <button class="registro-boton registro-boton--gris" type="button" data-cerrar-modal-despacho>Cancelar</button>
                            <button class="registro-boton registro-boton--primario" type="submit"><i class="bi bi-plus-lg"></i><span>Registrar transportista</span></button>
                        </footer>
                    </form>
                </section>
            </div>
        </section>
        <?php elseif ($pasoRegistroCompra === 'registrar') : ?>
        <section class="registro-compra">
            <!-- Estado textual del tercer paso del flujo -->
            <p class="registro-subpaso"><span>Paso 3 de 3</span> - Confirmar registro de compra</p>

            <!-- Indicador visual del avance del registro de compra -->
            <ol class="registro-pasos" aria-label="Pasos del registro de compra">
                <li class="registro-pasos__item registro-pasos__item--completo">
                    <span><i class="bi bi-check-lg"></i></span>
                    <strong>Ingresar productos</strong>
                </li>
                <li class="registro-pasos__item registro-pasos__item--completo">
                    <span><i class="bi bi-check-lg"></i></span>
                    <strong>Preparar compra</strong>
                </li>
                <li class="registro-pasos__item registro-pasos__item--activo">
                    <span>3</span>
                    <strong>Registrar la compra</strong>
                </li>
            </ol>

            <div class="registro-confirmar__grid">
                <div class="registro-confirmar__columna">
                    <div class="registro-confirmar__formulario">
                    <!-- Datos generales de la compra que se guardarán al confirmar -->
                    <section class="registro-panel confirmar-datos">
                        <h2>Datos generales de la compra</h2>
                        <div class="confirmar-formulario">
                            <label class="confirmar-campo">
                                <span>Proveedor general <strong>*</strong></span>
                                <input type="text" readonly required data-confirmar-proveedor>
                            </label>
                            <label class="confirmar-campo">
                                <span>Transportista <strong>*</strong></span>
                                <input type="text" readonly required data-confirmar-transportista>
                            </label>
                            <label class="confirmar-campo">
                                <span>Fecha de compra <strong>*</strong></span>
                                <span class="confirmar-fecha">
                                    <input type="date" required data-confirmar-fecha-compra>
                                    <i class="bi bi-calendar3"></i>
                                </span>
                            </label>
                            <label class="confirmar-campo">
                                <span>Fecha estimada de llegada</span>
                                <span class="confirmar-fecha">
                                    <input type="date" data-confirmar-fecha-estimada>
                                    <i class="bi bi-calendar3"></i>
                                </span>
                            </label>
                            <label class="confirmar-campo">
                                <span>Responsable de la compra <strong>*</strong></span>
                                <select required data-confirmar-responsable>
                                    <option value="">Cargando responsables...</option>
                                </select>
                            </label>
                            <label class="confirmar-campo">
                                <span>Moneda <strong>*</strong></span>
                                <select required data-confirmar-moneda>
                                    <option value="">Cargando monedas...</option>
                                </select>
                            </label>
                            <label class="confirmar-campo">
                                <span>Código de compra</span>
                                <input type="text" readonly data-confirmar-codigo-compra placeholder="Generando código...">
                            </label>
                            <label class="confirmar-campo confirmar-campo--observaciones">
                                <span>Observaciones (opcional)</span>
                                <textarea maxlength="5000" data-confirmar-observaciones></textarea>
                            </label>
                            <label class="confirmar-campo confirmar-campo--referencia">
                                <span>Referencia / N°Pedido del proveedor</span>
                                <input type="text" maxlength="80" data-confirmar-pedido>
                            </label>
                        </div>
                    </section>

                    <!-- Resumen de los productos y valores que se guardarán -->
                    <section class="registro-panel confirmar-productos">
                        <header class="confirmar-productos__titulo">
                            <h2>Resumen de productos</h2>
                        </header>
                        <div class="registro-tabla confirmar-tabla">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Unidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody data-confirmar-productos>
                                    <tr><td colspan="7">No hay productos en la solicitud.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Estado del registro final -->
                    <div class="registro-alerta-info confirmar-alerta">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Revisa los datos y confirma para registrar la compra. El stock se sumará al recibirla.</p>
                    </div>
                    <p class="registro-formulario__mensaje" data-confirmar-mensaje role="status" aria-live="polite"></p>

                    <!-- Acciones para regresar o confirmar la compra -->
                    <footer class="registro-confirmar__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php?paso=preparar">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <button class="registro-boton registro-boton--primario" type="button" data-registrar-compra>
                            <span>Registrar la compra</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </footer>
                    </div>
                </div>

                <!-- Resumen de la compra antes de registrarla -->
                <aside class="registro-panel compra-resumen">
                    <header class="registro-panel__titulo registro-panel__titulo--azul compra-resumen__titulo">
                        <i class="bi bi-cart"></i>
                        <h2>Resumen de la compra</h2>
                    </header>

                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--celeste"><i class="bi bi-person"></i></span>
                        <div>
                            <strong>Proveedor general</strong>
                            <p data-resumen-proveedor>--</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--amarillo"><i class="bi bi-truck"></i></span>
                        <div>
                            <strong>Transportista</strong>
                            <p data-resumen-transportista>--</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--azul"><i class="bi bi-cart"></i></span>
                        <div>
                            <strong data-resumen-confirmar-productos>0</strong>
                            <p>Productos</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--azul"><i class="bi bi-box-seam"></i></span>
                        <div>
                            <strong data-resumen-confirmar-unidades>0</strong>
                            <p>Unidades totales</p>
                        </div>
                    </div>

                    <div class="compra-resumen__costos">
                        <p><span>Subtotal productos</span><strong data-resumen-subtotal>$0</strong></p>
                        <p><span>Costo de envío</span><strong data-resumen-costo-envio>$0</strong></p>
                    </div>
                    <p class="compra-resumen__tipo-cambio" data-resumen-tipo-cambio aria-live="polite" hidden></p>

                    <div class="compra-resumen__total">
                        <span>Total estimado</span>
                        <strong data-resumen-total>$0</strong>
                    </div>

                    <div class="compra-resumen__entrega">
                        <i class="bi bi-calendar3"></i>
                        <span>Entrega estimada</span>
                        <strong data-resumen-entrega>Por confirmar</strong>
                    </div>
                </aside>
            </div>
        </section>
        <?php else : ?>
        <section class="registro-compra">
            <!-- Estado textual del paso actual del flujo -->
            <p class="registro-subpaso"><span>Paso 1 de 3</span> - Ingresar productos</p>

            <!-- Indicador visual del avance del registro de compra -->
            <ol class="registro-pasos" aria-label="Pasos del registro de compra">
                <li class="registro-pasos__item registro-pasos__item--activo">
                    <span>1</span>
                    <strong>Ingresar productos</strong>
                </li>
                <li class="registro-pasos__item">
                    <span>2</span>
                    <strong>Preparar compra</strong>
                </li>
                <li class="registro-pasos__item">
                    <span>3</span>
                    <strong>Registrar la compra</strong>
                </li>
            </ol>

            <div class="registro-compra__grid">
                <!-- Columna izquierda con tabla y acciones del paso -->
                <div class="registro-lista-columna">
                    <section class="registro-panel registro-lista">
                        <header class="registro-panel__titulo">
                            <h2>Lista de productos solicitados</h2>
                        </header>

                        <div class="registro-tabla">
                            <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Unidad</th>
                                    <th>Categoría</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody data-productos-solicitados>
                                <tr><td colspan="6">Todavía no se han agregado productos.</td></tr>
                            </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Acciones para agregar productos y avanzar en el registro -->
                    <footer class="registro-lista__acciones">
                        <a class="registro-boton registro-boton--gris" href="ingresar_productos.php">
                            <span>Agregar producto</span>
                            <i class="bi bi-plus-lg"></i>
                        </a>
                        <a class="registro-boton registro-boton--primario" href="registro_compra.php?paso=preparar" data-continuar-registro aria-disabled="true">
                            <span>Continuar al siguiente paso</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </footer>
                    <p class="registro-formulario__mensaje" data-registro-mensaje-vacio role="status" aria-live="polite"></p>
                </div>

                <!-- Resumen de productos solicitados -->
                <aside class="registro-panel registro-resumen">
                    <header class="registro-panel__titulo registro-panel__titulo--azul">
                        <h2>Resumen de la solicitud</h2>
                    </header>
                    <div class="registro-resumen__contenido">
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--azul"><i class="bi bi-cart"></i></span>
                            <div>
                                <strong data-resumen-productos>0</strong>
                                <p data-resumen-productos-texto>productos</p>
                            </div>
                        </div>
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--celeste"><i class="bi bi-box-seam"></i></span>
                            <div>
                                <strong data-resumen-unidades>0</strong>
                                <p data-resumen-unidades-texto>unidades totales</p>
                            </div>
                        </div>
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--amarillo"><i class="bi bi-tag"></i></span>
                            <div>
                                <strong data-resumen-nuevos>0</strong>
                                <p data-resumen-nuevos-texto>productos nuevos detectados</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Confirmación antes de quitar un producto de la solicitud -->
            <div class="registro-eliminar-modal" data-modal-eliminar-producto hidden>
                <section class="registro-eliminar-modal__dialogo" role="alertdialog" aria-modal="true" aria-labelledby="tituloEliminarProducto" aria-describedby="textoEliminarProducto">
                    <h2 id="tituloEliminarProducto">Eliminar producto</h2>
                    <p id="textoEliminarProducto" data-modal-eliminar-producto-texto>¿Estás seguro de que quieres quitar este producto de la solicitud?</p>
                    <div class="registro-eliminar-modal__acciones">
                        <button type="button" class="registro-boton registro-boton--gris" data-cancelar-eliminar-producto>Cancelar</button>
                        <button type="button" class="registro-boton registro-boton--eliminar" data-confirmar-eliminar-producto>Eliminar</button>
                    </div>
                </section>
            </div>
        </section>
        <?php endif; ?>

        <?php include 'componentes/footer.php'; ?>
    </main>
</body>
</html>
