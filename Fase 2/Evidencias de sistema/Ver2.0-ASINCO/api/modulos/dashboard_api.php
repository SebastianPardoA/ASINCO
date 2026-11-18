<?php
/**
 * dashboard_api.php
 *
 * Consulta indicadores, compras, gastos por período y calificaciones para el dashboard
 */

/* Devuelve el resumen solicitado con períodos validados antes de consultar la base de datos */
function obtenerDatosDashboard(PDO $conexion, int $meses = 6, string $mesEstados = ''): array
{
    $meses = in_array($meses, [3, 6, 12], true) ? $meses : 6;
    $mesActual = new DateTimeImmutable(date('Y-m-01'));
    if ($mesEstados === '') {
        $mesEstados = $mesActual->format('Y-m');
    }
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mesEstados)) {
        throw new InvalidArgumentException('El mes seleccionado no es válido.');
    }
    $inicioMesEstados = new DateTimeImmutable($mesEstados . '-01');
    if ($inicioMesEstados->format('Y-m') !== $mesEstados) {
        throw new InvalidArgumentException('El mes seleccionado no es válido.');
    }

    $inicioMes = $mesActual->modify('-' . ($meses - 1) . ' months');
    $finMeses = $mesActual->modify('+1 month');
    $inicioMesAnterior = $mesActual->modify('-1 month');
    $subconsultaCompras = dashboardSubconsultaCompras();

    $consultaProductos = $conexion->query('SELECT COUNT(*) FROM productos');
    $productos = (int) $consultaProductos->fetchColumn();

    $consultaIndicadores = $conexion->prepare(
        'SELECT
            SUM(CASE WHEN LOWER(REPLACE(estado, \' \', \'_\')) IN (\'en_transito\', \'atrasada\') THEN 1 ELSE 0 END) AS compras_en_llegada,
            SUM(CASE WHEN LOWER(REPLACE(estado, \' \', \'_\')) IN (\'registrada\', \'pendiente\', \'confirmada\', \'confirmado\', \'en_preparacion\', \'en_preparación\') THEN 1 ELSE 0 END) AS compras_pendientes,
            SUM(CASE WHEN LOWER(REPLACE(estado, \' \', \'_\')) IN (\'registrada\', \'pendiente\', \'confirmada\', \'confirmado\', \'en_preparacion\', \'en_preparación\') THEN
                CASE WHEN moneda = \'CLP\' THEN subtotal_productos + costo_envio
                     WHEN moneda = \'USD\' AND tipo_cambio_usd_clp > 0 THEN (subtotal_productos + costo_envio) * tipo_cambio_usd_clp
                     ELSE 0 END
                ELSE 0 END) AS monto_pendiente_clp,
            SUM(CASE WHEN LOWER(REPLACE(estado, \' \', \'_\')) IN (\'registrada\', \'pendiente\', \'confirmada\', \'confirmado\', \'en_preparacion\', \'en_preparación\')
                      AND moneda = \'USD\' AND (tipo_cambio_usd_clp IS NULL OR tipo_cambio_usd_clp <= 0) THEN 1 ELSE 0 END) AS pendientes_sin_tasa
         FROM (' . $subconsultaCompras . ') compras_resumen'
    );
    $consultaIndicadores->execute();
    $indicadores = $consultaIndicadores->fetch() ?: [];

    $consultaGasto = $conexion->prepare(
        'SELECT
            SUM(CASE WHEN moneda = \'CLP\' THEN subtotal_productos + costo_envio
                     WHEN moneda = \'USD\' AND tipo_cambio_usd_clp > 0 THEN (subtotal_productos + costo_envio) * tipo_cambio_usd_clp
                     ELSE 0 END) AS gasto_clp,
            SUM(CASE WHEN moneda = \'USD\' AND (tipo_cambio_usd_clp IS NULL OR tipo_cambio_usd_clp <= 0) THEN 1 ELSE 0 END) AS compras_sin_tasa
         FROM (' . $subconsultaCompras . ') compras_resumen
         WHERE fecha_compra >= :inicio_mes_anterior AND fecha_compra < :inicio_mes_actual
           AND LOWER(estado) <> \'cancelada\''
    );
    $consultaGasto->execute([
        ':inicio_mes_anterior' => $inicioMesAnterior->format('Y-m-d'),
        ':inicio_mes_actual' => $mesActual->format('Y-m-d'),
    ]);
    $gasto = $consultaGasto->fetch() ?: [];

    $inicioReferenciaGasto = $mesActual->modify('-4 months');
    $consultaReferenciaGasto = $conexion->prepare(
        'SELECT
            SUM(CASE WHEN moneda = \'CLP\' THEN subtotal_productos + costo_envio
                     WHEN moneda = \'USD\' AND tipo_cambio_usd_clp > 0 THEN (subtotal_productos + costo_envio) * tipo_cambio_usd_clp
                     ELSE 0 END) AS gasto_clp,
            SUM(CASE WHEN moneda = \'CLP\' OR (moneda = \'USD\' AND tipo_cambio_usd_clp > 0) THEN 1 ELSE 0 END) AS compras_convertibles
         FROM (' . $subconsultaCompras . ') compras_resumen
         WHERE fecha_compra >= :inicio_referencia AND fecha_compra < :fin_referencia
           AND LOWER(estado) <> \'cancelada\''
    );
    $consultaReferenciaGasto->execute([
        ':inicio_referencia' => $inicioReferenciaGasto->format('Y-m-d'),
        ':fin_referencia' => $inicioMesAnterior->format('Y-m-d'),
    ]);
    $referenciaGasto = $consultaReferenciaGasto->fetch() ?: [];

    $consultaVencidas = $conexion->prepare(
        'SELECT COUNT(*)
         FROM compras c
         INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
         WHERE c.fecha_estimada_llegada IS NOT NULL
           AND c.fecha_estimada_llegada < :hoy
           AND c.fecha_recepcion IS NULL
           AND LOWER(e.nombre) <> \'cancelada\''
    );
    $consultaVencidas->execute([':hoy' => date('Y-m-d')]);
    $comprasVencidas = (int) $consultaVencidas->fetchColumn();

    $consultaStockBajo = $conexion->query(
        'SELECT COUNT(*)
         FROM (
            SELECT p.id_producto,
                   COALESCE(SUM(CASE WHEN ep.nombre = \'disponible\' THEN 1 ELSE 0 END), 0) AS stock_disponible
            FROM productos p
            LEFT JOIN compra_detalle cd ON cd.id_producto = p.id_producto
            LEFT JOIN unidades_inventario ui ON ui.id_detalle = cd.id_detalle
            LEFT JOIN estados_productos ep ON ep.id_estado_producto = ui.id_estado_producto
            WHERE p.activo = 1
            GROUP BY p.id_producto
            HAVING stock_disponible BETWEEN 1 AND 5
         ) productos_stock_bajo'
    );
    $productosStockBajo = (int) $consultaStockBajo->fetchColumn();

    $consultaTransito = $conexion->prepare(
        'SELECT
            AVG(CASE WHEN c.fecha_recepcion >= :inicio_actual AND c.fecha_recepcion < :fin_actual
                     THEN DATEDIFF(c.fecha_recepcion, c.fecha_compra) END) AS transito_actual,
            AVG(CASE WHEN c.fecha_recepcion >= :inicio_anterior AND c.fecha_recepcion < :fin_anterior
                     THEN DATEDIFF(c.fecha_recepcion, c.fecha_compra) END) AS transito_anterior
         FROM compras c
         INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
         WHERE c.fecha_recepcion IS NOT NULL
           AND c.fecha_compra IS NOT NULL
           AND DATEDIFF(c.fecha_recepcion, c.fecha_compra) >= 0
           AND LOWER(e.nombre) <> \'cancelada\'
           AND ((c.fecha_recepcion >= :inicio_actual_filtro AND c.fecha_recepcion < :fin_actual_filtro)
             OR (c.fecha_recepcion >= :inicio_anterior_filtro AND c.fecha_recepcion < :fin_anterior_filtro))'
    );
    $consultaTransito->execute([
        ':inicio_actual' => $mesActual->format('Y-m-d'),
        ':fin_actual' => $mesActual->modify('+1 month')->format('Y-m-d'),
        ':inicio_anterior' => $inicioMesAnterior->format('Y-m-d'),
        ':fin_anterior' => $mesActual->format('Y-m-d'),
        ':inicio_actual_filtro' => $mesActual->format('Y-m-d'),
        ':fin_actual_filtro' => $mesActual->modify('+1 month')->format('Y-m-d'),
        ':inicio_anterior_filtro' => $inicioMesAnterior->format('Y-m-d'),
        ':fin_anterior_filtro' => $mesActual->format('Y-m-d'),
    ]);
    $transito = $consultaTransito->fetch() ?: [];

    $consultaGastosMensuales = $conexion->prepare(
        'SELECT
            DATE_FORMAT(fecha_compra, \'%Y-%m\') AS mes,
            SUM(CASE WHEN moneda = \'CLP\' THEN subtotal_productos + costo_envio
                     WHEN moneda = \'USD\' AND tipo_cambio_usd_clp > 0 THEN (subtotal_productos + costo_envio) * tipo_cambio_usd_clp
                     ELSE 0 END) AS total_clp,
            SUM(CASE WHEN moneda = \'USD\' AND (tipo_cambio_usd_clp IS NULL OR tipo_cambio_usd_clp <= 0) THEN 1 ELSE 0 END) AS compras_sin_tasa
         FROM (' . $subconsultaCompras . ') compras_resumen
         WHERE fecha_compra >= :inicio AND fecha_compra < :fin
           AND LOWER(estado) <> \'cancelada\'
         GROUP BY DATE_FORMAT(fecha_compra, \'%Y-%m\')'
    );
    $consultaGastosMensuales->execute([
        ':inicio' => $inicioMes->format('Y-m-d'),
        ':fin' => $finMeses->format('Y-m-d'),
    ]);
    $gastosPorMes = [];
    foreach ($consultaGastosMensuales->fetchAll() as $fila) {
        $gastosPorMes[$fila['mes']] = [
            'total_clp' => (float) $fila['total_clp'],
            'compras_sin_tasa' => (int) $fila['compras_sin_tasa'],
        ];
    }
    $gastosMensuales = [];
    for ($indice = 0; $indice < $meses; $indice++) {
        $mes = $inicioMes->modify('+' . $indice . ' months')->format('Y-m');
        $gastosMensuales[] = [
            'mes' => $mes,
            'total_clp' => $gastosPorMes[$mes]['total_clp'] ?? 0,
            'compras_sin_tasa' => $gastosPorMes[$mes]['compras_sin_tasa'] ?? 0,
        ];
    }

    $consultaRecientes = $conexion->query(
        'SELECT
            id_compra, codigo_compra, fecha_compra, proveedor, productos, moneda, estado, estado_color,
            CASE WHEN moneda = \'CLP\' THEN subtotal_productos + costo_envio
                 WHEN moneda = \'USD\' AND tipo_cambio_usd_clp > 0 THEN (subtotal_productos + costo_envio) * tipo_cambio_usd_clp
                 ELSE NULL END AS total_clp,
            CASE WHEN moneda = \'USD\' AND (tipo_cambio_usd_clp IS NULL OR tipo_cambio_usd_clp <= 0) THEN 1 ELSE 0 END AS sin_tasa
         FROM (' . $subconsultaCompras . ') compras_resumen
         ORDER BY fecha_compra DESC, id_compra DESC
         LIMIT 5'
    );
    $comprasRecientes = array_map(static function (array $compra): array {
        return [
            'id_compra' => (int) $compra['id_compra'],
            'codigo_compra' => $compra['codigo_compra'],
            'fecha_compra' => $compra['fecha_compra'],
            'proveedor' => $compra['proveedor'],
            'productos' => (int) $compra['productos'],
            'total_clp' => $compra['total_clp'] !== null ? (float) $compra['total_clp'] : null,
            'sin_tasa' => (bool) $compra['sin_tasa'],
            'estado' => $compra['estado'],
            'estado_color' => $compra['estado_color'],
        ];
    }, $consultaRecientes->fetchAll());

    $finMesEstados = $inicioMesEstados->modify('+1 month');
    $consultaEstados = $conexion->prepare(
        'SELECT e.nombre, e.color, COUNT(c.id_compra) AS total
         FROM estados_compras e
         LEFT JOIN compras c ON c.id_estado_compra = e.id_estado_compra
             AND c.fecha_compra >= :inicio AND c.fecha_compra < :fin
         WHERE e.activo = 1
           AND LOWER(REPLACE(e.nombre, \' \', \'_\')) IN (\'recibida\', \'recibido\', \'registrada\', \'confirmada\', \'confirmado\', \'en_preparacion\', \'en_preparación\', \'atrasada\')
         GROUP BY e.id_estado_compra, e.nombre, e.color
         ORDER BY e.id_estado_compra'
    );
    $consultaEstados->execute([
        ':inicio' => $inicioMesEstados->format('Y-m-d'),
        ':fin' => $finMesEstados->format('Y-m-d'),
    ]);
    $comprasPorEstado = array_map(static function (array $estado): array {
        return [
            'nombre' => $estado['nombre'],
            'color' => $estado['color'],
            'total' => (int) $estado['total'],
        ];
    }, $consultaEstados->fetchAll());

    return [
        'resumen' => [
            'productos_registrados' => $productos,
            'gasto_ultimo_mes_clp' => (float) ($gasto['gasto_clp'] ?? 0),
            'mes_gasto' => $inicioMesAnterior->format('Y-m'),
            'compras_en_llegada' => (int) ($indicadores['compras_en_llegada'] ?? 0),
            'compras_pendientes' => (int) ($indicadores['compras_pendientes'] ?? 0),
            'monto_pendiente_clp' => (float) ($indicadores['monto_pendiente_clp'] ?? 0),
            'pendientes_sin_tasa' => (int) ($indicadores['pendientes_sin_tasa'] ?? 0),
            'gasto_sin_tasa' => (int) ($gasto['compras_sin_tasa'] ?? 0),
        ],
        'gastos_mensuales' => $gastosMensuales,
        'compras_por_estado' => $comprasPorEstado,
        'mes_estados' => $mesEstados,
        'compras_recientes' => $comprasRecientes,
        'alertas' => [
            'compras_vencidas' => $comprasVencidas,
            'productos_stock_bajo' => $productosStockBajo,
            'transito_promedio_actual' => $transito['transito_actual'] !== null ? (float) $transito['transito_actual'] : null,
            'transito_promedio_anterior' => $transito['transito_anterior'] !== null ? (float) $transito['transito_anterior'] : null,
            'gasto_ultimo_mes_clp' => (float) ($gasto['gasto_clp'] ?? 0),
            'gasto_promedio_clp' => (int) ($referenciaGasto['compras_convertibles'] ?? 0) > 0
                ? (float) ($referenciaGasto['gasto_clp'] ?? 0) / 3
                : null,
            'comparacion_gasto' => (int) ($referenciaGasto['compras_convertibles'] ?? 0) === 0
                ? null
                : (((float) ($gasto['gasto_clp'] ?? 0)) < (((float) ($referenciaGasto['gasto_clp'] ?? 0)) / 3)
                    ? 'menor'
                    : (((float) ($gasto['gasto_clp'] ?? 0)) > (((float) ($referenciaGasto['gasto_clp'] ?? 0)) / 3) ? 'mayor' : 'igual')),
        ],
        'mejores_proveedores' => dashboardListarEvaluaciones($conexion, 'proveedores', 'evaluaciones_proveedores', 'id_proveedor', 'id_compra', false),
        'peores_proveedores' => dashboardListarEvaluaciones($conexion, 'proveedores', 'evaluaciones_proveedores', 'id_proveedor', 'id_compra', true),
        'mejores_transportistas' => dashboardListarEvaluaciones($conexion, 'transportistas', 'evaluaciones_transportistas', 'id_transportista', 'id_compra', false),
        'peores_transportistas' => dashboardListarEvaluaciones($conexion, 'transportistas', 'evaluaciones_transportistas', 'id_transportista', 'id_compra', true),
    ];
}

/* Construye la consulta base agregada por compra que reutilizan los indicadores del dashboard */
function dashboardSubconsultaCompras(): string
{
    return 'SELECT
            c.id_compra, c.codigo_compra, c.fecha_compra, c.fecha_estimada_llegada,
            c.costo_envio, c.tipo_cambio_usd_clp, m.codigo AS moneda,
            e.nombre AS estado, e.color AS estado_color, p.nombre AS proveedor,
            COUNT(DISTINCT d.id_producto) AS productos,
            COALESCE(SUM(d.cantidad), 0) AS unidades,
            COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS subtotal_productos
        FROM compras c
        INNER JOIN monedas m ON m.id_moneda = c.id_moneda
        INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
        INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
        LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra
        GROUP BY c.id_compra, c.codigo_compra, c.fecha_compra, c.fecha_estimada_llegada,
            c.costo_envio, c.tipo_cambio_usd_clp, m.codigo, e.nombre, e.color, p.nombre';
}

/* Obtiene los tres mejores y peores proveedores o transportistas evaluados según sus compras */
function dashboardListarEvaluaciones(PDO $conexion, string $tablaEntidad, string $tablaEvaluacion, string $campoEntidad, string $campoCompra, bool $peores): array
{
    $tablasPermitidas = [
        'proveedores' => ['evaluaciones_proveedores', 'id_proveedor'],
        'transportistas' => ['evaluaciones_transportistas', 'id_transportista'],
    ];
    if (!isset($tablasPermitidas[$tablaEntidad]) || $tablasPermitidas[$tablaEntidad] !== [$tablaEvaluacion, $campoEntidad] || $campoCompra !== 'id_compra') {
        throw new InvalidArgumentException('Ranking del dashboard no válido.');
    }

    $direccion = $peores ? 'ASC' : 'DESC';
    $consulta = $conexion->query(
        'SELECT entidad.' . $campoEntidad . ' AS id, entidad.nombre,
                ROUND(AVG(evaluacion.calificacion), 1) AS calificacion,
                COUNT(evaluacion.id_evaluacion) AS evaluaciones
         FROM ' . $tablaEntidad . ' entidad
         INNER JOIN compras compra ON compra.' . $campoEntidad . ' = entidad.' . $campoEntidad . '
         INNER JOIN ' . $tablaEvaluacion . ' evaluacion ON evaluacion.' . $campoCompra . ' = compra.id_compra
         GROUP BY entidad.' . $campoEntidad . ', entidad.nombre
         ORDER BY AVG(evaluacion.calificacion) ' . $direccion . ', COUNT(evaluacion.id_evaluacion) DESC, entidad.nombre ASC
         LIMIT 3'
    );

    return array_map(static function (array $fila): array {
        return [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'calificacion' => (float) $fila['calificacion'],
            'evaluaciones' => (int) $fila['evaluaciones'],
        ];
    }, $consulta->fetchAll());
}
