<?php
/**
 * metodo_despacho_api.php
 *
 * Consulta transportistas y sus compras asociadas
 */

require_once __DIR__ . '/../../configuracion/imagenes.php';

/* Devuelve el resumen y una página de transportistas registrados */
function listarTransportistas(PDO $conexion, int $pagina = 1, int $porPagina = 5, string $busqueda = '')
{
    $pagina = max(1, $pagina);
    $porPagina = min(20, max(1, $porPagina));
    $busqueda = trim($busqueda);

    if (strlen($busqueda) > 100) {
        throw new InvalidArgumentException('El texto de búsqueda es demasiado largo.');
    }

    $condiciones = [];
    if ($busqueda !== '') {
        $condiciones[] = '(t.nombre LIKE :busqueda_nombre OR t.correo_electronico LIKE :busqueda_correo)';
    }
    $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
    $consultaTotal = $conexion->prepare('SELECT COUNT(*) FROM transportistas t' . $where);
    if ($busqueda !== '') {
        $consultaTotal->bindValue(':busqueda_nombre', '%' . $busqueda . '%', PDO::PARAM_STR);
        $consultaTotal->bindValue(':busqueda_correo', '%' . $busqueda . '%', PDO::PARAM_STR);
    }
    $consultaTotal->execute();
    $total = (int) $consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $resumen = $conexion->query(
        'SELECT
            COUNT(DISTINCT t.id_transportista) AS transportistas,
            COUNT(DISTINCT c.id_compra) AS compras,
            (SELECT AVG(calificacion) FROM evaluaciones_transportistas) AS calificacion_promedio
         FROM transportistas t
         LEFT JOIN compras c ON c.id_transportista = t.id_transportista'
    )->fetch() ?: [];

    $costos = [];
    foreach ($conexion->query(
        'SELECT m.codigo, COALESCE(SUM(c.costo_envio), 0) AS total
         FROM compras c
         INNER JOIN monedas m ON m.id_moneda = c.id_moneda
         WHERE c.id_transportista IS NOT NULL
         GROUP BY m.codigo ORDER BY m.codigo'
    )->fetchAll() as $costo) {
        $costos[] = ['moneda' => $costo['codigo'], 'total' => (float) $costo['total']];
    }

    $consulta = $conexion->prepare(
        'SELECT
            t.id_transportista,
            t.nombre,
            t.imagen,
            t.tipo_imagen,
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE(SUM(c.costo_envio), 0) AS costo_envios,
            (
                SELECT m3.codigo
                FROM compras c4
                INNER JOIN monedas m3 ON m3.id_moneda = c4.id_moneda
                WHERE c4.id_transportista = t.id_transportista
                ORDER BY c4.fecha_compra DESC, c4.id_compra DESC
                LIMIT 1
            ) AS moneda,
            MAX(c.fecha_compra) AS ultima_compra,
            (
                SELECT e2.nombre
                FROM estados_compras e2
                INNER JOIN compras c2 ON c2.id_estado_compra = e2.id_estado_compra
                WHERE c2.id_transportista = t.id_transportista
                ORDER BY c2.fecha_compra DESC, c2.id_compra DESC
                LIMIT 1
            ) AS estado_reciente,
            (
                SELECT AVG(et2.calificacion)
                FROM evaluaciones_transportistas et2
                INNER JOIN compras c2 ON c2.id_compra = et2.id_compra
                WHERE c2.id_transportista = t.id_transportista
            ) AS calificacion
         FROM transportistas t
         LEFT JOIN compras c ON c.id_transportista = t.id_transportista
         ' . $where . '
         GROUP BY t.id_transportista, t.nombre, t.imagen, t.tipo_imagen
         ORDER BY t.nombre ASC
         LIMIT :limite OFFSET :offset'
    );
    if ($busqueda !== '') {
        $consulta->bindValue(':busqueda_nombre', '%' . $busqueda . '%', PDO::PARAM_STR);
        $consulta->bindValue(':busqueda_correo', '%' . $busqueda . '%', PDO::PARAM_STR);
    }
    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consulta->execute();

    $transportistas = [];
    foreach ($consulta->fetchAll() as $transportista) {
        $transportistas[] = [
            'id_transportista' => (int) $transportista['id_transportista'],
            'nombre' => $transportista['nombre'],
            'imagen' => convertirImagenBlobADataUri($transportista['imagen'], $transportista['tipo_imagen']),
            'compras' => (int) $transportista['compras'],
            'costo_envios' => (float) $transportista['costo_envios'],
            'moneda' => $transportista['moneda'] ?: '',
            'ultima_compra' => $transportista['ultima_compra'],
            'estado_reciente' => $transportista['estado_reciente'] ?: 'Sin compras asociadas',
            'calificacion' => $transportista['calificacion'] !== null ? (float) $transportista['calificacion'] : null,
        ];
    }

    return [
        'resumen' => [
            'transportistas' => (int) ($resumen['transportistas'] ?? 0),
            'compras' => (int) ($resumen['compras'] ?? 0),
            'costos' => $costos,
            'calificacion_promedio' => $resumen['calificacion_promedio'] !== null ? (float) $resumen['calificacion_promedio'] : null,
        ],
        'transportistas' => $transportistas,
        'rankings' => [
            'mejores' => obtenerRankingTransportistas($conexion, 'DESC'),
            'peores' => obtenerRankingTransportistas($conexion, 'ASC'),
        ],
        'paginacion' => ['pagina_actual' => $pagina, 'por_pagina' => $porPagina, 'total' => $total, 'total_paginas' => $totalPaginas],
    ];
}

