<?php
/**
 * inventario_api.php
 *
 * Consulta el inventario real y entrega resumen, filtros y productos paginados
 */

require_once __DIR__ . '/../../configuracion/imagenes.php';

/* Valida y normaliza los filtros recibidos para el inventario */
function normalizarFiltrosInventario(array $filtros): array
{
    $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('El texto de búsqueda es demasiado largo.');
    }

    $stock = trim((string) ($filtros['stock'] ?? ''));
    $stocksPermitidos = ['', 'con_stock', 'sin_stock', 'stock_bajo'];
    if (!in_array($stock, $stocksPermitidos, true)) {
        $stock = '';
    }

    return [
        'busqueda' => $busqueda,
        'id_categoria' => max(0, (int) ($filtros['id_categoria'] ?? 0)),
        'id_proveedor' => max(0, (int) ($filtros['id_proveedor'] ?? 0)),
        'id_estado_producto' => max(0, (int) ($filtros['id_estado_producto'] ?? 0)),
        'stock' => $stock,
    ];
}

/* Construye la consulta agrupada que calcula el stock por producto */
function construirConsultaAgrupadaInventario(array $filtros, array &$parametros): array
{
    $condiciones = ['p.activo = 1'];
    $parametros = [];

    if ($filtros['busqueda'] !== '') {
        $condiciones[] = '(p.nombre LIKE :busqueda_nombre OR p.codigo_producto LIKE :busqueda_codigo)';
        $parametros['busqueda_nombre'] = '%' . $filtros['busqueda'] . '%';
        $parametros['busqueda_codigo'] = '%' . $filtros['busqueda'] . '%';
    }

    if ($filtros['id_categoria'] > 0) {
        $condiciones[] = 'p.id_categoria = :id_categoria';
        $parametros['id_categoria'] = $filtros['id_categoria'];
    }

    if ($filtros['id_proveedor'] > 0) {
        $condiciones[] = 'EXISTS (
            SELECT 1
            FROM compra_detalle cd_proveedor
            INNER JOIN compras c_proveedor ON c_proveedor.id_compra = cd_proveedor.id_compra
            WHERE cd_proveedor.id_producto = p.id_producto
              AND c_proveedor.id_proveedor = :id_proveedor
        )';
        $parametros['id_proveedor'] = $filtros['id_proveedor'];
    }

    if ($filtros['id_estado_producto'] > 0) {
        $condiciones[] = 'EXISTS (
            SELECT 1
            FROM compra_detalle cd_estado
            INNER JOIN unidades_inventario ui_estado ON ui_estado.id_detalle = cd_estado.id_detalle
            WHERE cd_estado.id_producto = p.id_producto
              AND ui_estado.id_estado_producto = :id_estado_producto
        )';
        $parametros['id_estado_producto'] = $filtros['id_estado_producto'];
    }

    $having = '';
    if ($filtros['stock'] === 'con_stock') {
        $having = ' HAVING stock_disponible > 0';
    } elseif ($filtros['stock'] === 'sin_stock') {
        $having = ' HAVING stock_total = 0';
    } elseif ($filtros['stock'] === 'stock_bajo') {
        $having = ' HAVING stock_disponible > 0 AND stock_disponible <= 5';
    }

    $consulta = 'SELECT
            p.id_producto,
            p.nombre,
            p.codigo_producto,
            c.nombre AS categoria,
            p.unidad_medida,
            (
                SELECT pi.imagen
                FROM producto_imagenes pi
                WHERE pi.id_producto = p.id_producto
                  AND pi.imagen IS NOT NULL
                  AND OCTET_LENGTH(pi.imagen) > 0
                ORDER BY pi.orden ASC, pi.id_imagen ASC
                LIMIT 1
            ) AS imagen,
            COUNT(ui.id_unidad) AS stock_total,
            COALESCE(SUM(CASE WHEN ep.nombre IN (\'asignada\', \'en_uso\') THEN 1 ELSE 0 END), 0) AS stock_asignado,
            COALESCE(SUM(CASE WHEN ep.nombre = \'disponible\' THEN 1 ELSE 0 END), 0) AS stock_disponible
        FROM productos p
        INNER JOIN categorias_productos c ON c.id_categoria = p.id_categoria
        LEFT JOIN compra_detalle cd ON cd.id_producto = p.id_producto
        LEFT JOIN unidades_inventario ui ON ui.id_detalle = cd.id_detalle
        LEFT JOIN estados_productos ep ON ep.id_estado_producto = ui.id_estado_producto
        WHERE ' . implode(' AND ', $condiciones) . '
        GROUP BY p.id_producto, p.nombre, p.codigo_producto, c.nombre, p.unidad_medida' . $having;

    return [$consulta, $parametros];
}

/* Obtiene las opciones que alimentan los filtros del inventario */
function obtenerOpcionesInventario(PDO $conexion): array
{
    $categorias = $conexion->query(
        'SELECT id_categoria AS id, nombre
         FROM categorias_productos
         WHERE activo = 1
         ORDER BY nombre'
    )->fetchAll();

    $proveedores = $conexion->query(
        "SELECT id_proveedor AS id, COALESCE(NULLIF(razon_social, ''), nombre) AS nombre
         FROM proveedores
         ORDER BY nombre"
    )->fetchAll();

    $estados = $conexion->query(
        'SELECT id_estado_producto AS id, nombre
         FROM estados_productos
         WHERE activo = 1
         ORDER BY id_estado_producto'
    )->fetchAll();

    return [
        'categorias' => array_map(static fn ($opcion) => [
            'id' => (int) $opcion['id'],
            'nombre' => $opcion['nombre'],
        ], $categorias),
        'proveedores' => array_map(static fn ($opcion) => [
            'id' => (int) $opcion['id'],
            'nombre' => $opcion['nombre'],
        ], $proveedores),
        'estados' => array_map(static fn ($opcion) => [
            'id' => (int) $opcion['id'],
            'nombre' => $opcion['nombre'],
        ], $estados),
        'stock' => [
            ['id' => 'con_stock', 'nombre' => 'Con stock disponible'],
            ['id' => 'sin_stock', 'nombre' => 'Sin stock'],
            ['id' => 'stock_bajo', 'nombre' => 'Stock bajo'],
        ],
    ];
}

/* Lista el inventario real, sus indicadores y las opciones de filtros */
function listarInventario(PDO $conexion, int $pagina = 1, int $porPagina = 7, array $filtros = []): array
{
    $pagina = max(1, $pagina);
    $porPagina = min(50, max(1, $porPagina));
    $filtros = normalizarFiltrosInventario($filtros);

    $parametrosConsulta = [];
    [$consultaAgrupada, $parametros] = construirConsultaAgrupadaInventario($filtros, $parametrosConsulta);

    $consultaTotal = $conexion->prepare('SELECT COUNT(*) FROM (' . $consultaAgrupada . ') inventario_filtrado');
    $consultaTotal->execute($parametrosConsulta);
    $total = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $consultaProductos = $conexion->prepare(
        $consultaAgrupada . ' ORDER BY nombre ASC LIMIT :limite OFFSET :offset'
    );
    foreach ($parametros as $nombre => $valor) {
        $consultaProductos->bindValue(':' . $nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $consultaProductos->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consultaProductos->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consultaProductos->execute();

    $productos = array_map(static function (array $producto): array {
        return [
            'id_producto' => (int) $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'codigo_producto' => $producto['codigo_producto'],
            'categoria' => $producto['categoria'],
            'unidad_medida' => $producto['unidad_medida'],
            'imagen' => convertirImagenBlobADataUri($producto['imagen'], null),
            'stock_total' => (int) $producto['stock_total'],
            'stock_asignado' => (int) $producto['stock_asignado'],
            'stock_disponible' => (int) $producto['stock_disponible'],
        ];
    }, $consultaProductos->fetchAll());

    $consultaResumen = $conexion->prepare(
        'SELECT
            COUNT(*) AS productos,
            COALESCE(SUM(stock_total), 0) AS stock_total,
            COALESCE(SUM(stock_asignado), 0) AS stock_asignado,
            COALESCE(SUM(stock_disponible), 0) AS stock_disponible
         FROM (' . $consultaAgrupada . ') inventario_filtrado'
    );
    $consultaResumen->execute($parametrosConsulta);
    $resumen = $consultaResumen->fetch() ?: [];

    return [
        'productos' => $productos,
        'resumen' => [
            'productos' => (int) ($resumen['productos'] ?? 0),
            'stock_total' => (int) ($resumen['stock_total'] ?? 0),
            'stock_asignado' => (int) ($resumen['stock_asignado'] ?? 0),
            'stock_disponible' => (int) ($resumen['stock_disponible'] ?? 0),
        ],
        'opciones' => obtenerOpcionesInventario($conexion),
        'paginacion' => [
            'pagina_actual' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas,
        ],
    ];
}

/* Valida y normaliza los filtros de las unidades del detalle de producto */
function normalizarFiltrosUnidadesDetalle(array $filtros): array
{
    $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('La búsqueda de unidades es demasiado larga.');
    }

    $fechaCompra = trim((string) ($filtros['fecha_compra'] ?? ''));
    if ($fechaCompra !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCompra)) {
        $fechaCompra = '';
    }

    $usuarioAsignado = trim((string) ($filtros['usuario_asignado'] ?? ''));
    if ($usuarioAsignado !== 'sin_asignar' && (!ctype_digit($usuarioAsignado) || (int) $usuarioAsignado <= 0)) {
        $usuarioAsignado = '';
    }

    return [
        'busqueda' => $busqueda,
        'usuario_asignado' => $usuarioAsignado,
        'fecha_compra' => $fechaCompra,
        'id_proveedor' => max(0, (int) ($filtros['id_proveedor'] ?? 0)),
        'id_transportista' => max(0, (int) ($filtros['id_transportista'] ?? 0)),
    ];
}

/* Obtiene los filtros disponibles para las unidades del producto seleccionado */
function obtenerOpcionesUnidadesDetalle(PDO $conexion, int $idProducto): array
{
    $consultaProveedores = $conexion->prepare(
        "SELECT DISTINCT
            proveedor.id_proveedor AS id,
            COALESCE(NULLIF(proveedor.razon_social, ''), proveedor.nombre) AS nombre
         FROM compra_detalle detalle
         INNER JOIN compras compra ON compra.id_compra = detalle.id_compra
         INNER JOIN proveedores proveedor ON proveedor.id_proveedor = compra.id_proveedor
         WHERE detalle.id_producto = :id_producto
         ORDER BY nombre ASC"
    );
    $consultaProveedores->execute(['id_producto' => $idProducto]);

    $consultaTransportistas = $conexion->prepare(
        "SELECT DISTINCT
            transportista.id_transportista AS id,
            transportista.nombre
         FROM compra_detalle detalle
         INNER JOIN compras compra ON compra.id_compra = detalle.id_compra
         INNER JOIN transportistas transportista ON transportista.id_transportista = compra.id_transportista
         WHERE detalle.id_producto = :id_producto
         ORDER BY nombre ASC"
    );
    $consultaTransportistas->execute(['id_producto' => $idProducto]);

    return [
        'usuarios' => listarUsuariosDisponiblesUnidad($conexion),
        'proveedores' => array_map(static function (array $opcion): array {
            return ['id' => (int) $opcion['id'], 'nombre' => $opcion['nombre']];
        }, $consultaProveedores->fetchAll()),
        'transportistas' => array_map(static function (array $opcion): array {
            return ['id' => (int) $opcion['id'], 'nombre' => $opcion['nombre']];
        }, $consultaTransportistas->fetchAll()),
    ];
}

/* Obtiene la ficha completa de un producto y una página filtrada de sus unidades */
function obtenerDetalleProducto(PDO $conexion, int $idProducto, int $pagina = 1, int $porPagina = 9, array $filtros = []): array
{
    if ($idProducto <= 0) {
        throw new InvalidArgumentException('El producto solicitado no es válido.');
    }

    $pagina = max(1, $pagina);
    $porPagina = min(50, max(1, $porPagina));
    $filtros = normalizarFiltrosUnidadesDetalle($filtros);

    $consultaProducto = $conexion->prepare(
        'SELECT
            p.id_producto,
            p.nombre,
            p.codigo_producto,
            p.descripcion,
            p.unidad_medida,
            p.activo,
            c.nombre AS categoria
         FROM productos p
         INNER JOIN categorias_productos c ON c.id_categoria = p.id_categoria
         WHERE p.id_producto = :id_producto
         LIMIT 1'
    );
    $consultaProducto->execute(['id_producto' => $idProducto]);
    $producto = $consultaProducto->fetch();
    if (!$producto) {
        throw new InvalidArgumentException('El producto solicitado no existe.');
    }

    $consultaImagenes = $conexion->prepare(
        'SELECT id_imagen, imagen, nombre_archivo, orden
         FROM producto_imagenes
         WHERE id_producto = :id_producto
         ORDER BY orden ASC, id_imagen ASC'
    );
    $consultaImagenes->execute(['id_producto' => $idProducto]);
    $imagenes = array_map(static function (array $imagen): array {
        return [
            'id_imagen' => (int) $imagen['id_imagen'],
            'nombre_archivo' => $imagen['nombre_archivo'],
            'orden' => (int) $imagen['orden'],
            'imagen' => convertirImagenBlobADataUri($imagen['imagen'], null),
        ];
    }, $consultaImagenes->fetchAll());

    $consultaProveedorPrincipal = $conexion->prepare(
        "SELECT COALESCE(NULLIF(p.razon_social, ''), p.nombre) AS nombre
         FROM compra_detalle cd
         INNER JOIN compras c ON c.id_compra = cd.id_compra
         INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
         WHERE cd.id_producto = :id_producto
         ORDER BY c.fecha_compra DESC, c.id_compra DESC
         LIMIT 1"
    );
    $consultaProveedorPrincipal->execute(['id_producto' => $idProducto]);
    $proveedorPrincipal = $consultaProveedorPrincipal->fetchColumn() ?: null;

    $consultaStock = $conexion->prepare(
        "SELECT
            COUNT(ui.id_unidad) AS stock_total,
            COALESCE(SUM(CASE WHEN ep.nombre IN ('asignada', 'en_uso') THEN 1 ELSE 0 END), 0) AS stock_asignado,
            COALESCE(SUM(CASE WHEN ep.nombre = 'disponible' THEN 1 ELSE 0 END), 0) AS stock_disponible
         FROM compra_detalle cd
         LEFT JOIN unidades_inventario ui ON ui.id_detalle = cd.id_detalle
         LEFT JOIN estados_productos ep ON ep.id_estado_producto = ui.id_estado_producto
         WHERE cd.id_producto = :id_producto"
    );
    $consultaStock->execute(['id_producto' => $idProducto]);
    $stock = $consultaStock->fetch() ?: [];

    $condicionesUnidades = ['cd.id_producto = :id_producto'];
    $parametrosUnidades = ['id_producto' => $idProducto];
    if ($filtros['busqueda'] !== '') {
        $condicionesUnidades[] = '(ui.codigo_unidad LIKE :busqueda_unidad OR c.codigo_compra LIKE :busqueda_compra)';
        $parametrosUnidades['busqueda_unidad'] = '%' . $filtros['busqueda'] . '%';
        $parametrosUnidades['busqueda_compra'] = '%' . $filtros['busqueda'] . '%';
    }
    if ($filtros['usuario_asignado'] === 'sin_asignar') {
        $condicionesUnidades[] = 'NOT EXISTS (
            SELECT 1
            FROM asignaciones_unidades au_filtro
            WHERE au_filtro.id_unidad = ui.id_unidad
              AND au_filtro.fecha_devolucion IS NULL
        )';
    } elseif ($filtros['usuario_asignado'] !== '') {
        $condicionesUnidades[] = 'EXISTS (
            SELECT 1
            FROM asignaciones_unidades au_filtro
            WHERE au_filtro.id_unidad = ui.id_unidad
              AND au_filtro.id_usuario = :id_usuario_asignado
              AND au_filtro.fecha_devolucion IS NULL
        )';
        $parametrosUnidades['id_usuario_asignado'] = (int) $filtros['usuario_asignado'];
    }
    if ($filtros['fecha_compra'] !== '') {
        $condicionesUnidades[] = 'c.fecha_compra = :fecha_compra';
        $parametrosUnidades['fecha_compra'] = $filtros['fecha_compra'];
    }
    if ($filtros['id_proveedor'] > 0) {
        $condicionesUnidades[] = 'c.id_proveedor = :id_proveedor_unidad';
        $parametrosUnidades['id_proveedor_unidad'] = $filtros['id_proveedor'];
    }
    if ($filtros['id_transportista'] > 0) {
        $condicionesUnidades[] = 'c.id_transportista = :id_transportista_unidad';
        $parametrosUnidades['id_transportista_unidad'] = $filtros['id_transportista'];
    }
    $whereUnidades = implode(' AND ', $condicionesUnidades);

    $consultaTotal = $conexion->prepare(
        'SELECT COUNT(*)
         FROM unidades_inventario ui
         INNER JOIN compra_detalle cd ON cd.id_detalle = ui.id_detalle
         INNER JOIN compras c ON c.id_compra = cd.id_compra
         WHERE ' . $whereUnidades
    );
    $consultaTotal->execute($parametrosUnidades);
    $totalUnidades = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($totalUnidades / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $consultaUnidades = $conexion->prepare(
        "SELECT
            ui.id_unidad,
            ui.codigo_unidad,
            ep.id_estado_producto,
            ep.nombre AS estado,
            COALESCE((
                SELECT TRIM(CONCAT(usr.nombre, ' ', usr.apellido))
                FROM asignaciones_unidades au
                INNER JOIN usuarios usr ON usr.id_usuario = au.id_usuario
                WHERE au.id_unidad = ui.id_unidad
                  AND au.fecha_devolucion IS NULL
                ORDER BY au.fecha_asignacion DESC, au.id_asignacion DESC
                LIMIT 1
            ), '----') AS asignado_a,
            (
                SELECT au.id_usuario
                FROM asignaciones_unidades au
                WHERE au.id_unidad = ui.id_unidad
                  AND au.fecha_devolucion IS NULL
                ORDER BY au.fecha_asignacion DESC, au.id_asignacion DESC
                LIMIT 1
            ) AS id_usuario_asignado,
            c.id_compra,
            c.codigo_compra,
            c.fecha_compra,
            cd.precio_unitario,
            moneda.codigo AS moneda,
            COALESCE(NULLIF(proveedor.razon_social, ''), proveedor.nombre) AS proveedor,
            transportista.nombre AS transportista
         FROM unidades_inventario ui
         INNER JOIN estados_productos ep ON ep.id_estado_producto = ui.id_estado_producto
         INNER JOIN compra_detalle cd ON cd.id_detalle = ui.id_detalle
         INNER JOIN compras c ON c.id_compra = cd.id_compra
         INNER JOIN monedas moneda ON moneda.id_moneda = c.id_moneda
         INNER JOIN proveedores proveedor ON proveedor.id_proveedor = c.id_proveedor
         LEFT JOIN transportistas transportista ON transportista.id_transportista = c.id_transportista
         WHERE " . $whereUnidades . "
         ORDER BY ui.id_unidad ASC
         LIMIT :limite OFFSET :offset"
    );
    foreach ($parametrosUnidades as $nombreParametro => $valorParametro) {
        $consultaUnidades->bindValue(':' . $nombreParametro, $valorParametro, is_int($valorParametro) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $consultaUnidades->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consultaUnidades->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consultaUnidades->execute();

    $unidades = array_map(static function (array $unidad): array {
        return [
            'id_unidad' => (int) $unidad['id_unidad'],
            'codigo_unidad' => $unidad['codigo_unidad'],
            'id_estado_producto' => (int) $unidad['id_estado_producto'],
            'estado' => $unidad['estado'],
            'asignado_a' => $unidad['asignado_a'],
            'id_usuario_asignado' => $unidad['id_usuario_asignado'] !== null ? (int) $unidad['id_usuario_asignado'] : null,
            'id_compra' => (int) $unidad['id_compra'],
            'codigo_compra' => $unidad['codigo_compra'],
            'fecha_compra' => $unidad['fecha_compra'],
            'precio_unitario' => (float) $unidad['precio_unitario'],
            'moneda' => $unidad['moneda'],
            'proveedor' => $unidad['proveedor'],
            'transportista' => $unidad['transportista'] ?: '----',
        ];
    }, $consultaUnidades->fetchAll());

    $consultaMejorProveedor = $conexion->prepare(
        "SELECT
            COALESCE(NULLIF(proveedor.razon_social, ''), proveedor.nombre) AS nombre,
            proveedor.imagen AS imagen,
            AVG(evaluacion.calificacion) AS calificacion,
            COUNT(DISTINCT compra.id_compra) AS compras
         FROM compra_detalle detalle
         INNER JOIN compras compra ON compra.id_compra = detalle.id_compra
         INNER JOIN proveedores proveedor ON proveedor.id_proveedor = compra.id_proveedor
         INNER JOIN evaluaciones_proveedores evaluacion ON evaluacion.id_compra = compra.id_compra
         WHERE detalle.id_producto = :id_producto
         GROUP BY proveedor.id_proveedor, proveedor.nombre, proveedor.razon_social, proveedor.imagen
         ORDER BY calificacion DESC, compras DESC
         LIMIT 1"
    );
    $consultaMejorProveedor->execute(['id_producto' => $idProducto]);
    $mejorProveedor = $consultaMejorProveedor->fetch() ?: null;

    $consultaMejorTransportista = $conexion->prepare(
        "SELECT
            transportista.nombre,
            transportista.imagen AS imagen,
            AVG(evaluacion.calificacion) AS calificacion,
            COUNT(DISTINCT compra.id_compra) AS compras
         FROM compra_detalle detalle
         INNER JOIN compras compra ON compra.id_compra = detalle.id_compra
         INNER JOIN transportistas transportista ON transportista.id_transportista = compra.id_transportista
         INNER JOIN evaluaciones_transportistas evaluacion ON evaluacion.id_compra = compra.id_compra
         WHERE detalle.id_producto = :id_producto
         GROUP BY transportista.id_transportista, transportista.nombre, transportista.imagen
         ORDER BY calificacion DESC, compras DESC
         LIMIT 1"
    );
    $consultaMejorTransportista->execute(['id_producto' => $idProducto]);
    $mejorTransportista = $consultaMejorTransportista->fetch() ?: null;
    $opcionesUnidades = obtenerOpcionesUnidadesDetalle($conexion, $idProducto);

    return [
        'producto' => [
            'id_producto' => (int) $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'codigo_producto' => $producto['codigo_producto'],
            'descripcion' => $producto['descripcion'],
            'unidad_medida' => $producto['unidad_medida'],
            'categoria' => $producto['categoria'],
            'activo' => (bool) $producto['activo'],
            'proveedor_principal' => $proveedorPrincipal,
        ],
        'imagenes' => $imagenes,
        'stock' => [
            'total' => (int) ($stock['stock_total'] ?? 0),
            'asignado' => (int) ($stock['stock_asignado'] ?? 0),
            'disponible' => (int) ($stock['stock_disponible'] ?? 0),
        ],
        'calificaciones' => [
            'proveedor' => $mejorProveedor ? [
                'nombre' => $mejorProveedor['nombre'],
                'imagen' => convertirImagenBlobADataUri($mejorProveedor['imagen'], null),
                'calificacion' => (float) $mejorProveedor['calificacion'],
                'compras' => (int) $mejorProveedor['compras'],
            ] : null,
            'transportista' => $mejorTransportista ? [
                'nombre' => $mejorTransportista['nombre'],
                'imagen' => convertirImagenBlobADataUri($mejorTransportista['imagen'], null),
                'calificacion' => (float) $mejorTransportista['calificacion'],
                'compras' => (int) $mejorTransportista['compras'],
            ] : null,
        ],
        'unidades' => $unidades,
        'opciones_unidades' => $opcionesUnidades,
        'paginacion' => [
            'pagina_actual' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $totalUnidades,
            'total_paginas' => $totalPaginas,
        ],
    ];
}

/* Reasigna las órdenes de las imágenes evitando conflictos con la clave única */
function aplicarOrdenImagenesProducto(PDO $conexion, int $idProducto, array $idsImagenes): void
{
    if (!$idsImagenes) {
        return;
    }

    $desplazamiento = 1000000;
    $consultaTemporal = $conexion->prepare(
        'UPDATE producto_imagenes
         SET orden = orden + :desplazamiento
         WHERE id_producto = :id_producto'
    );
    $consultaTemporal->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
    $consultaTemporal->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
    $consultaTemporal->execute();

    $consultaOrden = $conexion->prepare(
        'UPDATE producto_imagenes
         SET orden = :orden
         WHERE id_imagen = :id_imagen AND id_producto = :id_producto'
    );
    foreach ($idsImagenes as $indice => $idImagen) {
        $consultaOrden->bindValue(':orden', $indice + 1, PDO::PARAM_INT);
        $consultaOrden->bindValue(':id_imagen', (int) $idImagen, PDO::PARAM_INT);
        $consultaOrden->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $consultaOrden->execute();
    }
}

/* Normaliza todas las órdenes actuales de las imágenes de un producto */
function normalizarOrdenImagenesProducto(PDO $conexion, int $idProducto): void
{
    $consulta = $conexion->prepare(
        'SELECT id_imagen
         FROM producto_imagenes
         WHERE id_producto = :id_producto
         ORDER BY orden ASC, id_imagen ASC'
    );
    $consulta->execute(['id_producto' => $idProducto]);
    $idsImagenes = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
    aplicarOrdenImagenesProducto($conexion, $idProducto, $idsImagenes);
}

/* Valida y almacena una imagen nueva asociada a un producto */
function subirImagenProducto(PDO $conexion): array
{
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    if ($idProducto <= 0) {
        throw new InvalidArgumentException('El producto solicitado no es válido.');
    }
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new InvalidArgumentException('Selecciona una imagen para subir.');
    }
    if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('No fue posible recibir la imagen.');
    }
    if ((int) $_FILES['imagen']['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('La imagen no puede superar los 5 MB.');
    }

    $tipoImagen = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagen']['tmp_name']);
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($tipoImagen, $tiposPermitidos, true)) {
        throw new InvalidArgumentException('El archivo debe ser una imagen JPG, PNG, WEBP o GIF.');
    }

    $contenidoImagen = file_get_contents($_FILES['imagen']['tmp_name']);
    if ($contenidoImagen === false) {
        throw new InvalidArgumentException('No fue posible leer la imagen seleccionada.');
    }

    $verificarProducto = $conexion->prepare('SELECT id_producto FROM productos WHERE id_producto = :id_producto');
    $verificarProducto->execute(['id_producto' => $idProducto]);
    if (!$verificarProducto->fetchColumn()) {
        throw new InvalidArgumentException('El producto solicitado no existe.');
    }

    try {
        $conexion->beginTransaction();
        $consultaMaximo = $conexion->prepare('SELECT COALESCE(MAX(orden), 0) FROM producto_imagenes WHERE id_producto = :id_producto');
        $consultaMaximo->execute(['id_producto' => $idProducto]);
        $orden = (int) $consultaMaximo->fetchColumn() + 1;

        $consulta = $conexion->prepare(
            'INSERT INTO producto_imagenes (id_producto, imagen, nombre_archivo, orden)
             VALUES (:id_producto, :imagen, :nombre_archivo, :orden)'
        );
        $consulta->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $consulta->bindValue(':imagen', $contenidoImagen, PDO::PARAM_LOB);
        $consulta->bindValue(':nombre_archivo', basename((string) $_FILES['imagen']['name']), PDO::PARAM_STR);
        $consulta->bindValue(':orden', $orden, PDO::PARAM_INT);
        $consulta->execute();
        normalizarOrdenImagenesProducto($conexion, $idProducto);
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }

    return ['mensaje' => 'La imagen se agrego correctamente.'];
}

/* Elimina una imagen y renumera las restantes del producto */
function eliminarImagenProducto(PDO $conexion): array
{
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $idImagen = (int) ($_POST['id_imagen'] ?? 0);
    if ($idProducto <= 0 || $idImagen <= 0) {
        throw new InvalidArgumentException('La imagen solicitada no es válida.');
    }

    try {
        $conexion->beginTransaction();
        $consulta = $conexion->prepare('DELETE FROM producto_imagenes WHERE id_imagen = :id_imagen AND id_producto = :id_producto');
        $consulta->execute(['id_imagen' => $idImagen, 'id_producto' => $idProducto]);
        if ($consulta->rowCount() === 0) {
            throw new InvalidArgumentException('La imagen no existe para este producto.');
        }
        normalizarOrdenImagenesProducto($conexion, $idProducto);
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }

    return ['mensaje' => 'La imagen se elimino correctamente.'];
}

/* Marca una imagen como principal y ajusta el orden de todas las imágenes */
function marcarImagenPrincipalProducto(PDO $conexion): array
{
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $idImagen = (int) ($_POST['id_imagen'] ?? 0);
    if ($idProducto <= 0 || $idImagen <= 0) {
        throw new InvalidArgumentException('La imagen solicitada no es válida.');
    }

    try {
        $conexion->beginTransaction();
        $consulta = $conexion->prepare(
            'SELECT id_imagen
             FROM producto_imagenes
             WHERE id_producto = :id_producto
             ORDER BY orden ASC, id_imagen ASC'
        );
        $consulta->execute(['id_producto' => $idProducto]);
        $idsImagenes = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));
        if (!in_array($idImagen, $idsImagenes, true)) {
            throw new InvalidArgumentException('La imagen no existe para este producto.');
        }
        $idsImagenes = array_values(array_diff($idsImagenes, [$idImagen]));
        array_unshift($idsImagenes, $idImagen);
        aplicarOrdenImagenesProducto($conexion, $idProducto, $idsImagenes);
        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }

    return ['mensaje' => 'La imagen principal se actualizó correctamente.'];
}

/* Lista de los usuarios activos que pueden recibir una unidad de inventario */
function listarUsuariosDisponiblesUnidad(PDO $conexion): array
{
    $consulta = $conexion->query(
        "SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.nombre_usuario,
            COALESCE(r.nombre, 'Sin rol asignado') AS rol_nombre
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.estado = 'activo'
         ORDER BY u.nombre ASC, u.apellido ASC, u.id_usuario ASC"
    );

    return array_map(static function (array $usuario): array {
        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => trim($usuario['nombre'] . ' ' . $usuario['apellido']),
            'nombre_usuario' => $usuario['nombre_usuario'],
            'rol_nombre' => $usuario['rol_nombre'],
        ];
    }, $consulta->fetchAll());
}

