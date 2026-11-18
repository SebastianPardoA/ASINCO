<?php
/**
 * registro_compra_api.php
 *
 * Proporciona catálogos, cotización y registro transaccional de compras
 */

/* Obtiene responsables activos, monedas disponibles y el código correlativo de compra */
function obtenerDatosConfirmacionCompra(PDO $conexion): array
{
    $consultaResponsables = $conexion->query(
        "SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            COALESCE(r.nombre, 'Sin rol') AS rol
         FROM usuarios u
         LEFT JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.estado = 'activo'
         ORDER BY u.nombre ASC, u.apellido ASC, u.id_usuario ASC"
    );

    $responsables = array_map(static function (array $usuario): array {
        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'rol' => $usuario['rol'],
        ];
    }, $consultaResponsables->fetchAll());

    $consultaMonedas = $conexion->query(
        "SELECT id_moneda, codigo, nombre
         FROM monedas
         WHERE codigo IN ('CLP', 'USD')
         ORDER BY CASE codigo WHEN 'CLP' THEN 1 WHEN 'USD' THEN 2 ELSE 3 END"
    );

    $monedas = array_map(static function (array $moneda): array {
        return [
            'id_moneda' => (int) $moneda['id_moneda'],
            'codigo' => $moneda['codigo'],
            'nombre' => $moneda['nombre'],
        ];
    }, $consultaMonedas->fetchAll());

    $consultaCodigo = $conexion->query(
        "SELECT
            YEAR(CURDATE()) AS anio,
            COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo_compra, '-', -1) AS UNSIGNED)), 0) AS ultimo_correlativo
         FROM compras
         WHERE codigo_compra REGEXP CONCAT('^OC-', YEAR(CURDATE()), '-[0-9]+$')"
    );
    $secuencia = $consultaCodigo->fetch() ?: [];
    $anio = (int) ($secuencia['anio'] ?? date('Y'));
    $correlativo = (int) ($secuencia['ultimo_correlativo'] ?? 0) + 1;

    return [
        'responsables' => $responsables,
        'monedas' => $monedas,
        'codigo_compra' => sprintf('OC-%04d-%04d', $anio, $correlativo),
    ];
}