/* Devuelve el detalle completo de un transportista para el modal */
function obtenerTransportista(PDO $conexion, int $idTransportista)
{
    if ($idTransportista <= 0) {
        throw new InvalidArgumentException('El transportista solicitado no es válido.');
    }

    $consulta = $conexion->prepare(
        'SELECT
            t.id_transportista,
            t.nombre,
            t.descripcion,
            t.correo_electronico,
            t.telefono,
            t.imagen,
            t.tipo_imagen,
            COUNT(DISTINCT c.id_compra) AS compras,
            COALESCE((
                SELECT SUM(c2.costo_envio)
                FROM compras c2
                WHERE c2.id_transportista = t.id_transportista
            ), 0) AS costo_envios,
            (
                SELECT m.codigo
                FROM compras c3
                INNER JOIN monedas m ON m.id_moneda = c3.id_moneda
                WHERE c3.id_transportista = t.id_transportista
                ORDER BY c3.fecha_compra DESC, c3.id_compra DESC
                LIMIT 1
            ) AS moneda,
            (
                SELECT AVG(et.calificacion)
                FROM evaluaciones_transportistas et
                INNER JOIN compras c4 ON c4.id_compra = et.id_compra
                WHERE c4.id_transportista = t.id_transportista
            ) AS calificacion,
            (
                SELECT et2.comentario
                FROM evaluaciones_transportistas et2
                INNER JOIN compras c5 ON c5.id_compra = et2.id_compra
                WHERE c5.id_transportista = t.id_transportista
                ORDER BY et2.fecha_evaluacion DESC, et2.id_evaluacion DESC
                LIMIT 1
            ) AS ultimo_comentario
         FROM transportistas t
         LEFT JOIN compras c ON c.id_transportista = t.id_transportista
         WHERE t.id_transportista = :id_transportista
         GROUP BY t.id_transportista, t.nombre, t.descripcion, t.correo_electronico, t.telefono, t.imagen, t.tipo_imagen'
    );
    $consulta->bindValue(':id_transportista', $idTransportista, PDO::PARAM_INT);
    $consulta->execute();
    $transportista = $consulta->fetch();

    if (!$transportista) {
        throw new InvalidArgumentException('El transportista solicitado no existe.');
    }

    $comentarios = obtenerUltimosComentariosTransportista($conexion, $idTransportista);

    return [
        'id_transportista' => (int) $transportista['id_transportista'],
        'nombre' => $transportista['nombre'],
        'descripcion' => $transportista['descripcion'] ?: 'Sin descripción registrada',
        'correo_electronico' => $transportista['correo_electronico'] ?: 'Sin correo registrado',
        'telefono' => $transportista['telefono'] ?: 'Sin teléfono registrado',
        'imagen' => convertirImagenBlobADataUri($transportista['imagen'], $transportista['tipo_imagen']),
        'compras' => (int) $transportista['compras'],
        'costo_envios' => (float) $transportista['costo_envios'],
        'moneda' => $transportista['moneda'] ?: '',
        'calificacion' => $transportista['calificacion'] !== null ? (float) $transportista['calificacion'] : null,
        'ultimo_comentario' => $comentarios[0]['comentario'] ?? 'Sin comentarios registrados.',
        'comentarios' => $comentarios,
    ];
}

