<?php
/**
 * historial_compras_api.php
 *
 * Consulta el historial real de compras, sus filtros disponibles, indicadores agregados y resultados paginados
 */

require_once __DIR__ . '/../../configuracion/imagenes.php';

/* Devuelve el historial de compras filtrado y sus opciones de consulta */
function listarHistorialCompras(PDO $conexion, int $pagina = 1, int $porPagina = 7, array $filtros = []): array
{
    $pagina = max(1, $pagina);
    $porPagina = min(50, max(1, $porPagina));
    $busqueda = trim((string) ($filtros['busqueda'] ?? ''));
    $fecha = trim((string) ($filtros['fecha'] ?? ''));
    $idProveedor = (int) ($filtros['id_proveedor'] ?? 0);
    $idTransportista = (int) ($filtros['id_transportista'] ?? 0);
    $idEstado = (int) ($filtros['id_estado_compra'] ?? 0);

    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('El texto de búsqueda es demasiado largo.');
    }
    if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new InvalidArgumentException('La fecha seleccionada no es válida.');
    }
    if ($idProveedor < 0 || $idTransportista < 0 || $idEstado < 0) {
        throw new InvalidArgumentException('Uno de los filtros seleccionados no es válido.');
    }

    $condiciones = ['1 = 1'];
    $parametros = [];
    if ($busqueda !== '') {
        $condiciones[] = '(c.codigo_compra LIKE :busqueda_codigo
            OR EXISTS (
                SELECT 1
                FROM usuarios u_busqueda
                WHERE u_busqueda.id_usuario = c.id_usuario
                  AND CONCAT_WS(\' \', u_busqueda.nombre, u_busqueda.apellido) LIKE :busqueda_responsable
            ))';
        $parametros[':busqueda_codigo'] = '%' . $busqueda . '%';
        $parametros[':busqueda_responsable'] = '%' . $busqueda . '%';
    }
    if ($fecha !== '') {
        $condiciones[] = 'c.fecha_compra = :fecha';
        $parametros[':fecha'] = $fecha;
    }
    if ($idProveedor > 0) {
        $condiciones[] = 'c.id_proveedor = :id_proveedor';
        $parametros[':id_proveedor'] = $idProveedor;
    }
    if ($idTransportista > 0) {
        $condiciones[] = 'c.id_transportista = :id_transportista';
        $parametros[':id_transportista'] = $idTransportista;
    }
    if ($idEstado > 0) {
        $condiciones[] = 'c.id_estado_compra = :id_estado_compra';
        $parametros[':id_estado_compra'] = $idEstado;
    }
    $where = implode(' AND ', $condiciones);

    $consultaTotal = $conexion->prepare('SELECT COUNT(*) FROM compras c WHERE ' . $where);
    $consultaTotal->execute($parametros);
    $total = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $consultaResumen = $conexion->prepare(
        'SELECT
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE(SUM(d.cantidad), 0) AS articulos,
            COUNT(DISTINCT CASE WHEN e.nombre = \'recibida\' THEN c.id_compra END) AS recibidas
         FROM compras c
         INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
         LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra
         WHERE ' . $where
    );
    $consultaResumen->execute($parametros);
    $resumen = $consultaResumen->fetch() ?: [];

    $consultaMontos = $conexion->prepare(
        'SELECT moneda, SUM(total_compra) AS total
         FROM (
             SELECT
                 c.id_compra,
                 m.codigo AS moneda,
                 c.costo_envio + COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS total_compra
             FROM compras c
             INNER JOIN monedas m ON m.id_moneda = c.id_moneda
             LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra
             WHERE ' . $where . '
             GROUP BY c.id_compra, m.codigo, c.costo_envio
         ) compras_totales
         GROUP BY moneda
         ORDER BY moneda'
    );
    $consultaMontos->execute($parametros);
    $montos = array_map(static function (array $monto): array {
        return ['moneda' => $monto['moneda'], 'total' => (float) $monto['total']];
    }, $consultaMontos->fetchAll());

    $consultaCompras = $conexion->prepare(
        'SELECT
            c.id_compra,
            c.codigo_compra,
            c.fecha_compra,
            p.nombre AS proveedor,
            COALESCE(t.nombre, \'Sin transportista\') AS transportista,
            COUNT(DISTINCT d.id_producto) AS productos,
            COALESCE(SUM(d.cantidad * d.precio_unitario), 0) + c.costo_envio AS total,
            m.codigo AS moneda,
            e.id_estado_compra,
            e.nombre AS estado,
            TRIM(CONCAT(u.nombre, \' \', u.apellido)) AS responsable
         FROM compras c
         INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
         INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
         INNER JOIN monedas m ON m.id_moneda = c.id_moneda
         INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
         LEFT JOIN transportistas t ON t.id_transportista = c.id_transportista
         LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra
         WHERE ' . $where . '
         GROUP BY c.id_compra, c.codigo_compra, c.fecha_compra, p.nombre, t.nombre, c.costo_envio, m.codigo, e.id_estado_compra, e.nombre, u.nombre, u.apellido
         ORDER BY c.fecha_compra DESC, c.id_compra DESC
         LIMIT :limite OFFSET :offset'
    );
    foreach ($parametros as $parametro => $valor) {
        $consultaCompras->bindValue($parametro, $valor, PDO::PARAM_STR);
    }
    $consultaCompras->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consultaCompras->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consultaCompras->execute();

    $compras = array_map(static function (array $compra): array {
        return [
            'id_compra' => (int) $compra['id_compra'],
            'codigo_compra' => $compra['codigo_compra'],
            'fecha_compra' => $compra['fecha_compra'],
            'proveedor' => $compra['proveedor'],
            'transportista' => $compra['transportista'],
            'productos' => (int) $compra['productos'],
            'total' => (float) $compra['total'],
            'moneda' => $compra['moneda'],
            'id_estado_compra' => (int) $compra['id_estado_compra'],
            'estado' => $compra['estado'],
            'responsable' => $compra['responsable'] ?: 'Sin responsable',
        ];
    }, $consultaCompras->fetchAll());

    return [
        'resumen' => [
            'compras' => (int) ($resumen['compras'] ?? 0),
            'articulos' => (int) ($resumen['articulos'] ?? 0),
            'recibidas' => (int) ($resumen['recibidas'] ?? 0),
            'montos' => $montos,
        ],
        'compras' => $compras,
        'opciones' => [
            'proveedores' => obtenerOpcionesHistorial($conexion, 'proveedores', 'id_proveedor', 'nombre'),
            'transportistas' => obtenerOpcionesHistorial($conexion, 'transportistas', 'id_transportista', 'nombre'),
            'estados' => obtenerEstadosHistorial($conexion),
        ],
        'paginacion' => [
            'pagina_actual' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas,
        ],
    ];
}

/* Devuelve las opciones de proveedores o transportistas para los radios */
function obtenerOpcionesHistorial(PDO $conexion, string $tabla, string $campoId, string $campoNombre): array
{
    $tablasPermitidas = [
        'proveedores' => ['id_proveedor', 'nombre'],
        'transportistas' => ['id_transportista', 'nombre'],
    ];
    if (!isset($tablasPermitidas[$tabla]) || $tablasPermitidas[$tabla] !== [$campoId, $campoNombre]) {
        throw new InvalidArgumentException('Catálogo de filtro no válido.');
    }

    $consulta = $conexion->query(
        'SELECT ' . $campoId . ' AS id, ' . $campoNombre . ' AS nombre
         FROM ' . $tabla . '
         ORDER BY ' . $campoNombre
    );

    return array_map(static function (array $opcion): array {
        return ['id' => (int) $opcion['id'], 'nombre' => $opcion['nombre']];
    }, $consulta->fetchAll());
}

/* Devuelve los estados de compra activos para el filtro */
function obtenerEstadosHistorial(PDO $conexion): array
{
    $consulta = $conexion->query(
        'SELECT id_estado_compra AS id, nombre
         FROM estados_compras
         WHERE activo = 1
         ORDER BY id_estado_compra'
    );

    return array_map(static function (array $estado): array {
        return ['id' => (int) $estado['id'], 'nombre' => $estado['nombre']];
    }, $consulta->fetchAll());
}

/* Devuelve toda la información real asociada a una compra específica */
function obtenerDetalleCompra(PDO $conexion, int $idCompra): array
{
    if ($idCompra <= 0) {
        throw new InvalidArgumentException('La compra seleccionada no es válida.');
    }

    $consultaCompra = $conexion->prepare(
        'SELECT
            c.id_compra,
            c.codigo_compra,
            c.fecha_compra,
            c.fecha_estimada_llegada,
            c.fecha_recepcion,
            c.numero_pedido,
            c.costo_envio,
            c.observaciones,
            p.id_proveedor,
            p.nombre AS proveedor,
            p.razon_social,
            p.imagen AS imagen_proveedor,
            p.tipo_imagen AS tipo_imagen_proveedor,
            t.id_transportista,
            t.nombre AS transportista,
            t.imagen AS imagen_transportista,
            t.tipo_imagen AS tipo_imagen_transportista,
            u.id_usuario,
            TRIM(CONCAT(u.nombre, \' \', u.apellido)) AS responsable,
            r.nombre AS rol_responsable,
            m.codigo AS moneda,
            e.id_estado_compra,
            e.nombre AS estado,
            e.descripcion AS estado_descripcion,
            e.color AS estado_color
         FROM compras c
         INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
         INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         INNER JOIN monedas m ON m.id_moneda = c.id_moneda
         INNER JOIN estados_compras e ON e.id_estado_compra = c.id_estado_compra
         LEFT JOIN transportistas t ON t.id_transportista = c.id_transportista
         WHERE c.id_compra = :id_compra
         LIMIT 1'
    );
    $consultaCompra->execute([':id_compra' => $idCompra]);
    $compra = $consultaCompra->fetch();

    if (!$compra) {
        throw new InvalidArgumentException('La compra seleccionada no existe.');
    }

    $consultaArticulos = $conexion->prepare(
        'SELECT
            p.nombre AS producto,
            p.codigo_producto AS codigo,
            d.cantidad,
            d.precio_unitario,
            d.cantidad * d.precio_unitario AS subtotal
         FROM compra_detalle d
         INNER JOIN productos p ON p.id_producto = d.id_producto
         WHERE d.id_compra = :id_compra
         ORDER BY d.id_detalle'
    );
    $consultaArticulos->execute([':id_compra' => $idCompra]);
    $articulos = array_map(static function (array $articulo): array {
        return [
            'producto' => $articulo['producto'],
            'codigo' => $articulo['codigo'],
            'cantidad' => (int) $articulo['cantidad'],
            'precio_unitario' => (float) $articulo['precio_unitario'],
            'subtotal' => (float) $articulo['subtotal'],
        ];
    }, $consultaArticulos->fetchAll());

    $consultaComentarios = $conexion->prepare(
        'SELECT
            cc.id_comentario,
            cc.id_usuario,
            TRIM(CONCAT(u.nombre, \' \', u.apellido)) AS usuario,
            cc.comentario,
            cc.fecha_comentario
         FROM comentarios_compras cc
         INNER JOIN usuarios u ON u.id_usuario = cc.id_usuario
         WHERE cc.id_compra = :id_compra
         ORDER BY cc.fecha_comentario DESC, cc.id_comentario DESC'
    );
    $consultaComentarios->execute([':id_compra' => $idCompra]);
    $idUsuarioSesion = (int) (obtenerUsuarioSesion()['id_usuario'] ?? 0);
    $comentarios = array_map(static function (array $comentario) use ($idUsuarioSesion): array {
        return [
            'id_comentario' => (int) $comentario['id_comentario'],
            'usuario' => $comentario['usuario'] ?: 'Usuario del sistema',
            'comentario' => $comentario['comentario'],
            'fecha' => $comentario['fecha_comentario'],
            'puede_editar' => (int) $comentario['id_usuario'] === $idUsuarioSesion,
        ];
    }, $consultaComentarios->fetchAll());

    $consultaDocumentos = $conexion->prepare(
        'SELECT
            dc.id_documento,
            td.nombre AS tipo,
            dc.numero_documento,
            dc.nombre_archivo,
            dc.tipo_archivo,
            CASE WHEN dc.archivo IS NULL THEN 0 ELSE 1 END AS tiene_archivo,
            OCTET_LENGTH(dc.archivo) AS tamano_bytes,
            dc.fecha_documento,
            dc.monto,
            dc.observaciones
         FROM documentos_compra dc
         INNER JOIN tipos_documentos td ON td.id_tipo_documento = dc.id_tipo_documento
         WHERE dc.id_compra = :id_compra
         ORDER BY dc.fecha_documento DESC, dc.id_documento DESC'
    );
    $consultaDocumentos->execute([':id_compra' => $idCompra]);
    $documentos = array_map(static function (array $documento): array {
        return [
            'id_documento' => (int) $documento['id_documento'],
            'tipo' => $documento['tipo'],
            'numero_documento' => $documento['numero_documento'],
            'nombre_archivo' => $documento['nombre_archivo'],
            'tipo_archivo' => $documento['tipo_archivo'],
            'tiene_archivo' => (bool) $documento['tiene_archivo'],
            'tamano_bytes' => $documento['tamano_bytes'] !== null ? (int) $documento['tamano_bytes'] : 0,
            'fecha_documento' => $documento['fecha_documento'],
            'monto' => $documento['monto'] !== null ? (float) $documento['monto'] : null,
            'observaciones' => $documento['observaciones'],
        ];
    }, $consultaDocumentos->fetchAll());

    $calificaciones = [
        'proveedor' => obtenerEvaluacionCompra($conexion, 'evaluaciones_proveedores', $idCompra),
        'transportista' => $compra['id_transportista'] !== null
            ? obtenerEvaluacionCompra($conexion, 'evaluaciones_transportistas', $idCompra)
            : null,
    ];

    $unidades = array_sum(array_column($articulos, 'cantidad'));
    $subtotalProductos = array_sum(array_column($articulos, 'subtotal'));
    $costoEnvio = (float) $compra['costo_envio'];

    return [
        'compra' => [
            'id_compra' => (int) $compra['id_compra'],
            'codigo_compra' => $compra['codigo_compra'],
            'fecha_compra' => $compra['fecha_compra'],
            'fecha_estimada_llegada' => $compra['fecha_estimada_llegada'],
            'fecha_recepcion' => $compra['fecha_recepcion'],
            'numero_pedido' => $compra['numero_pedido'],
            'costo_envio' => $costoEnvio,
            'observaciones' => $compra['observaciones'],
            'moneda' => $compra['moneda'],
            'id_estado_compra' => (int) $compra['id_estado_compra'],
            'estado' => $compra['estado'],
            'estado_descripcion' => $compra['estado_descripcion'],
            'estado_color' => $compra['estado_color'],
        ],
        'proveedor' => [
            'id' => (int) $compra['id_proveedor'],
            'nombre' => $compra['proveedor'],
            'razon_social' => $compra['razon_social'],
            'imagen' => convertirImagenBlobADataUri($compra['imagen_proveedor'], $compra['tipo_imagen_proveedor']),
        ],
        'transportista' => $compra['id_transportista'] !== null ? [
            'id' => (int) $compra['id_transportista'],
            'nombre' => $compra['transportista'],
            'imagen' => convertirImagenBlobADataUri($compra['imagen_transportista'], $compra['tipo_imagen_transportista']),
        ] : null,
        'responsable' => [
            'id' => (int) $compra['id_usuario'],
            'nombre' => $compra['responsable'] ?: 'Sin responsable',
            'rol' => $compra['rol_responsable'] ?: 'Sin rol asignado',
        ],
        'articulos' => $articulos,
        'comentarios' => $comentarios,
        'calificaciones' => $calificaciones,
        'documentos' => $documentos,
        'tipos_documentos' => obtenerTiposDocumentosCompra($conexion),
        'estados_compra' => obtenerEstadosHistorial($conexion),
        'totales' => [
            'unidades' => $unidades,
            'subtotal_productos' => (float) $subtotalProductos,
            'costo_envio' => $costoEnvio,
            'total' => (float) ($subtotalProductos + $costoEnvio),
            'moneda' => $compra['moneda'],
        ],
    ];
}

/* Devuelve los tipos de documento activos para el formulario de carga */
function obtenerTiposDocumentosCompra(PDO $conexion): array
{
    $consulta = $conexion->query(
        'SELECT id_tipo_documento AS id, nombre
         FROM tipos_documentos
         WHERE activo = 1
         ORDER BY nombre'
    );

    return array_map(static function (array $tipo): array {
        return ['id' => (int) $tipo['id'], 'nombre' => $tipo['nombre']];
    }, $consulta->fetchAll());
}

/* Actualiza el estado de una compra usando un estado activo del catálogo */
function actualizarEstadoCompra(PDO $conexion): array
{
    $idCompra = (int) ($_POST['id_compra'] ?? 0);
    $idEstado = (int) ($_POST['id_estado_compra'] ?? 0);

    if ($idCompra <= 0 || $idEstado <= 0) {
        throw new InvalidArgumentException('La compra o el estado no son válidos.');
    }

    $consultaCompra = $conexion->prepare('SELECT id_compra FROM compras WHERE id_compra = :id_compra LIMIT 1');
    $consultaCompra->execute([':id_compra' => $idCompra]);
    if (!$consultaCompra->fetch()) {
        throw new InvalidArgumentException('La compra seleccionada no existe.');
    }

    $consultaEstado = $conexion->prepare(
        'SELECT id_estado_compra
         FROM estados_compras
         WHERE id_estado_compra = :id_estado AND activo = 1
         LIMIT 1'
    );
    $consultaEstado->execute([':id_estado' => $idEstado]);
    if (!$consultaEstado->fetch()) {
        throw new InvalidArgumentException('El estado seleccionado no existe o está inactivo.');
    }

    $actualizar = $conexion->prepare(
        'UPDATE compras
         SET id_estado_compra = :id_estado
         WHERE id_compra = :id_compra'
    );
    $actualizar->execute([
        ':id_estado' => $idEstado,
        ':id_compra' => $idCompra,
    ]);

    return ['mensaje' => 'El estado de la compra se actualizó correctamente.'];
}

/* Crea un comentario o actualiza uno existente del usuario autenticado. */
function guardarComentarioCompra(PDO $conexion): array
{
    $idCompra = (int) ($_POST['id_compra'] ?? 0);
    $idComentario = (int) ($_POST['id_comentario'] ?? 0);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));
    $idUsuario = (int) (obtenerUsuarioSesion()['id_usuario'] ?? 0);

    if ($idCompra <= 0 || $idUsuario <= 0) {
        throw new InvalidArgumentException('La compra o el usuario no son válidos.');
    }
    if ($comentario === '') {
        throw new InvalidArgumentException('Escribe un comentario antes de guardar.');
    }
    if (strlen($comentario) > 1000) {
        throw new InvalidArgumentException('El comentario no puede superar los 1000 caracteres.');
    }

    $consultaCompra = $conexion->prepare('SELECT id_compra FROM compras WHERE id_compra = :id_compra LIMIT 1');
    $consultaCompra->execute([':id_compra' => $idCompra]);
    if (!$consultaCompra->fetch()) {
        throw new InvalidArgumentException('La compra seleccionada no existe.');
    }

    if ($idComentario > 0) {
        $consultaComentario = $conexion->prepare(
            'SELECT id_comentario
             FROM comentarios_compras
             WHERE id_comentario = :id_comentario
               AND id_compra = :id_compra
               AND id_usuario = :id_usuario
             LIMIT 1'
        );
        $consultaComentario->execute([
            ':id_comentario' => $idComentario,
            ':id_compra' => $idCompra,
            ':id_usuario' => $idUsuario,
        ]);
        if (!$consultaComentario->fetch()) {
            throw new InvalidArgumentException('No puedes editar este comentario.');
        }

        $actualizar = $conexion->prepare(
            'UPDATE comentarios_compras
             SET comentario = :comentario
             WHERE id_comentario = :id_comentario
               AND id_compra = :id_compra
               AND id_usuario = :id_usuario'
        );
        $actualizar->execute([
            ':comentario' => $comentario,
            ':id_comentario' => $idComentario,
            ':id_compra' => $idCompra,
            ':id_usuario' => $idUsuario,
        ]);

        return ['mensaje' => 'El comentario se actualizó correctamente.'];
    }

    $insertar = $conexion->prepare(
        'INSERT INTO comentarios_compras (id_compra, id_usuario, comentario)
         VALUES (:id_compra, :id_usuario, :comentario)'
    );
    $insertar->execute([
        ':id_compra' => $idCompra,
        ':id_usuario' => $idUsuario,
        ':comentario' => $comentario,
    ]);

    return ['mensaje' => 'El comentario se registro correctamente.'];
}

/* Obtiene el promedio y el comentario más reciente de una evaluación de compra */
function obtenerEvaluacionCompra(PDO $conexion, string $tabla, int $idCompra): ?array
{
    $tablasPermitidas = ['evaluaciones_proveedores', 'evaluaciones_transportistas'];
    if (!in_array($tabla, $tablasPermitidas, true)) {
        throw new InvalidArgumentException('Tabla de evaluación no válida.');
    }

    $consultaPromedio = $conexion->prepare(
        'SELECT AVG(calificacion) AS promedio, COUNT(*) AS cantidad
         FROM ' . $tabla . '
         WHERE id_compra = :id_compra'
    );
    $consultaPromedio->execute([':id_compra' => $idCompra]);
    $promedio = $consultaPromedio->fetch() ?: [];

    if ((int) ($promedio['cantidad'] ?? 0) === 0) {
        return null;
    }

    $consultaComentario = $conexion->prepare(
        'SELECT id_evaluacion, calificacion, comentario, fecha_evaluacion
         FROM ' . $tabla . '
         WHERE id_compra = :id_compra
         ORDER BY fecha_evaluacion DESC, id_evaluacion DESC
         LIMIT 1'
    );
    $consultaComentario->execute([':id_compra' => $idCompra]);
    $detalle = $consultaComentario->fetch() ?: [];

    return [
        'id_evaluacion' => (int) ($detalle['id_evaluacion'] ?? 0),
        'promedio' => round((float) $promedio['promedio'], 1),
        'cantidad' => (int) $promedio['cantidad'],
        'calificacion' => (float) ($detalle['calificacion'] ?? 0),
        'comentario' => $detalle['comentario'] ?? null,
        'fecha' => $detalle['fecha_evaluacion'] ?? null,
    ];
}

/* Guarda una calificación nueva o actualiza la evaluación mostrada de una compra recibida */
function guardarEvaluacionCompra(PDO $conexion): array
{
    $idCompra = (int) ($_POST['id_compra'] ?? 0);
    $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
    $tipo = trim((string) ($_POST['tipo'] ?? ''));
    $calificacion = (float) ($_POST['calificacion'] ?? 0);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));
    $usuarioSesion = obtenerUsuarioSesion();
    $idUsuario = (int) ($usuarioSesion['id_usuario'] ?? 0);

    if ($idCompra <= 0 || $idUsuario <= 0) {
        throw new InvalidArgumentException('La compra o el usuario no son válidos.');
    }
    if (!in_array($tipo, ['proveedor', 'transportista'], true)) {
        throw new InvalidArgumentException('El tipo de evaluación no es válido.');
    }
    if ($calificacion < 1 || $calificacion > 5 || floor($calificacion) !== $calificacion) {
        throw new InvalidArgumentException('La calificación debe estar entre 1 y 5 estrellas.');
    }
    if (strlen($comentario) > 1000) {
        throw new InvalidArgumentException('El comentario no puede superar los 1000 caracteres.');
    }

    $consultaCompra = $conexion->prepare(
        'SELECT compras.id_transportista, compras.id_estado_compra
         FROM compras
         INNER JOIN estados_compras e ON e.id_estado_compra = compras.id_estado_compra
         WHERE id_compra = :id_compra AND e.nombre = \'recibida\'
         LIMIT 1'
    );
    $consultaCompra->execute([':id_compra' => $idCompra]);
    $compra = $consultaCompra->fetch();

    if (!$compra) {
        throw new InvalidArgumentException('Solo se pueden calificar compras recibidas.');
    }
    if ($tipo === 'transportista' && $compra['id_transportista'] === null) {
        throw new InvalidArgumentException('La compra no tiene un transportista asociado.');
    }

    $tabla = $tipo === 'proveedor' ? 'evaluaciones_proveedores' : 'evaluaciones_transportistas';
    if ($idEvaluacion > 0) {
        $consultaEvaluacion = $conexion->prepare(
            'SELECT id_evaluacion
             FROM ' . $tabla . '
             WHERE id_evaluacion = :id_evaluacion AND id_compra = :id_compra
             LIMIT 1'
        );
        $consultaEvaluacion->execute([
            ':id_evaluacion' => $idEvaluacion,
            ':id_compra' => $idCompra,
        ]);
        if (!$consultaEvaluacion->fetch()) {
            throw new InvalidArgumentException('La evaluación seleccionada no existe para esta compra.');
        }

        $actualizar = $conexion->prepare(
            'UPDATE ' . $tabla . '
             SET calificacion = :calificacion,
                 comentario = :comentario,
                 fecha_evaluacion = CURRENT_TIMESTAMP
             WHERE id_evaluacion = :id_evaluacion AND id_compra = :id_compra'
        );
        $actualizar->execute([
            ':calificacion' => $calificacion,
            ':comentario' => $comentario !== '' ? $comentario : null,
            ':id_evaluacion' => $idEvaluacion,
            ':id_compra' => $idCompra,
        ]);

        return ['mensaje' => 'La calificación original se actualizó correctamente.'];
    }

    $consulta = $conexion->prepare(
        'INSERT INTO ' . $tabla . ' (id_compra, id_usuario, calificacion, comentario)
         VALUES (:id_compra, :id_usuario, :calificacion, :comentario)
         ON DUPLICATE KEY UPDATE
            calificacion = :calificacion_actual,
            comentario = :comentario_actual,
            fecha_evaluacion = CURRENT_TIMESTAMP'
    );
    $consulta->execute([
        ':id_compra' => $idCompra,
        ':id_usuario' => $idUsuario,
        ':calificacion' => $calificacion,
        ':comentario' => $comentario !== '' ? $comentario : null,
        ':calificacion_actual' => $calificacion,
        ':comentario_actual' => $comentario !== '' ? $comentario : null,
    ]);

    return ['mensaje' => 'La calificación se guardó correctamente.'];
}

/* Guarda un documento PDF, Word o imagen directamente en la compra seleccionada */
function subirDocumentoCompra(PDO $conexion): array
{
    $idCompra = (int) ($_POST['id_compra'] ?? 0);
    $idTipoDocumento = (int) ($_POST['id_tipo_documento'] ?? 0);

    if ($idCompra <= 0 || $idTipoDocumento <= 0) {
        throw new InvalidArgumentException('La compra o el tipo de documento no son válidos.');
    }
    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new InvalidArgumentException('Selecciona un documento para cargar.');
    }
    if ($_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('No fue posible recibir el documento.');
    }
    if ((int) $_FILES['documento']['size'] > 10 * 1024 * 1024) {
        throw new InvalidArgumentException('El documento no puede superar los 10 MB.');
    }

    $consultaCompra = $conexion->prepare('SELECT id_compra FROM compras WHERE id_compra = :id_compra LIMIT 1');
    $consultaCompra->execute([':id_compra' => $idCompra]);
    if (!$consultaCompra->fetch()) {
        throw new InvalidArgumentException('La compra seleccionada no existe.');
    }

    $consultaTipo = $conexion->prepare(
        'SELECT id_tipo_documento FROM tipos_documentos WHERE id_tipo_documento = :id_tipo AND activo = 1 LIMIT 1'
    );
    $consultaTipo->execute([':id_tipo' => $idTipoDocumento]);
    if (!$consultaTipo->fetch()) {
        throw new InvalidArgumentException('El tipo de documento seleccionado no existe.');
    }

    $tiposPermitidos = [
        'application/pdf' => 'application/pdf',
        'application/msword' => 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg' => 'image/jpeg',
        'image/png' => 'image/png',
        'image/webp' => 'image/webp',
        'image/gif' => 'image/gif',
    ];
    $nombreArchivo = basename((string) $_FILES['documento']['name']);
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    $tipoDetectado = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['documento']['tmp_name']);
    $tipoArchivo = $tiposPermitidos[$tipoDetectado] ?? null;

    /* Algunos servidores detectan los documentos DOCX como ZIP; la extension confirma el formato esperado */
    if ($tipoArchivo === null && $extension === 'docx') {
        $tipoArchivo = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
    if ($tipoArchivo === null && $extension === 'doc') {
        $tipoArchivo = 'application/msword';
    }
    if ($tipoArchivo === null) {
        throw new InvalidArgumentException('El archivo debe ser PDF, Word, JPG, PNG, WEBP o GIF.');
    }

    $archivo = file_get_contents($_FILES['documento']['tmp_name']);
    if ($archivo === false) {
        throw new InvalidArgumentException('No fue posible leer el documento seleccionado.');
    }

    $consulta = $conexion->prepare(
        'INSERT INTO documentos_compra
            (id_compra, id_tipo_documento, nombre_archivo, archivo, tipo_archivo, fecha_documento)
         VALUES
            (:id_compra, :id_tipo_documento, :nombre_archivo, :archivo, :tipo_archivo, CURRENT_DATE)'
    );
    $consulta->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
    $consulta->bindValue(':id_tipo_documento', $idTipoDocumento, PDO::PARAM_INT);
    $consulta->bindValue(':nombre_archivo', $nombreArchivo, PDO::PARAM_STR);
    $consulta->bindValue(':archivo', $archivo, PDO::PARAM_LOB);
    $consulta->bindValue(':tipo_archivo', $tipoArchivo, PDO::PARAM_STR);
    $consulta->execute();

    return [
        'id_documento' => (int) $conexion->lastInsertId(),
        'mensaje' => 'El documento se cargo correctamente.',
    ];
}

/* Obtiene el archivo binario de un documento para previsualizarlo o descargarlo */
function obtenerDocumentoCompra(PDO $conexion, int $idDocumento): array
{
    if ($idDocumento <= 0) {
        throw new InvalidArgumentException('El documento solicitado no es válido.');
    }

    $consulta = $conexion->prepare(
        'SELECT id_documento, nombre_archivo, tipo_archivo, archivo
         FROM documentos_compra
         WHERE id_documento = :id_documento
         LIMIT 1'
    );
    $consulta->execute([':id_documento' => $idDocumento]);
    $documento = $consulta->fetch();

    if (!$documento || $documento['archivo'] === null) {
        throw new InvalidArgumentException('El documento no tiene un archivo almacenado.');
    }

    return [
        'id_documento' => (int) $documento['id_documento'],
        'nombre_archivo' => $documento['nombre_archivo'] ?: 'documento',
        'tipo_archivo' => $documento['tipo_archivo'] ?: 'application/octet-stream',
        'archivo' => $documento['archivo'],
    ];
}

/* Elimina un documento almacenado en la compra seleccionada */
function eliminarDocumentoCompra(PDO $conexion): array
{
    $idDocumento = (int) ($_POST['id_documento'] ?? 0);
    if ($idDocumento <= 0) {
        throw new InvalidArgumentException('El documento seleccionado no es válido.');
    }

    $consulta = $conexion->prepare('DELETE FROM documentos_compra WHERE id_documento = :id_documento');
    $consulta->execute([':id_documento' => $idDocumento]);
    if ($consulta->rowCount() === 0) {
        throw new InvalidArgumentException('El documento seleccionado no existe.');
    }

    return ['mensaje' => 'El documento se elimino correctamente.'];
}
