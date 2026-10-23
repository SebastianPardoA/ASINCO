<?php
/**
 * dashboard.php
 *
 * Muestra la vista principal del sistema ASINCO después del ingreso
 */

$vistaActiva = 'dashboard';
$tituloBarraSuperior = 'Dashboard';
$subtituloBarraSuperior = 'Resumen general del sistema y estado actual de abastecimiento y compras.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Dashboard</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/dashboard.css">
</head>
<body class="dashboard-pagina">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal del dashboard con información temporal -->
    <main class="dashboard-contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <div class="dashboard-acciones-superiores">

            <!-- Selector visual de fecha para el resumen del dashboard -->
            <button class="dashboard-fecha" type="button">
                <i class="bi bi-calendar3"></i>
                <span>20 de agosto de 2026</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>

        <!-- Indicadores principales del sistema -->
        <section class="dashboard-indicadores" aria-label="Indicadores principales">
            <article class="indicador">
                <div class="indicador__icono indicador__icono--azul">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <span>Productos registrados</span>
                    <strong>2,458</strong>
                    <small class="texto-exito">↑ 5.2% vs mes anterior</small>
                </div>
            </article>

            <article class="indicador">
                <div class="indicador__icono indicador__icono--verde">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                    <span>Gasto del último mes</span>
                    <strong>USD 128,540</strong>
                    <small class="texto-exito">↓ 8.7% vs el mes anterior</small>
                </div>
            </article>

            <article class="indicador">
                <div class="indicador__icono indicador__icono--amarillo">
                    <i class="bi bi-airplane"></i>
                </div>
                <div>
                    <span>Compras en proceso de llegar</span>
                    <strong>18</strong>
                    <small>En tránsito internacional</small>
                </div>
            </article>

            <article class="indicador">
                <div class="indicador__icono indicador__icono--morado">
                    <i class="bi bi-card-checklist"></i>
                </div>
                <div>
                    <span>Compras pendientes</span>
                    <strong>12</strong>
                    <small>Por un valor de USD 75,300</small>
                </div>
            </article>
        </section>

        <!-- Tabla y gráfico principal del dashboard -->
        <section class="dashboard-grid dashboard-grid--principal">
            <article class="panel dashboard-tabla">
                <div class="panel__titulo">
                    <h2>Compras recientes</h2>
                </div>

                <div class="tabla-contenedor">
                    <table>
                        <thead>
                            <tr>
                                <th>Id Compra</th>
                                <th>Proveedor</th>
                                <th>Producto(s)</th>
                                <th>Valor (USD)</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>OC-2026-0418-01</td>
                                <td>ElectroMakers Chile</td>
                                <td>8</td>
                                <td>12.450,00</td>
                                <td><span class="estado estado--transito">En tránsito</span></td>
                                <td>18/05/2026</td>
                            </tr>
                            <tr>
                                <td>OC-2026-0418-02</td>
                                <td>Arduino Chile</td>
                                <td>15</td>
                                <td>23.680,00</td>
                                <td><span class="estado estado--transito">En tránsito</span></td>
                                <td>18/05/2026</td>
                            </tr>
                            <tr>
                                <td>OC-2026-0418-03</td>
                                <td>Raspberry Pi Andina</td>
                                <td>8</td>
                                <td>8.450,00</td>
                                <td><span class="estado estado--pendiente">Pendiente</span></td>
                                <td>18/05/2026</td>
                            </tr>
                            <tr>
                                <td>OC-2026-0418-04</td>
                                <td>Asia Tech Supply Co.</td>
                                <td>12</td>
                                <td>17.320,00</td>
                                <td><span class="estado estado--pendiente">Pendiente</span></td>
                                <td>18/05/2026</td>
                            </tr>
                            <tr>
                                <td>OC-2026-0418-05</td>
                                <td>Insumos Ti SpA</td>
                                <td>4</td>
                                <td>4.230,00</td>
                                <td><span class="estado estado--recibido">Recibido</span></td>
                                <td>18/05/2026</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel dashboard-grafico">
                <div class="panel__titulo panel__titulo--acciones">
                    <h2>Gastos mensual (USD)</h2>
                    <select aria-label="Período del gráfico">
                        <option>Últimos 6 MESES</option>
                    </select>
                </div>

                <!-- Gráfico temporal -->
                <div class="grafico-linea" aria-label="Gráfico de gastos mensuales de ejemplo">
                    <div class="grafico-linea__area">
                        <span class="grafico-linea__guia"></span>
                        <span class="grafico-linea__guia"></span>
                        <span class="grafico-linea__guia"></span>
                        <span class="grafico-linea__punto" style="left: 4%; bottom: 35%;">96,400</span>
                        <span class="grafico-linea__punto" style="left: 21%; bottom: 42%;">110,250</span>
                        <span class="grafico-linea__punto" style="left: 40%; bottom: 50%;">142,300</span>
                        <span class="grafico-linea__punto" style="left: 59%; bottom: 62%;">160,890</span>
                        <span class="grafico-linea__punto" style="left: 78%; bottom: 48%;">140,760</span>
                        <span class="grafico-linea__punto" style="left: 95%; bottom: 43%;">128,540</span>
                    </div>
                    <div class="grafico-linea__meses">
                        <span>Dic 2025</span>
                        <span>Ene 2026</span>
                        <span>Feb 2026</span>
                        <span>Mar 2026</span>
                        <span>Abr 2026</span>
                        <span>May 2026</span>
                    </div>
                </div>
            </article>
        </section>

        <!-- Resumenes secundarios del dashboard -->
        <section class="dashboard-grid dashboard-grid--secundario">
            <article class="panel panel-dona">
                <div class="panel__titulo panel__titulo--acciones panel__titulo--claro">
                    <h2>Compras por estado</h2>
                    <select aria-label="Período de compras por estado">
                        <option>Este mes</option>
                    </select>
                </div>
                <div class="dona-resumen">
                    <div class="dona"></div>
                    <ul>
                        <li><span class="leyenda leyenda--verde"></span> En tránsito <small>45% (18)</small></li>
                        <li><span class="leyenda leyenda--celeste"></span> Pendiente <small>30% (12)</small></li>
                        <li><span class="leyenda leyenda--amarilla"></span> Recibida <small>15% (6)</small></li>
                        <li><span class="leyenda leyenda--azul"></span> Cancelada <small>10% (4)</small></li>
                    </ul>
                </div>
            </article>

            <article class="panel ranking">
                <h2>Mejores proveedores</h2>
                <ol>
                    <li><span>ElectroMakers Chile</span><strong>4.8 ★</strong></li>
                    <li><span>Raspberry Pi Andina</span><strong>4.6 ★</strong></li>
                    <li><span>MakerLab Componentes</span><strong>4.5 ★</strong></li>
                </ol>
                <a href="proveedores.php">Ver todos los proveedores</a>
            </article>

            <article class="panel ranking">
                <h2>Peores proveedores</h2>
                <ol>
                    <li><span>XYZ Importaciones Ltda.</span><strong>4.8 ★</strong></li>
                    <li><span>Mega Parts Co.</span><strong>4.6 ★</strong></li>
                    <li><span>Novatech Distribuciones</span><strong>4.5 ★</strong></li>
                </ol>
                <a href="proveedores.php">Ver todos los proveedores</a>
            </article>

            <article class="panel ranking">
                <h2>Mejores despachos</h2>
                <ol>
                    <li><span>AndesTech Logística</span><strong>4.7 ★</strong></li>
                    <li><span>TecnoCarga Express</span><strong>4.5 ★</strong></li>
                    <li><span>Circuito Courier</span><strong>4.3 ★</strong></li>
                </ol>
                <a href="metodos_despacho.php">Ver todos los métodos</a>
            </article>

            <article class="panel ranking">
                <h2>Peores despachos</h2>
                <ol>
                    <li><span>TransOceanic Cargo</span><strong>2.2 ★</strong></li>
                    <li><span>SpeedLine Logistics</span><strong>2.4 ★</strong></li>
                    <li><span>Global Shipping Intl</span><strong>2.6 ★</strong></li>
                </ol>
                <a href="metodos_despacho.php">Ver todos los métodos</a>
            </article>
        </section>

        <!-- Alertas temporales del estado -->
        <section class="dashboard-alertas" aria-label="Alertas del sistema">
            <article class="alerta alerta--roja">
                <span>!</span>
                <div>
                    <h2>Compras vencidas</h2>
                    <p>Tienes 3 compras con más de 7 días de atraso en la fecha estimada de llegada.</p>
                    <a href="historial_compras.php">Ver compras</a>
                </div>
            </article>

            <article class="alerta alerta--amarilla">
                <span>!</span>
                <div>
                    <h2>Productos por debajo del mínimo</h2>
                    <p>28 productos están por debajo del stock mínimo definido.</p>
                    <a href="inventario.php">Ver inventario</a>
                </div>
            </article>

            <article class="alerta alerta--azul">
                <span>!</span>
                <div>
                    <h2>Tiempo de tránsito altos</h2>
                    <p>El tiempo promedio de tránsito este mes aumentó 12% vs. el mes anterior.</p>
                    <a href="#">Ver reporte</a>
                </div>
            </article>

            <article class="alerta alerta--verde">
                <span>✓</span>
                <div>
                    <h2>Ahorro estimado</h2>
                    <p>Has ahorrado USD 9,540 en compras este mes comparado con el promedio de los últimos 3 meses.</p>
                    <a href="#">Ver análisis</a>
                </div>
            </article>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
</body>
</html>
