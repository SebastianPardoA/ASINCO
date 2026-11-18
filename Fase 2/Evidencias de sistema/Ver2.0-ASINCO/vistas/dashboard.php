<?php
/**
 * dashboard.php
 *
 * Presenta indicadores, compras recientes, gastos y calificaciones del sistema
 */

require_once __DIR__ . '/../configuracion/permisos.php';
exigirAccesoVista('dashboard');

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
    <script src="../recursos/js/dashboard.js" defer></script>
</head>
<body class="dashboard-pagina">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Paneles con información cargada desde la API del dashboard -->
    <main class="dashboard-contenido" data-dashboard>
        <?php include 'componentes/barra_superior.php'; ?>

        <p class="dashboard-mensaje" data-dashboard-mensaje role="status" aria-live="polite" hidden></p>

        <!-- Indicadores generales de productos y compras -->
        <section class="dashboard-indicadores" aria-label="Indicadores principales">
            <article class="indicador">
                <div class="indicador__icono indicador__icono--azul"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                <div><span>Productos registrados</span><strong data-dashboard-productos>—</strong><small>Productos en el catálogo</small></div>
            </article>
            <article class="indicador">
                <div class="indicador__icono indicador__icono--verde"><i class="bi bi-currency-dollar" aria-hidden="true"></i></div>
                <div><span>Gasto del último mes</span><strong data-dashboard-gasto>—</strong><small data-dashboard-periodo>Mes calendario anterior · CLP</small></div>
            </article>
            <article class="indicador">
                <div class="indicador__icono indicador__icono--amarillo"><i class="bi bi-truck" aria-hidden="true"></i></div>
                <div><span>Compras en proceso de llegada</span><strong data-dashboard-llegada>—</strong><small>En tránsito o atrasadas</small></div>
            </article>
            <article class="indicador">
                <div class="indicador__icono indicador__icono--morado"><i class="bi bi-card-checklist" aria-hidden="true"></i></div>
                <div><span>Compras pendientes</span><strong data-dashboard-pendientes>—</strong><small data-dashboard-monto-pendiente>Valor pendiente en CLP: —</small></div>
            </article>
        </section>

        <!-- Tabla de compras recientes y evolución del gasto mensual -->
        <section class="dashboard-grid dashboard-grid--principal">
            <article class="panel dashboard-tabla">
                <div class="panel__titulo"><h2>Compras recientes</h2></div>
                <div class="tabla-contenedor">
                    <table>
                        <thead><tr><th>Id compra</th><th>Proveedor</th><th>Productos</th><th>Valor (CLP)</th><th>Estado</th><th>Fecha</th></tr></thead>
                        <tbody data-dashboard-recientes><tr><td colspan="6" class="dashboard-vacio">Cargando compras…</td></tr></tbody>
                    </table>
                </div>
            </article>

            <article class="panel dashboard-grafico">
                <div class="panel__titulo panel__titulo--acciones">
                    <h2>Gastos mensuales (CLP)</h2>
                    <label class="dashboard-selector"><span class="solo-lectores">Período del gráfico</span>
                        <select data-dashboard-periodo-grafico aria-label="Período de gastos mensuales">
                            <option value="6" selected>Últimos 6 meses</option>
                            <option value="3">Últimos 3 meses</option>
                            <option value="12">Últimos 12 meses</option>
                        </select>
                    </label>
                </div>
                <div class="grafico-linea" data-dashboard-grafico-linea role="img" aria-label="Gráfico de gastos mensuales">
                    <p class="dashboard-vacio">Cargando gráfico…</p>
                </div>
                <p class="dashboard-nota" data-dashboard-nota-tasa hidden></p>
            </article>
        </section>

        <!-- Distribución mensual por estado y rankings calculados con evaluaciones -->
        <section class="dashboard-grid dashboard-grid--secundario">
            <article class="panel panel-dona">
                <div class="panel__titulo panel__titulo--acciones panel__titulo--claro">
                    <h2>Compras por estado</h2>
                    <label class="dashboard-selector"><span class="solo-lectores">Mes del gráfico</span>
                        <select data-dashboard-mes aria-label="Mes de compras por estado"></select>
                    </label>
                </div>
                <div class="dona-resumen" data-dashboard-resumen-estados>
                    <div class="dona" data-dashboard-grafico-dona role="img" aria-label="Gráfico de compras por estado"></div>
                    <ul data-dashboard-leyenda-estados><li class="dashboard-vacio">Cargando estados…</li></ul>
                    <p class="dashboard-estados-vacio" data-dashboard-estados-vacio hidden>No hay compras en este mes.</p>
                </div>
            </article>

            <article class="panel ranking ranking--mejores">
                <div class="ranking__titulo"><span><i class="bi bi-people" aria-hidden="true"></i></span><div><h2>Mejores proveedores</h2><small>Según calificación registrada</small></div></div>
                <ol data-dashboard-mejores-proveedores><li class="dashboard-vacio">Cargando…</li></ol><a href="proveedores.php">Ver todos los proveedores</a>
            </article>
            <article class="panel ranking ranking--peores">
                <div class="ranking__titulo"><span><i class="bi bi-people" aria-hidden="true"></i></span><div><h2>Peores proveedores</h2><small>Según calificación registrada</small></div></div>
                <ol data-dashboard-peores-proveedores><li class="dashboard-vacio">Cargando…</li></ol><a href="proveedores.php">Ver todos los proveedores</a>
            </article>
            <article class="panel ranking ranking--mejores">
                <div class="ranking__titulo"><span><i class="bi bi-truck" aria-hidden="true"></i></span><div><h2>Mejores transportistas</h2><small>Según calificación registrada</small></div></div>
                <ol data-dashboard-mejores-transportistas><li class="dashboard-vacio">Cargando…</li></ol><a href="metodos_despacho.php">Ver todos los transportistas</a>
            </article>
            <article class="panel ranking ranking--peores">
                <div class="ranking__titulo"><span><i class="bi bi-truck" aria-hidden="true"></i></span><div><h2>Peores transportistas</h2><small>Según calificación registrada</small></div></div>
                <ol data-dashboard-peores-transportistas><li class="dashboard-vacio">Cargando…</li></ol><a href="metodos_despacho.php">Ver todos los transportistas</a>
            </article>
        </section>

        <!-- Alertas calculadas con vencimientos, niveles de stock y tendencias reales. -->
        <section class="dashboard-alertas" aria-label="Alertas del sistema">
            <article class="alerta alerta--roja">
                <span class="alerta__icono"><i class="bi bi-exclamation-diamond" aria-hidden="true"></i></span>
                <div><h2 data-dashboard-alerta-vencidas-titulo>Compras vencidas</h2><p data-dashboard-alerta-vencidas>Calculando…</p><a href="historial_compras.php">Ver compras</a></div>
            </article>
            <article class="alerta alerta--amarilla">
                <span class="alerta__icono"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                <div><h2 data-dashboard-alerta-stock-titulo>Productos con stock bajo</h2><p data-dashboard-alerta-stock>Calculando…</p><a href="inventario.php">Ver inventario</a></div>
            </article>
            <article class="alerta alerta--azul">
                <span class="alerta__icono"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
                <div><h2 data-dashboard-alerta-transito-titulo>Tiempo de tránsito</h2><p data-dashboard-alerta-transito>Calculando…</p><a href="historial_compras.php">Ver compras</a></div>
            </article>
            <article class="alerta alerta--verde">
                <span class="alerta__icono"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
                <div><h2 data-dashboard-alerta-gasto-titulo>Gasto vs. promedio</h2><p data-dashboard-alerta-gasto>Calculando…</p><a href="historial_compras.php">Ver historial</a></div>
            </article>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
</body>
</html>
