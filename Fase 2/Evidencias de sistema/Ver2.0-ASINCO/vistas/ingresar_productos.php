<?php
/**
 * ingresar_productos.php
 *
 * Muestra el formulario para agregar o editar productos solicitados en una compra
 */

require_once __DIR__ . '/../configuracion/permisos.php';
exigirAccesoVista('ingresar_productos');

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
    <link rel="stylesheet" href="../recursos/css/registro_compra.css?v=20261112-5">
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

                        <form id="formularioAgregarProducto" data-formulario-agregar-producto novalidate>
                            <label class="campo-registro campo-registro--completo">
                                <span>Nombre del producto <b>*</b></span>
                                <input type="text" name="nombre" maxlength="200" autocomplete="off" required data-producto-nombre>
                            </label>

                            <label class="campo-registro">
                                <span>Cantidad <b>*</b></span>
                                <input type="number" name="cantidad" min="1" step="1" value="1" required data-producto-cantidad>
                            </label>

                            <label class="campo-registro">
                                <span>Unidad <b>*</b></span>
                                <div class="campo-registro__combobox" data-unidad-combobox>
                                    <input type="text" name="unidad_medida" maxlength="50" placeholder="Ej: Unidades" autocomplete="off" required data-producto-unidad role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="opcionesUnidadesProducto" aria-expanded="false">
                                    <div class="campo-registro__opciones" id="opcionesUnidadesProducto" role="listbox" data-unidades-medida hidden></div>
                                </div>
                            </label>

                            <label class="campo-registro">
                                <span>Código <b>*</b></span>
                                <input type="text" name="codigo_producto" maxlength="80" required data-producto-codigo>
                            </label>

                            <label class="campo-registro">
                                <span>Categoría <b>*</b></span>
                                <div class="campo-registro__combobox" data-categoria-combobox>
                                    <input type="text" name="categoria" maxlength="100" autocomplete="off" required data-producto-categoria role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="opcionesCategoriasProducto" aria-expanded="false">
                                    <div class="campo-registro__opciones" id="opcionesCategoriasProducto" role="listbox" data-categorias-producto hidden></div>
                                </div>
                            </label>

                            <label class="campo-registro campo-registro--completo">
                                <span>Descripción (Opcional)</span>
                                <textarea name="descripcion" maxlength="2000" placeholder="Ej: Prototipo IoT" data-producto-descripcion></textarea>
                            </label>

                            <div class="campo-registro campo-registro--completo">
                                <span>Imagen del producto (Opcional)</span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" data-producto-archivo hidden>
                                <button class="registro-carga-imagen" type="button" data-producto-seleccionar-imagen>
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <span data-producto-imagen-texto>Seleccionar una imagen</span>
                                    <small>Formatos aceptados: JPG, PNG o WEBP. Máximo 1 MB.</small>
                                </button>
                                <img class="registro-imagen-preview" data-producto-imagen-preview alt="Vista previa de la imagen seleccionada" hidden>
                            </div>
                            <p class="registro-formulario__mensaje campo-registro--completo" data-producto-mensaje role="status" aria-live="polite"></p>
                        </form>
                    </section>

                    <!-- Acciones para guardar o cancelar el producto solicitado -->
                    <div class="registro-formulario__acciones">
                        <a class="registro-boton registro-boton--gris" href="registro_compra.php">
                            <i class="bi bi-arrow-left"></i>
                            <span>Volver al paso anterior</span>
                        </a>
                        <button class="registro-boton registro-boton--primario" type="submit" form="formularioAgregarProducto" data-producto-guardar>
                            <span>Agregar producto</span>
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Coincidencias del catálogo por nombre y categoría -->
                <aside class="registro-panel coincidencias">
                    <header class="registro-panel__titulo registro-panel__titulo--azul">
                        <h2>Posibles coincidencias</h2>
                    </header>

                    <div class="coincidencias__alerta" data-coincidencias-alerta>
                        <i class="bi bi-exclamation-circle"></i>
                        <p>Las coincidencias del catálogo aparecerán aquí. <span data-coincidencias-mensaje>Ingresa un nombre o una categoría para buscar productos similares.</span></p>
                    </div>

                    <div class="coincidencias__lista" data-coincidencias-lista role="radiogroup" aria-label="Productos similares"></div>
                </aside>
            </div>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
    <script src="../recursos/js/registro_compra.js?v=20261112-10" defer></script>
</body>
</html>