/* Obtiene los últimos cinco comentarios asociados a un transportista */
function obtenerUltimosComentariosTransportista(PDO $conexion, int $idTransportista): array
{
    $consulta = $conexion->prepare(
        'SELECT
            et.calificacion,
            et.comentario,
            et.fecha_evaluacion,
            c.codigo_compra,
            CONCAT(u.nombre, \' \', u.apellido) AS usuario
         FROM evaluaciones_transportistas et
         INNER JOIN compras c ON c.id_compra = et.id_compra
         INNER JOIN usuarios u ON u.id_usuario = et.id_usuario
         WHERE c.id_transportista = :id_transportista
           AND et.comentario IS NOT NULL
           AND TRIM(et.comentario) <> \'\'
         ORDER BY et.fecha_evaluacion DESC, et.id_evaluacion DESC
         LIMIT 5'
    );
    $consulta->bindValue(':id_transportista', $idTransportista, PDO::PARAM_INT);
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

/* Actualiza los datos editables y la imagen BLOB de un transportista */
function actualizarTransportista(PDO $conexion): array
{
    $idTransportista = (int) ($_POST['id_transportista'] ?? 0);
    if ($idTransportista <= 0) {
        throw new InvalidArgumentException('El transportista solicitado no es válido.');
    }

    $campos = [];
    $parametros = [':id_transportista' => $idTransportista];
    $camposEditables = [
        'correo_electronico' => ['maximo' => 150, 'nulo' => true],
        'telefono' => ['maximo' => 30, 'nulo' => true],
        'descripcion' => ['maximo' => 255, 'nulo' => true],
    ];

    foreach ($camposEditables as $campo => $reglas) {
        if (!array_key_exists($campo, $_POST)) {
            continue;
        }

        $valor = trim((string) $_POST[$campo]);
        if (strlen($valor) > $reglas['maximo']) {
            throw new InvalidArgumentException('El campo ' . $campo . ' supera el largo permitido.');
        }
        if ($campo === 'correo_electronico' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        $campos[] = $campo . ' = :' . $campo;
        $parametros[':' . $campo] = $valor === '' && $reglas['nulo'] ? null : $valor;
    }

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No fue posible cargar la imagen seleccionada.');
        }
        if ((int) $_FILES['imagen']['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('La imagen no puede superar los 5 MB.');
        }

        $tiposPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $tipoImagen = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagen']['tmp_name']);
        if (!isset($tiposPermitidos[$tipoImagen])) {
            throw new InvalidArgumentException('El archivo debe ser una imagen JPG, PNG, WEBP o GIF.');
        }

        $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
        if ($imagen === false) {
            throw new InvalidArgumentException('No fue posible leer la imagen seleccionada.');
        }

        $campos[] = 'imagen = :imagen';
        $campos[] = 'nombre_imagen = :nombre_imagen';
        $campos[] = 'tipo_imagen = :tipo_imagen';
        $parametros[':imagen'] = $imagen;
        $parametros[':nombre_imagen'] = basename((string) $_FILES['imagen']['name']);
        $parametros[':tipo_imagen'] = $tipoImagen;
    }

    if (!$campos) {
        throw new InvalidArgumentException('No hay cambios para guardar.');
    }

    $consulta = $conexion->prepare(
        'UPDATE transportistas SET ' . implode(', ', $campos) . ' WHERE id_transportista = :id_transportista'
    );
    foreach ($parametros as $parametro => $valor) {
        $consulta->bindValue($parametro, $valor, $parametro === ':id_transportista' ? PDO::PARAM_INT : ($valor === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
    }
    $consulta->execute();

    return ['mensaje' => 'El transportista fue actualizado correctamente.'];
}

/* Obtiene los transportistas con evaluaciones ordenados por calificación */
function obtenerRankingTransportistas(PDO $conexion, string $orden)
{
    $ordenSeguro = $orden === 'ASC' ? 'ASC' : 'DESC';
    $consulta = $conexion->query(
        'SELECT
            t.id_transportista,
            t.nombre,
            (
                SELECT COUNT(DISTINCT c2.id_compra)
                FROM compras c2
                WHERE c2.id_transportista = t.id_transportista
            ) AS compras,
            COALESCE((
                SELECT SUM(c3.costo_envio)
                FROM compras c3
                WHERE c3.id_transportista = t.id_transportista
            ), 0) AS costo_envios,
            (
                SELECT m2.codigo
                FROM compras c4
                INNER JOIN monedas m2 ON m2.id_moneda = c4.id_moneda
                WHERE c4.id_transportista = t.id_transportista
                ORDER BY c4.fecha_compra DESC, c4.id_compra DESC
                LIMIT 1
            ) AS moneda,
            (
                SELECT AVG(et.calificacion)
                FROM evaluaciones_transportistas et
                INNER JOIN compras c5 ON c5.id_compra = et.id_compra
                WHERE c5.id_transportista = t.id_transportista
            ) AS calificacion
         FROM transportistas t
         WHERE EXISTS (
            SELECT 1
            FROM evaluaciones_transportistas et2
            INNER JOIN compras c6 ON c6.id_compra = et2.id_compra
            WHERE c6.id_transportista = t.id_transportista
         )
         ORDER BY calificacion ' . $ordenSeguro . ', t.nombre ASC
         LIMIT 3'
    );

    return array_map(static function ($transportista) {
        return [
            'id_transportista' => (int) $transportista['id_transportista'],
            'nombre' => $transportista['nombre'],
            'compras' => (int) $transportista['compras'],
            'costo_envios' => (float) $transportista['costo_envios'],
            'moneda' => $transportista['moneda'] ?: '',
            'calificacion' => (float) $transportista['calificacion'],
        ];
    }, $consulta->fetchAll());
}
