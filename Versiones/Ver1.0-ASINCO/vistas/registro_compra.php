<?php
/**
 * registro_compra.php
 *
 * Muestra la vista principal del módulo de registro de compra
 */

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
    <link rel="stylesheet" href="../recursos/css/registro_compra.css">
    <script src="../recursos/js/registro_compra.js" defer></script>
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
                    <!-- Controles generales temporales de proveedor, despacho y costo -->
                    <section class="registro-panel preparar-controles">
                        <label>
                            <span>Proveedor general</span>
                            <button class="preparar-selector-solo-lectura" type="button" data-selector-proveedor aria-label="Mostrar proveedores sugeridos">
                                <span data-selector-proveedor-texto>Seleccionar</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </label>
                        <label>
                            <span>Método de despacho general</span>
                            <button class="preparar-selector-solo-lectura" type="button" data-selector-despacho aria-label="Mostrar métodos de despacho sugeridos">
                                <span data-selector-despacho-texto>Seleccionar</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </label>
                        <label>
                            <span>Costo de envío</span>
                            <input type="text" value="" data-costo-envio>
                        </label>
                    </section>

                    <!-- Lista temporal de productos para preparar la compra -->
                    <section class="registro-panel preparar-lista">
                        <header class="preparar-lista__titulo">
                            <div>
                                <h2>Lista de productos solicitados</h2>
                                <p>4 productos</p>
                            </div>
                            <div class="preparar-lista__vista">
                                <button class="preparar-lista__vista-activa" type="button" aria-label="Vista de lista"><i class="bi bi-list-ul"></i></button>
                                <button type="button" aria-label="Vista de tabla"><i class="bi bi-table"></i></button>
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
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><span class="preparar-imagen producto-imagen--verde"><i class="bi bi-cpu"></i></span></td>
                                        <td>Módulo LoRa 915MHz</td>
                                        <td>10</td>
                                        <td>Unidades</td>
                                        <td><input type="text" value="$12500" aria-label="Precio Módulo LoRa"></td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td><span class="preparar-imagen producto-imagen--rojo"><i class="bi bi-usb-symbol"></i></span></td>
                                        <td>Cable Dupont macho-hembra 20cm</td>
                                        <td>5</td>
                                        <td>Unidades</td>
                                        <td><input type="text" value="$12000" aria-label="Precio Cable Dupont"></td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td><span class="preparar-imagen producto-imagen--gris"><i class="bi bi-disc"></i></span></td>
                                        <td>Estano para soldar 60/40 1.0mm</td>
                                        <td>5</td>
                                        <td>Rollos</td>
                                        <td><input type="text" value="$3800" aria-label="Precio Estano"></td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td><span class="preparar-imagen producto-imagen--azul"><i class="bi bi-motherboard"></i></span></td>
                                        <td>ESP8266 ESP-12E</td>
                                        <td>20</td>
                                        <td>Unidades</td>
                                        <td><input type="text" value="$4800" aria-label="Precio ESP8266"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Mensaje informativo temporal para el segundo paso -->
                    <div class="registro-alerta-info">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Selecciona el proveedor y el método de despacho para los productos. <span>Las recomendaciones se basan en compras anteriores, precios y tiempos de entrega.</span></p>
                    </div>

                    <!-- Acciones temporales del segundo paso -->
                    <footer class="registro-preparar__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <div class="registro-preparar__total">
                            <span>Total estimado (4 productos)</span>
                            <strong>$250.000</strong>
                        </div>
                        <a class="registro-boton registro-boton--primario" href="registro_compra.php?paso=registrar">
                            <span>Continuar al siguiente paso</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </footer>
                </div>

                <!-- Panel temporal de proveedores sugeridos -->
                <aside class="registro-panel proveedores-sugeridos panel-sugerencias" data-panel-proveedores>
                    <header class="registro-panel__titulo registro-panel__titulo--azul proveedores-sugeridos__titulo">
                        <i class="bi bi-check-square"></i>
                        <div>
                            <h2>Proveedores sugeridos</h2>
                            <p>Recomendaciones basadas en compras anteriores</p>
                        </div>
                    </header>

                    <button class="proveedores-sugeridos__nuevo" type="button" data-abrir-modal-proveedor>Registrar nuevo proveedor</button>

                    <div class="proveedores-sugeridos__lista">
                        <article class="proveedor-card proveedor-card--seleccionable" data-proveedor="Arduino Chile" role="button" tabindex="0" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <div class="proveedor-card__contenido">
                                <div class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar"></span>
                                    <h3>Global components</h3>
                                </div>
                                <p>Último precio: $12500</p>
                                <strong>★★★★☆ 4.8 (24)</strong>
                                <div class="proveedor-card__etiquetas">
                                    <span>Confiable</span>
                                    <span>Entrega rápida</span>
                                    <span>Buenos Precios</span>
                                </div>
                            </div>
                            <small>Recomendado</small>
                        </article>

                        <article class="proveedor-card proveedor-card--seleccionable" data-proveedor="MakerLab Componentes" role="button" tabindex="0" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <div class="proveedor-card__contenido">
                                <div class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar proveedor-card__avatar--morado"></span>
                                    <h3>MakerLab Componentes</h3>
                                </div>
                                <p>Último precio: $12500</p>
                                <strong>★★★★☆ 4.6 (32)</strong>
                                <div class="proveedor-card__etiquetas">
                                    <span>Confiable</span>
                                    <span>Entrega rápida</span>
                                    <span>Proveedor habitual</span>
                                </div>
                            </div>
                            <small>Recomendado</small>
                        </article>

                        <article class="proveedor-card proveedor-card--seleccionable" data-proveedor="Altronics" role="button" tabindex="0" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <div class="proveedor-card__contenido">
                                <div class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar proveedor-card__avatar--amarillo"></span>
                                    <h3>Altronics</h3>
                                </div>
                                <p>Último precio: $12500</p>
                                <strong>★★★☆☆ 3.9 (8)</strong>
                                <div class="proveedor-card__etiquetas">
                                    <span>Confiable</span>
                                    <span>Buenos Precios</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <a class="proveedores-sugeridos__detalle" href="proveedores.php">
                        <i class="bi bi-list"></i>
                        <span>Ver detalle de proveedores</span>
                    </a>
                </aside>

                <!-- Panel temporal de métodos de despacho sugeridos -->
                <aside class="registro-panel proveedores-sugeridos panel-sugerencias" data-panel-despachos hidden>
                    <header class="registro-panel__titulo registro-panel__titulo--azul proveedores-sugeridos__titulo">
                        <i class="bi bi-check-square"></i>
                        <div>
                            <h2>Métodos de despacho sugeridos</h2>
                            <p>Recomendaciones basadas en compras anteriores</p>
                        </div>
                    </header>

                    <button class="proveedores-sugeridos__nuevo" type="button" data-abrir-modal-despacho>Registrar nuevo método de despacho</button>

                    <div class="proveedores-sugeridos__lista">
                        <button class="proveedor-card metodo-despacho-card" type="button" data-metodo-despacho="AndesTech Logística" data-costo-despacho="$6500" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <span class="proveedor-card__contenido">
                                <span class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar metodo-despacho-card__avatar--azul"></span>
                                    <span class="metodo-despacho-card__titulo">AndesTech Logística</span>
                                </span>
                                <span class="metodo-despacho-card__detalle">Último costo de envío: $6500</span>
                                <strong>★★★★☆ 4.8 (24)</strong>
                                <span class="proveedor-card__etiquetas"><span>Confiable</span><span>Entrega rápida</span><span>Buenos precios</span></span>
                            </span>
                            <small>Recomendado</small>
                        </button>

                        <button class="proveedor-card metodo-despacho-card" type="button" data-metodo-despacho="TecnoCarga Express" data-costo-despacho="$4000" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <span class="proveedor-card__contenido">
                                <span class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar metodo-despacho-card__avatar--naranjo"></span>
                                    <span class="metodo-despacho-card__titulo">TecnoCarga Express</span>
                                </span>
                                <span class="metodo-despacho-card__detalle">Último costo de envío: $4000</span>
                                <strong>★★★★☆ 4.6 (32)</strong>
                                <span class="proveedor-card__etiquetas"><span>Confiable</span><span>Economico</span><span>Con seguimiento</span></span>
                            </span>
                        </button>

                        <button class="proveedor-card metodo-despacho-card" type="button" data-metodo-despacho="Envio estandar" data-costo-despacho="GRATIS" aria-pressed="false">
                            <span class="proveedor-card__selector"></span>
                            <span class="proveedor-card__contenido">
                                <span class="proveedor-card__encabezado">
                                    <span class="proveedor-card__avatar metodo-despacho-card__avatar--amarillo"></span>
                                    <span class="metodo-despacho-card__titulo">Envío estándar</span>
                                </span>
                                <span class="metodo-despacho-card__detalle">Último costo: GRATIS</span>
                                <strong>★★★☆☆ 3.2 (8)</strong>
                                <span class="proveedor-card__etiquetas"><span>Confiable</span><span>Buenos precios</span></span>
                            </span>
                        </button>
                    </div>

                    <a class="proveedores-sugeridos__detalle" href="metodos_despacho.php">
                        <i class="bi bi-list"></i>
                        <span>Ver detalle de métodos de despacho</span>
                    </a>
                </aside>
            </div>

            <!-- Modal temporal para registrar proveedores -->
            <div class="registro-modal" data-modal-proveedor hidden>
                <section class="registro-modal__dialogo" role="dialog" aria-modal="true" aria-labelledby="tituloModalProveedor">
                    <header class="registro-modal__encabezado">
                        <div>
                            <h2 id="tituloModalProveedor">Registrar nuevo proveedor</h2>
                            <p>Completa los datos principales para agregarlo a esta compra.</p>
                        </div>
                        <button type="button" data-cerrar-modal-proveedor aria-label="Cerrar formulario de proveedor"><i class="bi bi-x-lg"></i></button>
                    </header>

                    <form class="registro-modal__formulario" data-formulario-proveedor>
                        <!-- Zona desplazable que contiene solamente los campos del proveedor -->
                        <div class="registro-modal__campos">
                            <label>
                                <span>Nombre o razón social <strong>*</strong></span>
                                <input type="text" name="nombre" placeholder="Ej. Componentes Chile SpA" required data-nombre-proveedor>
                            </label>
                            <label>
                                <span>RUT o identificador <strong>*</strong></span>
                                <input type="text" name="identificador" placeholder="Ej. 76.123.456-7" required>
                            </label>
                            <label>
                                <span>Correo electrónico <strong>*</strong></span>
                                <input type="email" name="correo" placeholder="contacto@proveedor.cl" required>
                            </label>
                            <label>
                                <span>Teléfono</span>
                                <input type="tel" name="telefono" placeholder="+56 9 1234 5678">
                            </label>
                            <label>
                                <span>Pais</span>
                                <select name="pais">
                                    <option>Chile</option>
                                    <option>Argentina</option>
                                    <option>Estados Unidos</option>
                                    <option>China</option>
                                    <option>Otro</option>
                                </select>
                            </label>
                            <label class="registro-modal__campo-completo">
                                <span>Dirección</span>
                                <input type="text" name="direccion" placeholder="Dirección comercial del proveedor">
                            </label>
                            <label class="registro-modal__campo-completo">
                                <span>Observaciones</span>
                                <textarea name="observaciones" placeholder="Información adicional o condiciones comerciales"></textarea>
                            </label>
                        </div>

                        <!-- Pie fijo separado de los campos desplazables -->
                        <footer class="registro-modal__acciones">
                            <button class="registro-boton registro-boton--gris" type="button" data-cerrar-modal-proveedor>Cancelar</button>
                            <button class="registro-boton registro-boton--primario" type="submit"><i class="bi bi-plus-lg"></i><span>Registrar proveedor</span></button>
                        </footer>
                    </form>
                </section>
            </div>

            <!-- Modal temporal para registrar métodos de despacho -->
            <div class="registro-modal" data-modal-despacho hidden>
                <section class="registro-modal__dialogo" role="dialog" aria-modal="true" aria-labelledby="tituloModalDespacho">
                    <header class="registro-modal__encabezado">
                        <div>
                            <h2 id="tituloModalDespacho">Registrar nuevo método de despacho</h2>
                            <p>Completa los datos principales para agregarlo a esta compra.</p>
                        </div>
                        <button type="button" data-cerrar-modal-despacho aria-label="Cerrar formulario de método de despacho"><i class="bi bi-x-lg"></i></button>
                    </header>

                    <form class="registro-modal__formulario" data-formulario-despacho>
                        <!-- Zona desplazable que contiene solamente los campos del despacho -->
                        <div class="registro-modal__campos">
                            <label>
                                <span>Nombre del método <strong>*</strong></span>
                                <input type="text" name="nombre_despacho" placeholder="Ej. ChileExpress" required data-nombre-despacho>
                            </label>
                            <label>
                                <span>Tipo de despacho <strong>*</strong></span>
                                <select name="tipo_despacho" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option>Courier nacional</option>
                                    <option>Courier internacional</option>
                                    <option>Transporte terrestre</option>
                                    <option>Transporte maritimo</option>
                                    <option>Retiro en tienda</option>
                                </select>
                            </label>
                            <label>
                                <span>Correo de contacto</span>
                                <input type="email" name="correo_despacho" placeholder="contacto@transportista.cl">
                            </label>
                            <label>
                                <span>Teléfono</span>
                                <input type="tel" name="telefono_despacho" placeholder="+56 9 1234 5678">
                            </label>
                            <label>
                                <span>Costo estimado (CLP) <strong>*</strong></span>
                                <input type="number" name="costo_despacho" min="0" step="1" placeholder="Ej. 6500" required data-costo-nuevo-despacho>
                            </label>
                            <label>
                                <span>Tiempo estimado de entrega</span>
                                <input type="text" name="tiempo_entrega" placeholder="Ej. 3 a 5 días" data-tiempo-nuevo-despacho>
                            </label>
                            <label>
                                <span>Seguimiento del envío</span>
                                <select name="seguimiento">
                                    <option>Incluye seguimiento</option>
                                    <option>No incluye seguimiento</option>
                                </select>
                            </label>
                            <label class="registro-modal__campo-completo">
                                <span>Observaciones</span>
                                <textarea name="observaciones_despacho" placeholder="Cobertura, restricciones o información adicional"></textarea>
                            </label>
                        </div>

                        <!-- Pie fijo separado de los campos desplazables -->
                        <footer class="registro-modal__acciones">
                            <button class="registro-boton registro-boton--gris" type="button" data-cerrar-modal-despacho>Cancelar</button>
                            <button class="registro-boton registro-boton--primario" type="submit"><i class="bi bi-plus-lg"></i><span>Registrar método</span></button>
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
                    <!-- Datos generales temporales para confirmar la compra -->
                    <section class="registro-panel confirmar-datos">
                        <h2>Datos generales de la compra</h2>
                        <div class="confirmar-formulario">
                            <label class="confirmar-campo">
                                <span>Proveedor general <strong>*</strong></span>
                                <input type="text" value="Arduino Chile">
                            </label>
                            <label class="confirmar-campo">
                                <span>Método de despacho <strong>*</strong></span>
                                <input type="text" value="TecnoCarga Express">
                            </label>
                            <label class="confirmar-campo">
                                <span>Fecha de compra <strong>*</strong></span>
                                <span class="confirmar-fecha">
                                    <input type="text" value="02/06/2026">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                            </label>
                            <label class="confirmar-campo">
                                <span>Fecha estimada de llegada</span>
                                <span class="confirmar-fecha">
                                    <input type="text" value="08/06/2026">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                            </label>
                            <label class="confirmar-campo">
                                <span>Responsable de la compra <strong>*</strong></span>
                                <select>
                                    <option>Benjamin Jeria</option>
                                </select>
                            </label>
                            <label class="confirmar-campo">
                                <span>Moneda <strong>*</strong></span>
                                <select>
                                    <option>CLP - Peso Chileno</option>
                                </select>
                            </label>
                            <label class="confirmar-campo">
                                <span>Referencia / N°Pedido <strong>*</strong></span>
                                <select>
                                    <option>OC-2026-0184</option>
                                </select>
                            </label>
                            <label class="confirmar-campo confirmar-campo--observaciones">
                                <span>Observaciones (opcional)</span>
                                <textarea>Compra para reposición de stock y pruebas de prototipos</textarea>
                            </label>
                        </div>
                    </section>

                    <!-- Resumen temporal de productos antes de registrar -->
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
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td><span class="preparar-imagen producto-imagen--verde"><i class="bi bi-cpu"></i></span></td>
                                        <td>Módulo LoRa 915MHz</td>
                                        <td>10</td>
                                        <td>Unidades</td>
                                        <td>$12500</td>
                                        <td>$125000</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td><span class="preparar-imagen producto-imagen--rojo"><i class="bi bi-usb-symbol"></i></span></td>
                                        <td>Cable Dupont macho-hembra 20cm</td>
                                        <td>5</td>
                                        <td>Unidades</td>
                                        <td>$12000</td>
                                        <td>$60000</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td><span class="preparar-imagen producto-imagen--gris"><i class="bi bi-disc"></i></span></td>
                                        <td>Estano para soldar 60/40 1.0mm</td>
                                        <td>5</td>
                                        <td>Rollos</td>
                                        <td>$3800</td>
                                        <td>$19000</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td><span class="preparar-imagen producto-imagen--azul"><i class="bi bi-motherboard"></i></span></td>
                                        <td>ESP8266 ESP-12E</td>
                                        <td>20</td>
                                        <td>Unidades</td>
                                        <td>$4800</td>
                                        <td>$96000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Mensaje informativo temporal para confirmar la compra -->
                    <div class="registro-alerta-info confirmar-alerta">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Revisa los datos y confirma para registrar la compra</p>
                    </div>

                    <!-- Acciones temporales del tercer paso -->
                    <footer class="registro-confirmar__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php?paso=preparar">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <a class="registro-boton registro-boton--primario" href="#">
                            <span>Registrar la compra</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </footer>
                </div>

                <!-- Panel temporal con el resumen final de la compra -->
                <aside class="registro-panel compra-resumen">
                    <header class="registro-panel__titulo registro-panel__titulo--azul compra-resumen__titulo">
                        <i class="bi bi-cart"></i>
                        <h2>Resumen de la compra</h2>
                    </header>

                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--celeste"><i class="bi bi-person"></i></span>
                        <div>
                            <strong>Proveedor general</strong>
                            <p>Arduino Chile</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--amarillo"><i class="bi bi-truck"></i></span>
                        <div>
                            <strong>Método de despacho</strong>
                            <p>TecnoCarga Express</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--azul"><i class="bi bi-cart"></i></span>
                        <div>
                            <strong>4</strong>
                            <p>Productos</p>
                        </div>
                    </div>
                    <div class="compra-resumen__item">
                        <span class="compra-resumen__icono compra-resumen__icono--azul"><i class="bi bi-box-seam"></i></span>
                        <div>
                            <strong>40</strong>
                            <p>Unidades totales</p>
                        </div>
                    </div>

                    <div class="compra-resumen__costos">
                        <p><span>Subtotal productos</span><strong>$300000</strong></p>
                        <p><span>Costo de envío</span><strong>$6500</strong></p>
                    </div>

                    <div class="compra-resumen__total">
                        <span>Total estimado</span>
                        <strong>$306.500</strong>
                    </div>

                    <div class="compra-resumen__entrega">
                        <i class="bi bi-calendar3"></i>
                        <span>Entrega estimada</span>
                        <strong>2 - 6 días</strong>
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
                                    <th>Notas (Opcional)</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Módulo LoRa 915MHz</td>
                                    <td><input type="number" value="10" aria-label="Cantidad módulo LoRa"></td>
                                    <td>
                                        <select aria-label="Unidad módulo LoRa">
                                            <option>Unidades</option>
                                        </select>
                                    </td>
                                    <td><input type="text" placeholder="Ej: Para prototipos IoT"></td>
                                    <td>
                                        <div class="registro-acciones">
                                            <button type="button" aria-label="Editar producto"><i class="bi bi-pencil"></i></button>
                                            <button type="button" aria-label="Eliminar producto"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Cable Dupont macho-hembra 20cm</td>
                                    <td><input type="number" value="5" aria-label="Cantidad cable Dupont"></td>
                                    <td>
                                        <select aria-label="Unidad cable Dupont">
                                            <option>Unidades</option>
                                        </select>
                                    </td>
                                    <td><input type="text" placeholder="Ej: Para prototipos IoT"></td>
                                    <td>
                                        <div class="registro-acciones">
                                            <button type="button" aria-label="Editar producto"><i class="bi bi-pencil"></i></button>
                                            <button type="button" aria-label="Eliminar producto"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Estano para soldar 60/40 1.0mm ...</td>
                                    <td><input type="number" value="5" aria-label="Cantidad estano"></td>
                                    <td>
                                        <select aria-label="Unidad estano">
                                            <option>Rollos</option>
                                        </select>
                                    </td>
                                    <td><input type="text" placeholder="Ej: Para prototipos IoT"></td>
                                    <td>
                                        <div class="registro-acciones">
                                            <button type="button" aria-label="Editar producto"><i class="bi bi-pencil"></i></button>
                                            <button type="button" aria-label="Eliminar producto"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>ESP8266 ESP-12E</td>
                                    <td><input type="number" value="20" aria-label="Cantidad ESP8266"></td>
                                    <td>
                                        <select aria-label="Unidad ESP8266">
                                            <option>Unidades</option>
                                        </select>
                                    </td>
                                    <td><input type="text" placeholder="Ej: Para prototipos IoT"></td>
                                    <td>
                                        <div class="registro-acciones">
                                            <button type="button" aria-label="Editar producto"><i class="bi bi-pencil"></i></button>
                                            <button type="button" aria-label="Eliminar producto"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Acciones temporales para continuar el flujo -->
                    <footer class="registro-lista__acciones">
                        <a class="registro-boton registro-boton--gris" href="ingresar_productos.php">
                            <span>Agregar producto</span>
                            <i class="bi bi-plus-lg"></i>
                        </a>
                        <a class="registro-boton registro-boton--primario" href="registro_compra.php?paso=preparar">
                            <span>Continuar al siguiente paso</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </footer>
                </div>

                <!-- Resumen temporal de la solicitud -->
                <aside class="registro-panel registro-resumen">
                    <header class="registro-panel__titulo registro-panel__titulo--azul">
                        <h2>Resumen de la solicitud</h2>
                    </header>
                    <div class="registro-resumen__contenido">
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--azul"><i class="bi bi-cart"></i></span>
                            <div>
                                <strong>4</strong>
                                <p>productos</p>
                            </div>
                        </div>
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--celeste"><i class="bi bi-box-seam"></i></span>
                            <div>
                                <strong>135</strong>
                                <p>unidades totales</p>
                            </div>
                        </div>
                        <div class="registro-resumen__item">
                            <span class="registro-resumen__icono registro-resumen__icono--amarillo"><i class="bi bi-tag"></i></span>
                            <div>
                                <strong>1</strong>
                                <p>producto nuevo detectado</p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
        <?php endif; ?>

        <?php include 'componentes/footer.php'; ?>
    </main>
</body>
</html>
