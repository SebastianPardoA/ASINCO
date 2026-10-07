<?php
/**
 * ingresar_productos.php
 *
 * Muestra el formulario temporal para agregar productos al registro de compra
 */

$vistaActiva = 'registro_compra';
$tituloBarraSuperior = 'Registro de compra';
$subtituloBarraSuperior = 'Agrega productos a la solicitud y revisa coincidencias existentes.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Ingresar productos</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/registro_compra.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del formulario para agregar productos -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

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

            <div class="registro-producto__grid">
                <!-- Columna izquierda con formulario y acciones del paso -->
                <div class="registro-formulario-columna">
                    <section class="registro-panel registro-formulario">
                        <h2>Registrar un producto</h2>

                        <form action="registro_compra.php" method="get" id="formularioAgregarProducto">
                            <label class="campo-registro campo-registro--completo">
                                <span>Nombre del producto <b>*</b></span>
                                <input type="text" value="Módulo Lora">
                            </label>

                            <label class="campo-registro">
                                <span>Cantidad <b>*</b></span>
                                <input type="number" value="1">
                            </label>

                            <label class="campo-registro">
                                <span>Unidad <b>*</b></span>
                                <select>
                                    <option>Seleccionar unidad</option>
                                    <option>Unidades</option>
                                    <option>Rollos</option>
                                    <option>Cajas</option>
                                </select>
                            </label>

                            <label class="campo-registro">
                                <span>Código <b>*</b></span>
                                <input type="text" value="LORA-SX1278">
                            </label>

                            <label class="campo-registro">
                                <span>Categoría <b>*</b></span>
                                <input type="text" value="Modul" list="categoriasProducto">
                                <datalist id="categoriasProducto">
                                    <option value="Módulos LoRa">
                                    <option value="Módulos WiFi">
                                    <option value="Módulos y redes">
                                </datalist>
                            </label>

                            <label class="campo-registro campo-registro--completo">
                                <span>Descripción (Opcional)</span>
                                <textarea placeholder="Ej: Prototipo IoT"></textarea>
                            </label>

                            <div class="campo-registro campo-registro--completo">
                                <span>Imagen del producto (Opcional)</span>
                                <button class="registro-carga-imagen" type="button">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <span>Arrastra una imagen aquí o haz click para abrirlo</span>
                                    <small>Formatos aceptados: JPG, PNG O WEBP</small>
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Acciones temporales del formulario -->
                    <div class="registro-formulario__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <button class="registro-boton registro-boton--primario" type="submit" form="formularioAgregarProducto">
                            <span>Agregar producto</span>
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Panel temporal de posibles coincidencias -->
                <aside class="registro-panel coincidencias">
                    <header class="registro-panel__titulo registro-panel__titulo--azul">
                        <h2>Posibles coincidencias</h2>
                    </header>

                    <div class="coincidencias__alerta">
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Hemos encontrado productos similares en el sistema. <span>Selecciona si quieres usar sus datos o continúa manualmente.</span></p>
                    </div>

                    <div class="coincidencias__lista">
                        <label class="coincidencia coincidencia--activa">
                            <input type="radio" name="coincidencia" checked>
                            <span class="coincidencia__imagen producto-imagen--verde"><i class="bi bi-cpu"></i></span>
                            <span>
                                <strong>Módulo LoRa RFM95W 915MHz</strong>
                                <small>SKU: LORA-RFM95W-915 | Categoría: Módulos LoRa</small>
                            </span>
                        </label>
                        <label class="coincidencia">
                            <input type="radio" name="coincidencia">
                            <span class="coincidencia__imagen producto-imagen--oliva"><i class="bi bi-router"></i></span>
                            <span>
                                <strong>Módulo LoRa SX1278 433MHz</strong>
                                <small>SKU: LORA-SX1278-433 | Categoría: Módulos LoRa</small>
                            </span>
                        </label>
                        <label class="coincidencia">
                            <input type="radio" name="coincidencia">
                            <span class="coincidencia__imagen producto-imagen--azul"><i class="bi bi-motherboard"></i></span>
                            <span>
                                <strong>ESP8266 NodeMCU</strong>
                                <small>SKU: NODEMCU-ESP8266 | Categoría: Módulos Wifi</small>
                            </span>
                        </label>
                    </div>
                </aside>
            </div>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
</body>
</html>
