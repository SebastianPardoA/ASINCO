<?php
/**
 * proveedores_api.php
 *
 * Consulta proveedores, compras, artículos y evaluaciones
 */

require_once __DIR__ . '/../../configuracion/imagenes.php';

/* Devuelve los proveedores mejor evaluados para el paso de preparación */
function listarProveedoresSugeridosCompra(PDO $conexion): array
{
    $consulta = $conexion->query(
        'SELECT
            p.id_proveedor,
            p.nombre,
            COALESCE(NULLIF(p.razon_social, \'\'), p.nombre) AS razon_social,
            (SELECT COUNT(DISTINCT c.id_compra)
             FROM compras c
             WHERE c.id_proveedor = p.id_proveedor) AS compras,
            (SELECT AVG(ep.calificacion)
             FROM evaluaciones_proveedores ep
             INNER JOIN compras c ON c.id_compra = ep.id_compra
             WHERE c.id_proveedor = p.id_proveedor) AS calificacion
         FROM proveedores p
         ORDER BY calificacion IS NULL ASC, calificacion DESC, compras DESC, p.nombre ASC
         LIMIT 5'
    );

    return array_map(static function (array $proveedor): array {
        return [
            'id_proveedor' => (int) $proveedor['id_proveedor'],
            'nombre' => $proveedor['nombre'],
            'razon_social' => $proveedor['razon_social'],
            'compras' => (int) $proveedor['compras'],
            'calificacion' => $proveedor['calificacion'] !== null ? (float) $proveedor['calificacion'] : null,
        ];
    }, $consulta->fetchAll());
}

/* Busca en todo el catálogo de proveedores y pagina cinco coincidencias */
function buscarProveedoresCatalogoCompra(PDO $conexion, string $busqueda, int $pagina = 1): array
{
    $busqueda = trim($busqueda);
    if (strlen($busqueda) < 2 || strlen($busqueda) > 300) {
        throw new InvalidArgumentException('Escribe al menos dos caracteres y usa una búsqueda más corta.');
    }

    $pagina = max(1, $pagina);
    $patron = '%' . $busqueda . '%';
    $condicion = '(p.nombre LIKE :nombre OR p.razon_social LIKE :razon_social OR p.rut LIKE :rut OR p.correo_electronico LIKE :correo)';
    $parametros = [
        'nombre' => $patron,
        'razon_social' => $patron,
        'rut' => $patron,
        'correo' => $patron,
    ];

    $totalConsulta = $conexion->prepare('SELECT COUNT(*) FROM proveedores p WHERE ' . $condicion);
    $totalConsulta->execute($parametros);
    $total = (int) $totalConsulta->fetchColumn();
    $porPagina = 5;
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $consulta = $conexion->prepare(
        'SELECT
            p.id_proveedor,
            p.nombre,
            COALESCE(NULLIF(p.razon_social, \'\'), p.nombre) AS razon_social,
            (SELECT COUNT(DISTINCT c.id_compra)
             FROM compras c
             WHERE c.id_proveedor = p.id_proveedor) AS compras,
            (SELECT AVG(ep.calificacion)
             FROM evaluaciones_proveedores ep
             INNER JOIN compras c ON c.id_compra = ep.id_compra
             WHERE c.id_proveedor = p.id_proveedor) AS calificacion
         FROM proveedores p
         WHERE ' . $condicion . '
         ORDER BY CASE WHEN p.nombre = :nombre_exacto THEN 0 ELSE 1 END, p.nombre ASC
         LIMIT :limite OFFSET :offset'
    );
    foreach ($parametros as $nombre => $valor) {
        $consulta->bindValue(':' . $nombre, $valor, PDO::PARAM_STR);
    }
    $consulta->bindValue(':nombre_exacto', $busqueda, PDO::PARAM_STR);
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consulta->execute();

    return [
        'resultados' => array_map(static function (array $proveedor): array {
            return [
                'id_proveedor' => (int) $proveedor['id_proveedor'],
                'nombre' => $proveedor['nombre'],
                'razon_social' => $proveedor['razon_social'],
                'compras' => (int) $proveedor['compras'],
                'calificacion' => $proveedor['calificacion'] !== null ? (float) $proveedor['calificacion'] : null,
            ];
        }, $consulta->fetchAll()),
        'paginacion' => [
            'pagina_actual' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'total_paginas' => $totalPaginas,
        ],
    ];
}

/* Crea un proveedor desde el formulario del paso de preparación */
function crearProveedorRegistroCompra(PDO $conexion): array
{
    $datos = [
        'nombre' => trim((string) ($_POST['nombre'] ?? '')),
        'razon_social' => trim((string) ($_POST['razon_social'] ?? '')),
        'rut' => trim((string) ($_POST['rut'] ?? '')),
        'correo_electronico' => trim((string) ($_POST['correo_electronico'] ?? '')),
        'telefono' => trim((string) ($_POST['telefono'] ?? '')),
        'direccion' => trim((string) ($_POST['direccion'] ?? '')),
    ];

    if ($datos['nombre'] === '' || $datos['razon_social'] === '' || $datos['rut'] === '') {
        throw new InvalidArgumentException('Completa el nombre comercial, la razón social y el RUT.');
    }
    if (strlen($datos['nombre']) > 150 || strlen($datos['razon_social']) > 200 || strlen($datos['rut']) > 20
        || strlen($datos['correo_electronico']) > 150 || strlen($datos['telefono']) > 30 || strlen($datos['direccion']) > 255) {
        throw new InvalidArgumentException('Uno de los datos supera el largo permitido.');
    }
    if ($datos['correo_electronico'] !== '' && !filter_var($datos['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('El correo electrónico no es válido.');
    }

    $verificar = $conexion->prepare('SELECT id_proveedor FROM proveedores WHERE rut = :rut LIMIT 1');
    $verificar->execute(['rut' => $datos['rut']]);
    if ($verificar->fetchColumn()) {
        throw new InvalidArgumentException('Ya existe un proveedor registrado con ese RUT.');
    }

    $insertar = $conexion->prepare(
        'INSERT INTO proveedores (nombre, razon_social, rut, correo_electronico, telefono, direccion)
         VALUES (:nombre, :razon_social, :rut, :correo_electronico, :telefono, :direccion)'
    );
    $insertar->execute([
        'nombre' => $datos['nombre'],
        'razon_social' => $datos['razon_social'],
        'rut' => $datos['rut'],
        'correo_electronico' => $datos['correo_electronico'] !== '' ? $datos['correo_electronico'] : null,
        'telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
        'direccion' => $datos['direccion'] !== '' ? $datos['direccion'] : null,
    ]);

    return [
        'id_proveedor' => (int) $conexion->lastInsertId(),
        'nombre' => $datos['nombre'],
        'razon_social' => $datos['razon_social'],
    ];
}

/* Devuelve el resumen y una página de proveedores registrados */
function listarProveedores(PDO $conexion, int $pagina = 1, int $porPagina = 5, string $busqueda = '')
{
    $pagina = max(1, $pagina);
    $porPagina = min(20, max(1, $porPagina));
    $busqueda = trim($busqueda);

    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('El texto de búsqueda es demasiado largo.');
    }

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== '') {
        $condiciones[] = '(p.nombre LIKE :busqueda_nombre OR p.razon_social LIKE :busqueda_razon OR p.correo_electronico LIKE :busqueda_correo)';
        $parametros['busqueda_nombre'] = '%' . $busqueda . '%';
        $parametros['busqueda_razon'] = '%' . $busqueda . '%';
        $parametros['busqueda_correo'] = '%' . $busqueda . '%';
    }

    $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
    $consultaTotal = $conexion->prepare('SELECT COUNT(*) FROM proveedores p' . $where);
    $consultaTotal->execute($parametros);
    $total = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $resumen = $conexion->query(
        'SELECT
            COUNT(DISTINCT p.id_proveedor) AS proveedores,
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE(SUM(d.cantidad), 0) AS articulos,
            (SELECT AVG(calificacion) FROM evaluaciones_proveedores) AS calificacion_promedio
         FROM proveedores p
         LEFT JOIN compras c ON c.id_proveedor = p.id_proveedor
         LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra'
    )->fetch() ?: [];

    $montoComprado = $conexion->query(
        'SELECT
            COALESCE(SUM(CASE
                WHEN m.codigo = \'CLP\' THEN d.cantidad * d.precio_unitario
                WHEN m.codigo = \'USD\' AND c.tipo_cambio_usd_clp > 0 THEN d.cantidad * d.precio_unitario * c.tipo_cambio_usd_clp
                ELSE 0
            END), 0) AS total_clp,
            COUNT(DISTINCT CASE
                WHEN m.codigo = \'USD\' AND (c.tipo_cambio_usd_clp IS NULL OR c.tipo_cambio_usd_clp <= 0) THEN c.id_compra
                ELSE NULL
            END) AS compras_sin_tasa
         FROM compras c
         INNER JOIN monedas m ON m.id_moneda = c.id_moneda
         LEFT JOIN compra_detalle d ON d.id_compra = c.id_compra'
    )->fetch() ?: [];

    $consulta = $conexion->prepare(
        'SELECT
            p.id_proveedor,
            p.nombre,
            p.imagen,
            p.tipo_imagen,
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE((
                SELECT SUM(d2.cantidad)
                FROM compras c2
                INNER JOIN compra_detalle d2 ON d2.id_compra = c2.id_compra
                WHERE c2.id_proveedor = p.id_proveedor
            ), 0) AS articulos,
            COALESCE((
                SELECT SUM(CASE
                    WHEN m3.codigo = \'CLP\' THEN d3.cantidad * d3.precio_unitario
                    WHEN m3.codigo = \'USD\' AND c3.tipo_cambio_usd_clp > 0 THEN d3.cantidad * d3.precio_unitario * c3.tipo_cambio_usd_clp
                    ELSE 0
                END)
                FROM compras c3
                INNER JOIN compra_detalle d3 ON d3.id_compra = c3.id_compra
                INNER JOIN monedas m3 ON m3.id_moneda = c3.id_moneda
                WHERE c3.id_proveedor = p.id_proveedor
            ), 0) AS monto_total,
            MAX(c.fecha_compra) AS ultima_compra,
            (
                SELECT AVG(ep2.calificacion)
                FROM evaluaciones_proveedores ep2
                INNER JOIN compras c5 ON c5.id_compra = ep2.id_compra
                WHERE c5.id_proveedor = p.id_proveedor
            ) AS calificacion
         FROM proveedores p
         LEFT JOIN compras c ON c.id_proveedor = p.id_proveedor
         ' . $where . '
         GROUP BY p.id_proveedor, p.nombre, p.imagen, p.tipo_imagen
         ORDER BY p.nombre ASC
         LIMIT :limite OFFSET :offset'
    );

    if ($busqueda !== '') {
        $consulta->bindValue(':busqueda_nombre', '%' . $busqueda . '%', PDO::PARAM_STR);
        $consulta->bindValue(':busqueda_razon', '%' . $busqueda . '%', PDO::PARAM_STR);
        $consulta->bindValue(':busqueda_correo', '%' . $busqueda . '%', PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consulta->execute();

    $proveedores = [];
    foreach ($consulta->fetchAll() as $proveedor) {
        $proveedores[] = [
            'id_proveedor' => (int) $proveedor['id_proveedor'],
            'nombre' => $proveedor['nombre'],
            'imagen' => convertirImagenBlobADataUri($proveedor['imagen'], $proveedor['tipo_imagen']),
            'compras' => (int) $proveedor['compras'],
            'articulos' => (int) $proveedor['articulos'],
            'monto_total' => (float) $proveedor['monto_total'],
            'moneda' => 'CLP',
            'ultima_compra' => $proveedor['ultima_compra'],
            'calificacion' => $proveedor['calificacion'] !== null ? (float) $proveedor['calificacion'] : null,
        ];
    }

    return [
        'resumen' => [
            'proveedores' => (int) ($resumen['proveedores'] ?? 0),
            'compras' => (int) ($resumen['compras'] ?? 0),
            'articulos' => (int) ($resumen['articulos'] ?? 0),
            'calificacion_promedio' => $resumen['calificacion_promedio'] !== null ? (float) $resumen['calificacion_promedio'] : null,
            'monto_clp' => (float) ($montoComprado['total_clp'] ?? 0),
            'compras_sin_tasa' => (int) ($montoComprado['compras_sin_tasa'] ?? 0),
        ],
        'proveedores' => $proveedores,
        'rankings' => [
            'mejores' => obtenerRankingProveedores($conexion, 'DESC'),
            'peores' => obtenerRankingProveedores($conexion, 'ASC'),
        ],
        'paginacion' => ['pagina_actual' => $pagina, 'por_pagina' => $porPagina, 'total' => $total, 'total_paginas' => $totalPaginas],
    ];
}

/* Devuelve el detalle completo de un proveedor para el modal */
function obtenerProveedor(PDO $conexion, int $idProveedor)
{
    if ($idProveedor <= 0) {
        throw new InvalidArgumentException('El proveedor solicitado no es válido.');
    }

    $consulta = $conexion->prepare(
        'SELECT
            p.id_proveedor,
            p.nombre,
            p.razon_social,
            p.rut,
            p.correo_electronico,
            p.telefono,
            p.direccion,
            p.imagen,
            p.tipo_imagen,
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE((
                SELECT SUM(d.cantidad)
                FROM compras c2
                INNER JOIN compra_detalle d ON d.id_compra = c2.id_compra
                WHERE c2.id_proveedor = p.id_proveedor
            ), 0) AS articulos,
            COALESCE((
                SELECT SUM(CASE
                    WHEN m3.codigo = \'CLP\' THEN d2.cantidad * d2.precio_unitario
                    WHEN m3.codigo = \'USD\' AND c3.tipo_cambio_usd_clp > 0 THEN d2.cantidad * d2.precio_unitario * c3.tipo_cambio_usd_clp
                    ELSE 0
                END)
                FROM compras c3
                INNER JOIN compra_detalle d2 ON d2.id_compra = c3.id_compra
                INNER JOIN monedas m3 ON m3.id_moneda = c3.id_moneda
                WHERE c3.id_proveedor = p.id_proveedor
            ), 0) AS monto_total,
            (
                SELECT AVG(ep.calificacion)
                FROM evaluaciones_proveedores ep
                INNER JOIN compras c5 ON c5.id_compra = ep.id_compra
                WHERE c5.id_proveedor = p.id_proveedor
            ) AS calificacion,
            (
                SELECT ep2.comentario
                FROM evaluaciones_proveedores ep2
                INNER JOIN compras c6 ON c6.id_compra = ep2.id_compra
                WHERE c6.id_proveedor = p.id_proveedor
                ORDER BY ep2.fecha_evaluacion DESC, ep2.id_evaluacion DESC
                LIMIT 1
            ) AS ultimo_comentario
         FROM proveedores p
         LEFT JOIN compras c ON c.id_proveedor = p.id_proveedor
         WHERE p.id_proveedor = :id_proveedor
         GROUP BY p.id_proveedor, p.nombre, p.razon_social, p.rut, p.correo_electronico, p.telefono, p.direccion, p.imagen, p.tipo_imagen'
    );
    $consulta->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
    $consulta->execute();
    $proveedor = $consulta->fetch();

    if (!$proveedor) {
        throw new InvalidArgumentException('El proveedor solicitado no existe.');
    }

    $comentarios = obtenerUltimosComentariosProveedor($conexion, $idProveedor);

    return [
        'id_proveedor' => (int) $proveedor['id_proveedor'],
        'nombre' => $proveedor['nombre'],
        'razon_social' => $proveedor['razon_social'] ?: 'Sin razón social registrada',
        'rut' => $proveedor['rut'] ?: 'Sin RUT registrado',
        'correo_electronico' => $proveedor['correo_electronico'] ?: 'Sin correo registrado',
        'telefono' => $proveedor['telefono'] ?: 'Sin teléfono registrado',
        'direccion' => $proveedor['direccion'] ?: 'Sin dirección registrada',
        'imagen' => convertirImagenBlobADataUri($proveedor['imagen'], $proveedor['tipo_imagen']),
        'compras' => (int) $proveedor['compras'],
        'articulos' => (int) $proveedor['articulos'],
        'monto_total' => (float) $proveedor['monto_total'],
        'moneda' => 'CLP',
        'calificacion' => $proveedor['calificacion'] !== null ? (float) $proveedor['calificacion'] : null,
        'ultimo_comentario' => $comentarios[0]['comentario'] ?? 'Sin comentarios registrados.',
        'comentarios' => $comentarios,
    ];
}

/* Actualiza los campos editables y la imagen BLOB de un proveedor */
function actualizarProveedor(PDO $conexion): array
{
    $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
    if ($idProveedor <= 0) {
        throw new InvalidArgumentException('El proveedor solicitado no es válido.');
    }

    $camposPermitidos = [
        'razon_social' => ['maximo' => 200, 'etiqueta' => 'La razón social'],
        'rut' => ['maximo' => 20, 'etiqueta' => 'El RUT'],
        'direccion' => ['maximo' => 255, 'etiqueta' => 'La dirección'],
        'correo_electronico' => ['maximo' => 150, 'etiqueta' => 'El correo electrónico'],
        'telefono' => ['maximo' => 30, 'etiqueta' => 'El teléfono'],
    ];
    $valores = [];

    foreach ($camposPermitidos as $campo => $configuracion) {
        if (!array_key_exists($campo, $_POST)) {
            continue;
        }

        $valor = trim((string) $_POST[$campo]);
        if (strlen($valor) > $configuracion['maximo']) {
            throw new InvalidArgumentException($configuracion['etiqueta'] . ' supera el largo permitido.');
        }
        if ($campo === 'correo_electronico' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        $valores[$campo] = $valor === '' ? null : $valor;
    }

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No fue posible recibir la imagen.');
        }
        if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('La imagen no puede superar los 5 MB.');
        }

        $contenidoImagen = file_get_contents($_FILES['imagen']['tmp_name']);
        $informacionArchivo = finfo_open(FILEINFO_MIME_TYPE);
        $tipoImagen = $informacionArchivo ? finfo_buffer($informacionArchivo, $contenidoImagen) : '';
        if ($informacionArchivo) {
            finfo_close($informacionArchivo);
        }

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($tipoImagen, $tiposPermitidos, true)) {
            throw new InvalidArgumentException('La imagen debe estar en formato JPG, PNG, WEBP o GIF.');
        }

        $valores['imagen'] = $contenidoImagen;
        $valores['nombre_imagen'] = basename((string) $_FILES['imagen']['name']);
        $valores['tipo_imagen'] = $tipoImagen;
    }

    if (!$valores) {
        throw new InvalidArgumentException('No se recibieron cambios para guardar.');
    }

    $actualizaciones = [];
    foreach ($valores as $campo => $valor) {
        $actualizaciones[] = $campo . ' = :' . $campo;
    }
    $consulta = $conexion->prepare(
        'UPDATE proveedores SET ' . implode(', ', $actualizaciones) . ' WHERE id_proveedor = :id_proveedor'
    );
    foreach ($valores as $campo => $valor) {
        $consulta->bindValue(':' . $campo, $valor, $campo === 'imagen' ? PDO::PARAM_LOB : ($valor === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
    }
    $consulta->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
    $consulta->execute();

    if ($consulta->rowCount() === 0) {
        $verificacion = $conexion->prepare('SELECT id_proveedor FROM proveedores WHERE id_proveedor = :id_proveedor');
        $verificacion->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
        $verificacion->execute();
        if (!$verificacion->fetchColumn()) {
            throw new InvalidArgumentException('El proveedor solicitado no existe.');
        }
    }

    return ['mensaje' => 'Los datos del proveedor se actualizaron correctamente.'];
}

/* Obtiene los últimos cinco comentarios asociados a un proveedor */
function obtenerUltimosComentariosProveedor(PDO $conexion, int $idProveedor): array
{
    $consulta = $conexion->prepare(
        'SELECT
            ep.calificacion,
            ep.comentario,
            ep.fecha_evaluacion,
            c.codigo_compra,
            CONCAT(u.nombre, \' \', u.apellido) AS usuario
         FROM evaluaciones_proveedores ep
         INNER JOIN compras c ON c.id_compra = ep.id_compra
         INNER JOIN usuarios u ON u.id_usuario = ep.id_usuario
         WHERE c.id_proveedor = :id_proveedor
           AND ep.comentario IS NOT NULL
           AND TRIM(ep.comentario) <> \'\'
         ORDER BY ep.fecha_evaluacion DESC, ep.id_evaluacion DESC
         LIMIT 5'
    );
    $consulta->bindValue(':id_proveedor', $idProveedor, PDO::PARAM_INT);
    $consulta->execute();

    return array_map(static function (array $comentario): array {
        return [
            'comentario' => $comentario['comentario'],
            'calificacion' => (float) $comentario['calificacion'],
            'fecha' => $comentario['fecha_evaluacion'],
            'compra' => $comentario['codigo_compra'],
            'usuario' => trim($comentario['usuario']) ?: 'Usuario',
        ];
    }, $consulta->fetchAll());
}

/* Obtiene los proveedores con evaluaciones ordenados por calificación */
function obtenerRankingProveedores(PDO $conexion, string $orden)
{
    $ordenSeguro = $orden === 'ASC' ? 'ASC' : 'DESC';
    $consulta = $conexion->query(
        'SELECT
            p.id_proveedor,
            p.nombre,
            (
                SELECT COUNT(DISTINCT c2.id_compra)
                FROM compras c2
                WHERE c2.id_proveedor = p.id_proveedor
            ) AS compras,
            COALESCE((
                SELECT SUM(CASE
                    WHEN m3.codigo = \'CLP\' THEN d2.cantidad * d2.precio_unitario
                    WHEN m3.codigo = \'USD\' AND c3.tipo_cambio_usd_clp > 0 THEN d2.cantidad * d2.precio_unitario * c3.tipo_cambio_usd_clp
                    ELSE 0
                END)
                FROM compras c3
                INNER JOIN compra_detalle d2 ON d2.id_compra = c3.id_compra
                INNER JOIN monedas m3 ON m3.id_moneda = c3.id_moneda
                WHERE c3.id_proveedor = p.id_proveedor
            ), 0) AS monto_total,
            (
                SELECT AVG(ep.calificacion)
                FROM evaluaciones_proveedores ep
                INNER JOIN compras c5 ON c5.id_compra = ep.id_compra
                WHERE c5.id_proveedor = p.id_proveedor
            ) AS calificacion
         FROM proveedores p
         WHERE EXISTS (
            SELECT 1
            FROM evaluaciones_proveedores ep2
            INNER JOIN compras c6 ON c6.id_compra = ep2.id_compra
            WHERE c6.id_proveedor = p.id_proveedor
         )
         ORDER BY calificacion ' . $ordenSeguro . ', p.nombre ASC
         LIMIT 3'
    );

    return array_map(static function ($proveedor) {
        return [
            'id_proveedor' => (int) $proveedor['id_proveedor'],
            'nombre' => $proveedor['nombre'],
            'compras' => (int) $proveedor['compras'],
            'monto_total' => (float) $proveedor['monto_total'],
            'moneda' => 'CLP',
            'calificacion' => (float) $proveedor['calificacion'],
        ];
    }, $consulta->fetchAll());
}