/* Asigna una unidad a un usuario activo y cierra la asignación anterior */
function asignarUnidadUsuario(PDO $conexion): array
{
    $idUnidad = (int) ($_POST['id_unidad'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
    if ($idUnidad <= 0 || $idUsuario < 0) {
        throw new InvalidArgumentException('La unidad o el usuario seleccionado no son válidos.');
    }

    try {
        $conexion->beginTransaction();

        $consultaUnidad = $conexion->prepare(
            'SELECT u.id_unidad, ep.nombre AS estado
             FROM unidades_inventario u
             INNER JOIN estados_productos ep ON ep.id_estado_producto = u.id_estado_producto
             WHERE u.id_unidad = :id_unidad
             LIMIT 1'
        );
        $consultaUnidad->execute(['id_unidad' => $idUnidad]);
        $unidad = $consultaUnidad->fetch();
        if (!$unidad) {
            throw new InvalidArgumentException('La unidad solicitada no existe.');
        }

        if ($idUsuario > 0) {
            $consultaUsuario = $conexion->prepare(
                "SELECT id_usuario
                 FROM usuarios
                 WHERE id_usuario = :id_usuario AND estado = 'activo'
                 LIMIT 1"
            );
            $consultaUsuario->execute(['id_usuario' => $idUsuario]);
            if (!$consultaUsuario->fetchColumn()) {
                throw new InvalidArgumentException('El usuario seleccionado no existe o no está activo.');
            }
        }

        $consultaVigente = $conexion->prepare(
            'SELECT id_asignacion, id_usuario
             FROM asignaciones_unidades
             WHERE id_unidad = :id_unidad AND fecha_devolucion IS NULL
             ORDER BY fecha_asignacion DESC, id_asignacion DESC
             LIMIT 1'
        );
        $consultaVigente->execute(['id_unidad' => $idUnidad]);
        $asignacionVigente = $consultaVigente->fetch();

        if ($idUsuario > 0 && $asignacionVigente && (int) $asignacionVigente['id_usuario'] === $idUsuario) {
            $conexion->commit();
            return ['mensaje' => 'La unidad ya estaba asignada a este usuario.'];
        }

        if ($asignacionVigente) {
            $cerrarAsignacion = $conexion->prepare(
                'UPDATE asignaciones_unidades
                 SET fecha_devolucion = NOW()
                 WHERE id_asignacion = :id_asignacion'
            );
            $cerrarAsignacion->execute(['id_asignacion' => (int) $asignacionVigente['id_asignacion']]);
        }

        if ($idUsuario > 0) {
            $nuevaAsignacion = $conexion->prepare(
                'INSERT INTO asignaciones_unidades (id_unidad, id_usuario, fecha_asignacion)
                 VALUES (:id_unidad, :id_usuario, NOW())'
            );
            $nuevaAsignacion->execute([
                'id_unidad' => $idUnidad,
                'id_usuario' => $idUsuario,
            ]);
        }

        if ($idUsuario > 0 && in_array($unidad['estado'], ['disponible', 'asignada'], true)) {
            $actualizarEstado = $conexion->prepare(
                "UPDATE unidades_inventario u
                 INNER JOIN estados_productos ep ON ep.nombre = 'asignada'
                 SET u.id_estado_producto = ep.id_estado_producto
                 WHERE u.id_unidad = :id_unidad"
            );
            $actualizarEstado->execute(['id_unidad' => $idUnidad]);
        } elseif ($idUsuario === 0 && $unidad['estado'] === 'asignada') {
            $actualizarEstado = $conexion->prepare(
                "UPDATE unidades_inventario u
                 INNER JOIN estados_productos ep ON ep.nombre = 'disponible'
                 SET u.id_estado_producto = ep.id_estado_producto
                 WHERE u.id_unidad = :id_unidad"
            );
            $actualizarEstado->execute(['id_unidad' => $idUnidad]);
        }

        $conexion->commit();
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    }

    return ['mensaje' => $idUsuario > 0 ? 'La unidad se asigno correctamente.' : 'La unidad quedo sin asignar.'];
}

/* Lista los estados activos que pueden seleccionarse para una unidad */
function listarEstadosDisponiblesUnidad(PDO $conexion): array
{
    $consulta = $conexion->query(
        'SELECT id_estado_producto, nombre, descripcion, color
         FROM estados_productos
         WHERE activo = 1
         ORDER BY id_estado_producto ASC'
    );

    return array_map(static function (array $estado): array {
        return [
            'id_estado_producto' => (int) $estado['id_estado_producto'],
            'nombre' => $estado['nombre'],
            'descripcion' => $estado['descripcion'] ?: '',
            'color' => $estado['color'] ?: '#64748b',
        ];
    }, $consulta->fetchAll());
}

/* Actualiza el estado de una unidad de inventario */
function actualizarEstadoUnidad(PDO $conexion): array
{
    $idUnidad = (int) ($_POST['id_unidad'] ?? 0);
    $idEstado = (int) ($_POST['id_estado_producto'] ?? 0);
    if ($idUnidad <= 0 || $idEstado <= 0) {
        throw new InvalidArgumentException('La unidad o el estado seleccionado no son válidos.');
    }

    $verificarEstado = $conexion->prepare(
        'SELECT id_estado_producto
         FROM estados_productos
         WHERE id_estado_producto = :id_estado AND activo = 1
         LIMIT 1'
    );
    $verificarEstado->execute(['id_estado' => $idEstado]);
    if (!$verificarEstado->fetchColumn()) {
        throw new InvalidArgumentException('El estado seleccionado no existe o no está activo.');
    }

    $consulta = $conexion->prepare(
        'UPDATE unidades_inventario
         SET id_estado_producto = :id_estado
         WHERE id_unidad = :id_unidad'
    );
    $consulta->execute([
        'id_estado' => $idEstado,
        'id_unidad' => $idUnidad,
    ]);
    if ($consulta->rowCount() === 0) {
        $verificarUnidad = $conexion->prepare('SELECT id_unidad FROM unidades_inventario WHERE id_unidad = :id_unidad LIMIT 1');
        $verificarUnidad->execute(['id_unidad' => $idUnidad]);
        if (!$verificarUnidad->fetchColumn()) {
            throw new InvalidArgumentException('La unidad solicitada no existe.');
        }
    }

    return ['mensaje' => 'El estado de la unidad se actualizó correctamente.'];
}