/* Registra la compra y sus productos en una transacción, sin ingresar unidades al inventario */
function registrarCompra(PDO $conexion): array
{
    $json = trim((string) ($_POST['datos'] ?? ''));
    if ($json === '' || strlen($json) > 8 * 1024 * 1024) {
        throw new InvalidArgumentException('Los datos de la compra están vacíos o exceden el tamaño permitido.');
    }

    try {
        $datos = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new InvalidArgumentException('Los datos enviados para la compra no tienen un formato válido.');
    }
    if (!is_array($datos)) {
        throw new InvalidArgumentException('Los datos de la compra no son válidos.');
    }

    $idProveedor = (int) ($datos['id_proveedor'] ?? 0);
    $idTransportista = (int) ($datos['id_transportista'] ?? 0);
    $idResponsable = (int) ($datos['id_responsable_compra'] ?? 0);
    $idMoneda = (int) ($datos['id_moneda'] ?? 0);
    $fechaCompra = trim((string) ($datos['fecha_compra'] ?? ''));
    $fechaEstimada = trim((string) ($datos['fecha_estimada_llegada'] ?? ''));
    $numeroPedido = trim((string) ($datos['numero_pedido'] ?? ''));
    $observaciones = trim((string) ($datos['observaciones'] ?? ''));
    $codigoMonedaEnviado = strtoupper(trim((string) ($datos['codigo_moneda'] ?? '')));
    $costoEnvio = $datos['costo_envio'] ?? null;
    $tipoCambio = $datos['tipo_cambio_usd_clp'] ?? null;
    $fechaTipoCambio = trim((string) ($datos['fecha_tipo_cambio'] ?? ''));
    $origenTipoCambio = trim((string) ($datos['origen_tipo_cambio'] ?? ''));
    $productosRecibidos = $datos['productos'] ?? null;

    /* Comprueba las fechas ISO recibidas antes de enviar valores a MySQL */
    $validarFecha = static function (string $fecha, bool $permitirVacio = false): bool {
        if ($fecha === '' && $permitirVacio) {
            return true;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $partes)) {
            return false;
        }
        return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
    };

    if ($idProveedor <= 0 || $idTransportista <= 0 || $idResponsable <= 0 || $idMoneda <= 0) {
        throw new InvalidArgumentException('Selecciona un proveedor, transportista, responsable y moneda válidos.');
    }
    if (!$validarFecha($fechaCompra) || !$validarFecha($fechaEstimada, true)) {
        throw new InvalidArgumentException('Revisa las fechas de compra y de llegada estimada.');
    }
    if ($fechaEstimada !== '' && $fechaEstimada < $fechaCompra) {
        throw new InvalidArgumentException('La fecha estimada de llegada no puede ser anterior a la fecha de compra.');
    }
    if (!is_numeric($costoEnvio) || !is_finite((float) $costoEnvio) || (float) $costoEnvio < 0 || (float) $costoEnvio > 9999999999999.99) {
        throw new InvalidArgumentException('El costo de envío debe ser un monto válido igual o mayor que cero.');
    }
    if (strlen($numeroPedido) > 80 || strlen($observaciones) > 5000) {
        throw new InvalidArgumentException('La referencia o las observaciones exceden el largo permitido.');
    }
    if (!is_array($productosRecibidos) || count($productosRecibidos) < 1 || count($productosRecibidos) > 200) {
        throw new InvalidArgumentException('La compra debe incluir entre 1 y 200 productos.');
    }

    $productos = [];
    $codigosProducto = [];
    $idsProductoExistente = [];
    foreach ($productosRecibidos as $producto) {
        if (!is_array($producto)) {
            throw new InvalidArgumentException('Uno de los productos enviados no es válido.');
        }

        $idProducto = (int) ($producto['id_producto'] ?? 0);
        $nombre = trim((string) ($producto['nombre'] ?? ''));
        $codigo = trim((string) ($producto['codigo_producto'] ?? ''));
        $categoria = trim((string) ($producto['categoria'] ?? ''));
        $unidad = trim((string) ($producto['unidad_medida'] ?? ''));
        $descripcion = trim((string) ($producto['descripcion'] ?? ''));
        $cantidad = filter_var($producto['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $precio = $producto['precio_unitario'] ?? null;
        $imagenData = trim((string) ($producto['imagen'] ?? ''));
        $nombreImagen = trim((string) ($producto['nombre_archivo'] ?? ''));

        if ($nombre === '' || strlen($nombre) > 200 || $codigo === '' || strlen($codigo) > 80
            || $categoria === '' || strlen($categoria) > 100 || $unidad === '' || strlen($unidad) > 50
            || strlen($descripcion) > 20000) {
            throw new InvalidArgumentException('Cada producto debe tener nombre, código, categoría y unidad dentro de los largos permitidos.');
        }
        if ($cantidad === false || $cantidad < 1 || $cantidad > 4294967295) {
            throw new InvalidArgumentException('La cantidad de cada producto debe ser un entero mayor que cero.');
        }
        if (!is_numeric($precio) || !is_finite((float) $precio) || (float) $precio <= 0 || (float) $precio > 9999999999999.99) {
            throw new InvalidArgumentException('Ingresa un precio unitario mayor que cero para cada producto.');
        }

        $claveCodigo = strtolower($codigo);
        if (isset($codigosProducto[$claveCodigo])) {
            throw new InvalidArgumentException('Un mismo código de producto aparece más de una vez. Consolida su cantidad en una sola fila.');
        }
        $codigosProducto[$claveCodigo] = true;
        if ($idProducto > 0) {
            if (isset($idsProductoExistente[$idProducto])) {
                throw new InvalidArgumentException('Un producto aparece más de una vez. Consolida su cantidad en una sola fila.');
            }
            $idsProductoExistente[$idProducto] = true;
        }

        $imagen = null;
        $tipoImagen = null;
        if ($imagenData !== '') {
            if (strlen($imagenData) > 2 * 1024 * 1024
                || !preg_match('#^data:(image/(?:jpeg|png|webp));base64,([A-Za-z0-9+/=\r\n]+)$#', $imagenData, $partesImagen)) {
                throw new InvalidArgumentException('La imagen de ' . $nombre . ' no tiene un formato permitido.');
            }
            $imagen = base64_decode($partesImagen[2], true);
            $tipoImagen = $partesImagen[1];
            $informacionImagen = $imagen !== false ? @getimagesizefromstring($imagen) : false;
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            if ($imagen === false || strlen($imagen) > 1024 * 1024 || !$informacionImagen
                || !in_array((string) ($informacionImagen['mime'] ?? ''), $tiposPermitidos, true)
                || $informacionImagen['mime'] !== $tipoImagen) {
                throw new InvalidArgumentException('La imagen de ' . $nombre . ' debe ser JPG, PNG o WEBP y no superar 1 MB.');
            }
            if (strlen($nombreImagen) > 255) {
                $nombreImagen = substr($nombreImagen, 0, 255);
            }
        }

        $productos[] = [
            'id_producto' => $idProducto,
            'nombre' => $nombre,
            'codigo_producto' => $codigo,
            'categoria' => $categoria,
            'unidad_medida' => $unidad,
            'descripcion' => $descripcion,
            'cantidad' => (int) $cantidad,
            'precio_unitario' => (float) $precio,
            'imagen' => $imagen,
            'tipo_imagen' => $tipoImagen,
            'nombre_archivo' => $nombreImagen !== '' ? $nombreImagen : null,
        ];
    }

    $conexion->beginTransaction();
    $nombreBloqueo = 'asinco_compra_codigo_' . date('Y');
    $bloqueoAdquirido = false;
    try {
        $bloqueo = $conexion->prepare('SELECT GET_LOCK(:nombre_bloqueo, 10)');
        $bloqueo->execute([':nombre_bloqueo' => $nombreBloqueo]);
        $bloqueoAdquirido = (int) $bloqueo->fetchColumn() === 1;
        if (!$bloqueoAdquirido) {
            throw new RuntimeException('No se pudo reservar el código correlativo de compra. Inténtalo nuevamente.');
        }

        $consultaProveedor = $conexion->prepare('SELECT id_proveedor FROM proveedores WHERE id_proveedor = :id LIMIT 1');
        $consultaProveedor->execute([':id' => $idProveedor]);
        if (!$consultaProveedor->fetchColumn()) {
            throw new InvalidArgumentException('El proveedor seleccionado ya no está disponible.');
        }
        $consultaTransportista = $conexion->prepare('SELECT id_transportista FROM transportistas WHERE id_transportista = :id LIMIT 1');
        $consultaTransportista->execute([':id' => $idTransportista]);
        if (!$consultaTransportista->fetchColumn()) {
            throw new InvalidArgumentException('El transportista seleccionado ya no está disponible.');
        }
        $consultaResponsable = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id AND estado = 'activo' LIMIT 1");
        $consultaResponsable->execute([':id' => $idResponsable]);
        if (!$consultaResponsable->fetchColumn()) {
            throw new InvalidArgumentException('El responsable seleccionado no existe o está inactivo.');
        }
        $consultaMoneda = $conexion->prepare('SELECT codigo FROM monedas WHERE id_moneda = :id LIMIT 1');
        $consultaMoneda->execute([':id' => $idMoneda]);
        $codigoMoneda = strtoupper((string) $consultaMoneda->fetchColumn());
        if (!in_array($codigoMoneda, ['CLP', 'USD'], true) || $codigoMoneda !== $codigoMonedaEnviado) {
            throw new InvalidArgumentException('La moneda seleccionada no es válida.');
        }

        $consultaEstado = $conexion->prepare("SELECT id_estado_compra FROM estados_compras WHERE nombre = 'registrada' AND activo = 1 LIMIT 1");
        $consultaEstado->execute();
        $idEstado = (int) $consultaEstado->fetchColumn();
        if ($idEstado <= 0) {
            throw new RuntimeException('Falta el estado inicial "registrada" en el catálogo de estados de compra.');
        }

        $tasaInformada = $tipoCambio !== null && $tipoCambio !== '';
        $tasaValida = $codigoMoneda === 'USD' && $tasaInformada
            && is_numeric($tipoCambio) && is_finite((float) $tipoCambio) && (float) $tipoCambio > 0;
        if ($codigoMoneda === 'USD' && $tasaInformada && !$tasaValida) {
            throw new InvalidArgumentException('La cotización USD/CLP debe ser un valor mayor que cero.');
        }
        if ($tasaValida && (!$validarFecha($fechaTipoCambio) || strlen($origenTipoCambio) > 50)) {
            throw new InvalidArgumentException('La fecha o el origen de la cotización USD/CLP no son válidos.');
        }
        $tipoCambioGuardar = $tasaValida ? (float) $tipoCambio : null;
        $fechaTipoCambioGuardar = $tasaValida ? $fechaTipoCambio : null;
        $origenTipoCambioGuardar = $tasaValida ? ($origenTipoCambio !== '' ? $origenTipoCambio : 'mindicador.cl') : null;

        $anioCodigo = (int) date('Y');
        $consultaSecuencia = $conexion->prepare(
            'SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo_compra, \'-\', -1) AS UNSIGNED)), 0)
             FROM compras
             WHERE codigo_compra REGEXP :patron'
        );
        $consultaSecuencia->execute([':patron' => '^OC-' . $anioCodigo . '-[0-9]+$']);
        $correlativo = (int) $consultaSecuencia->fetchColumn() + 1;
        $codigoCompra = sprintf('OC-%04d-%04d', $anioCodigo, $correlativo);

        $insertarCompra = $conexion->prepare(
            'INSERT INTO compras (
                codigo_compra, id_proveedor, id_usuario, id_moneda, id_estado_compra,
                id_transportista, fecha_compra, fecha_estimada_llegada, numero_pedido,
                costo_envio, tipo_cambio_usd_clp, fecha_tipo_cambio, origen_tipo_cambio, observaciones
             ) VALUES (
                :codigo, :proveedor, :usuario, :moneda, :estado,
                :transportista, :fecha_compra, :fecha_estimada, :numero_pedido,
                :costo_envio, :tipo_cambio, :fecha_tipo_cambio, :origen_tipo_cambio, :observaciones
             )'
        );
        $insertarCompra->execute([
            ':codigo' => $codigoCompra,
            ':proveedor' => $idProveedor,
            ':usuario' => $idResponsable,
            ':moneda' => $idMoneda,
            ':estado' => $idEstado,
            ':transportista' => $idTransportista,
            ':fecha_compra' => $fechaCompra,
            ':fecha_estimada' => $fechaEstimada !== '' ? $fechaEstimada : null,
            ':numero_pedido' => $numeroPedido !== '' ? $numeroPedido : null,
            ':costo_envio' => number_format((float) $costoEnvio, 2, '.', ''),
            ':tipo_cambio' => $tipoCambioGuardar !== null ? number_format($tipoCambioGuardar, 6, '.', '') : null,
            ':fecha_tipo_cambio' => $fechaTipoCambioGuardar,
            ':origen_tipo_cambio' => $origenTipoCambioGuardar,
            ':observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);
        $idCompra = (int) $conexion->lastInsertId();

        $buscarCategoria = $conexion->prepare('SELECT id_categoria FROM categorias_productos WHERE nombre = :nombre LIMIT 1');
        $crearCategoria = $conexion->prepare('INSERT IGNORE INTO categorias_productos (nombre) VALUES (:nombre)');
        $buscarProductoCodigo = $conexion->prepare('SELECT id_producto FROM productos WHERE codigo_producto = :codigo LIMIT 1');
        $consultarProducto = $conexion->prepare('SELECT id_producto FROM productos WHERE id_producto = :id FOR UPDATE');
        $actualizarProducto = $conexion->prepare(
            'UPDATE productos
             SET id_categoria = :categoria, codigo_producto = :codigo, nombre = :nombre,
                 descripcion = :descripcion, unidad_medida = :unidad, activo = 1
             WHERE id_producto = :id'
        );
        $crearProducto = $conexion->prepare(
            'INSERT INTO productos (id_categoria, codigo_producto, nombre, descripcion, unidad_medida)
             VALUES (:categoria, :codigo, :nombre, :descripcion, :unidad)'
        );
        $insertarDetalle = $conexion->prepare(
            'INSERT INTO compra_detalle (id_compra, id_producto, cantidad, precio_unitario)
             VALUES (:compra, :producto, :cantidad, :precio)'
        );
        $buscarImagen = $conexion->prepare(
            'SELECT id_imagen FROM producto_imagenes
             WHERE id_producto = :producto ORDER BY orden ASC, id_imagen ASC LIMIT 1'
        );
        $actualizarImagen = $conexion->prepare(
            'UPDATE producto_imagenes SET imagen = :imagen, nombre_archivo = :nombre WHERE id_imagen = :id'
        );
        $crearImagen = $conexion->prepare(
            'INSERT INTO producto_imagenes (id_producto, imagen, nombre_archivo, orden)
             VALUES (:producto, :imagen, :nombre, 1)'
        );

        foreach ($productos as $producto) {
            $buscarCategoria->execute([':nombre' => $producto['categoria']]);
            $idCategoria = (int) $buscarCategoria->fetchColumn();
            if ($idCategoria <= 0) {
                $crearCategoria->execute([':nombre' => $producto['categoria']]);
                $buscarCategoria->execute([':nombre' => $producto['categoria']]);
                $idCategoria = (int) $buscarCategoria->fetchColumn();
            }
            if ($idCategoria <= 0) {
                throw new RuntimeException('No se pudo guardar la categoría de ' . $producto['nombre'] . '.');
            }

            $idProducto = $producto['id_producto'];
            if ($idProducto > 0) {
                $consultarProducto->execute([':id' => $idProducto]);
                if (!$consultarProducto->fetchColumn()) {
                    throw new InvalidArgumentException('Un producto seleccionado ya no existe en el catálogo.');
                }
                $buscarProductoCodigo->execute([':codigo' => $producto['codigo_producto']]);
                $idConCodigo = (int) $buscarProductoCodigo->fetchColumn();
                if ($idConCodigo > 0 && $idConCodigo !== $idProducto) {
                    throw new InvalidArgumentException('El código ' . $producto['codigo_producto'] . ' ya pertenece a otro producto.');
                }
                $actualizarProducto->execute([
                    ':categoria' => $idCategoria,
                    ':codigo' => $producto['codigo_producto'],
                    ':nombre' => $producto['nombre'],
                    ':descripcion' => $producto['descripcion'] !== '' ? $producto['descripcion'] : null,
                    ':unidad' => $producto['unidad_medida'],
                    ':id' => $idProducto,
                ]);
            } else {
                $buscarProductoCodigo->execute([':codigo' => $producto['codigo_producto']]);
                if ($buscarProductoCodigo->fetchColumn()) {
                    throw new InvalidArgumentException('El código ' . $producto['codigo_producto'] . ' ya existe. Busca y selecciona ese producto del catálogo.');
                }
                $crearProducto->execute([
                    ':categoria' => $idCategoria,
                    ':codigo' => $producto['codigo_producto'],
                    ':nombre' => $producto['nombre'],
                    ':descripcion' => $producto['descripcion'] !== '' ? $producto['descripcion'] : null,
                    ':unidad' => $producto['unidad_medida'],
                ]);
                $idProducto = (int) $conexion->lastInsertId();
            }

            if ($producto['imagen'] !== null) {
                $buscarImagen->execute([':producto' => $idProducto]);
                $idImagen = (int) $buscarImagen->fetchColumn();
                if ($idImagen > 0) {
                    $actualizarImagen->bindValue(':imagen', $producto['imagen'], PDO::PARAM_LOB);
                    $actualizarImagen->bindValue(':nombre', $producto['nombre_archivo'], $producto['nombre_archivo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $actualizarImagen->bindValue(':id', $idImagen, PDO::PARAM_INT);
                    $actualizarImagen->execute();
                } else {
                    $crearImagen->bindValue(':producto', $idProducto, PDO::PARAM_INT);
                    $crearImagen->bindValue(':imagen', $producto['imagen'], PDO::PARAM_LOB);
                    $crearImagen->bindValue(':nombre', $producto['nombre_archivo'], $producto['nombre_archivo'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $crearImagen->execute();
                }
            }

            $insertarDetalle->execute([
                ':compra' => $idCompra,
                ':producto' => $idProducto,
                ':cantidad' => $producto['cantidad'],
                ':precio' => number_format($producto['precio_unitario'], 2, '.', ''),
            ]);
        }

        $conexion->commit();
        return [
            'id_compra' => $idCompra,
            'codigo_compra' => $codigoCompra,
            'estado' => 'registrada',
            'productos' => count($productos),
        ];
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        throw $error;
    } finally {
        if ($bloqueoAdquirido) {
            try {
                $liberarBloqueo = $conexion->prepare('SELECT RELEASE_LOCK(:nombre_bloqueo)');
                $liberarBloqueo->execute([':nombre_bloqueo' => $nombreBloqueo]);
            } catch (Throwable $errorLiberacion) {
                // La liberación no debe ocultar un guardado que ya se confirmó
            }
        }
    }
}

/* Descarga y valida una respuesta JSON desde un servicio de indicadores */
function solicitarJsonTipoCambio(string $url): array
{
    $respuesta = false;
    if (function_exists('curl_init')) {
        $cliente = curl_init($url);
        curl_setopt_array($cliente, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'ASINCO/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $respuesta = curl_exec($cliente);
        $codigoHttp = (int) curl_getinfo($cliente, CURLINFO_HTTP_CODE);
        curl_close($cliente);
        if ($respuesta === false || $codigoHttp !== 200) {
            throw new RuntimeException('El servicio de tipo de cambio no respondió.');
        }
    } else {
        $contexto = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "Accept: application/json\r\nUser-Agent: ASINCO/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $respuesta = @file_get_contents($url, false, $contexto);
        if ($respuesta === false) {
            throw new RuntimeException('El servicio de tipo de cambio no respondió.');
        }
    }

    $datos = json_decode((string) $respuesta, true);
    if (!is_array($datos)) {
        throw new RuntimeException('El servicio de tipo de cambio devolvió datos no válidos.');
    }

    return $datos;
}

/* Consulta el dólar observado usando Mindicador y un origen alternativo compatible */
function obtenerTipoCambioDolarMindicador(): array
{
    $fuentes = [
        [
            'origen' => 'mindicador.cl',
            'url' => 'https://mindicador.cl/api/dolar',
            'formato' => 'serie',
        ],
        [
            'origen' => 'indicadores.cl',
            'url' => 'https://indicadores.cl/api/v1/dolar.json',
            'formato' => 'actual',
        ],
    ];

    foreach ($fuentes as $fuente) {
        try {
            $datos = solicitarJsonTipoCambio($fuente['url']);
            if ($fuente['formato'] === 'actual') {
                $observaciones = [[
                    'valor' => $datos['valor'] ?? null,
                    'fecha' => $datos['fecha'] ?? null,
                ]];
            } else {
                $observaciones = is_array($datos['serie'] ?? null) ? $datos['serie'] : [];
            }

            foreach ($observaciones as $observacion) {
                $valor = (float) ($observacion['valor'] ?? 0);
                $fechaOriginal = (string) ($observacion['fecha'] ?? '');
                $fecha = strtotime($fechaOriginal);
                if ($valor > 0 && $fecha !== false) {
                    return [
                        'valor' => $valor,
                        'fecha' => date('Y-m-d', $fecha),
                        'origen' => $fuente['origen'],
                    ];
                }
            }
        } catch (Throwable $error) {
            // Prueba el siguiente origen si el actual no está disponible.
        }
    }

    throw new RuntimeException('No fue posible consultar las fuentes disponibles del dólar observado.');
}
