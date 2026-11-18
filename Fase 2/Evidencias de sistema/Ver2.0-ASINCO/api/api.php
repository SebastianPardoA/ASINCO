<?php
/**
 * api.php
 *
 * Punto de entrada general de las APIs del sistema ASINCO
 * Dirige las solicitudes de administración, compras, inventario y dashboard en formato JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../configuracion/base_datos.php';
require_once __DIR__ . '/../configuracion/sesion.php';
require_once __DIR__ . '/../configuracion/permisos.php';
require_once __DIR__ . '/modulos/administracion_api.php';
require_once __DIR__ . '/modulos/proveedores_api.php';
require_once __DIR__ . '/modulos/metodo_despacho_api.php';
require_once __DIR__ . '/modulos/historial_compras_api.php';
require_once __DIR__ . '/modulos/inventario_api.php';
require_once __DIR__ . '/modulos/registro_compra_api.php';
require_once __DIR__ . '/modulos/dashboard_api.php';

/* Devuelve una respuesta JSON y finaliza la solicitud */
function responderJson(array $respuesta, int $codigoHttp = 200)
{
    http_response_code($codigoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

/* Evita exponer datos o permitir operaciones de API sin una sesión activa */
if (!haySesionActiva()) {
    responderJson([
        'exito' => false,
        'mensaje' => 'La sesión no está activa.',
    ], 401);
}

$modulo = trim((string) ($_GET['modulo'] ?? ''));
$accion = trim((string) ($_GET['accion'] ?? ''));

try {
    $accionesAdministracion = ['crear_usuario', 'listar_usuarios', 'obtener_usuario', 'actualizar_usuario', 'eliminar_usuario'];
    $accionesCatalogos = ['listar_proveedores', 'obtener_proveedor', 'actualizar_proveedor', 'crear_proveedor_compra', 'listar_sugerencias_compra', 'buscar_catalogo_compra', 'listar_transportistas', 'obtener_transportista', 'actualizar_transportista', 'crear_transportista_compra', 'listar_historial_compras', 'obtener_detalle_compra', 'actualizar_estado_compra', 'guardar_evaluacion_compra', 'guardar_comentario_compra', 'subir_documento_compra', 'eliminar_documento_compra', 'listar_inventario', 'buscar_coincidencias_productos', 'obtener_detalle_producto', 'listar_usuarios_asignacion', 'asignar_unidad_usuario', 'listar_estados_unidad', 'actualizar_estado_unidad', 'subir_imagen_producto', 'eliminar_imagen_producto', 'marcar_imagen_principal_producto'];
    $accionesRegistroCompra = ['obtener_datos_confirmacion_compra', 'obtener_tipo_cambio_dolar', 'registrar_compra'];
    $accionesDashboard = ['obtener_datos_dashboard'];

    if (($modulo !== 'administracion' && $modulo !== 'proveedores' && $modulo !== 'transportistas' && $modulo !== 'historial_compras' && $modulo !== 'inventario' && $modulo !== 'registro_compra' && $modulo !== 'dashboard')
        || !in_array($accion, array_merge($accionesAdministracion, $accionesCatalogos, $accionesRegistroCompra, $accionesDashboard), true)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Solicitud de API no válida.',
        ], 400);
    }

    $conexion = obtenerConexionBaseDatos();
    $rolSesion = sincronizarRolUsuarioSesion($conexion);
    if (!usuarioTieneRolAsignado($rolSesion)) {
        destruirSesionSistema();
        responderJson([
            'exito' => false,
            'codigo' => 'rol_no_asignado',
            'mensaje' => 'Tu cuenta no tiene un rol asignado. Contacta al administrador para que te asigne uno.',
        ], 401);
    }
    if (!usuarioPuedeUsarModuloApi($modulo, $rolSesion)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Tu rol no tiene permiso para acceder a este módulo.',
        ], 403);
    }

    if ($modulo === 'dashboard' && $accion === 'obtener_datos_dashboard' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $datosDashboard = obtenerDatosDashboard(
            $conexion,
            (int) ($_GET['meses'] ?? 6),
            trim((string) ($_GET['mes_estados'] ?? ''))
        );
        responderJson(['exito' => true, 'datos' => $datosDashboard]);
    }

    if ($modulo === 'registro_compra' && $accion === 'obtener_datos_confirmacion_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJson(['exito' => true, 'datos' => obtenerDatosConfirmacionCompra($conexion)]);
    }

    if ($modulo === 'registro_compra' && $accion === 'registrar_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = registrarCompra($conexion);
        responderJson([
            'exito' => true,
            'mensaje' => 'La compra y sus productos se registraron correctamente.',
            'datos' => $resultado,
        ], 201);
    }

    if ($modulo === 'registro_compra' && $accion === 'obtener_tipo_cambio_dolar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            responderJson(['exito' => true, 'datos' => obtenerTipoCambioDolarMindicador()]);
        } catch (RuntimeException $error) {
            responderJson([
                'exito' => false,
                'mensaje' => 'No se pudo obtener la cotización del dólar. Revisa tu conexión e inténtalo nuevamente.',
            ], 502);
        }
    }

    if ($modulo === 'proveedores' && $accion === 'listar_sugerencias_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJson(['exito' => true, 'datos' => listarProveedoresSugeridosCompra($conexion)]);
    }

    if ($modulo === 'proveedores' && $accion === 'buscar_catalogo_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = buscarProveedoresCatalogoCompra(
            $conexion,
            trim((string) ($_GET['busqueda'] ?? '')),
            (int) ($_GET['pagina'] ?? 1)
        );
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'proveedores' && $accion === 'crear_proveedor_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = crearProveedorRegistroCompra($conexion);
        responderJson([
            'exito' => true,
            'mensaje' => 'El proveedor fue registrado correctamente.',
            'datos' => $resultado,
        ], 201);
    }

    if ($modulo === 'proveedores' && $accion === 'listar_proveedores' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarProveedores(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            5,
            trim((string) ($_GET['busqueda'] ?? ''))
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'proveedores' && $accion === 'obtener_proveedor' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerProveedor($conexion, (int) ($_GET['id_proveedor'] ?? 0));
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'proveedores' && $accion === 'actualizar_proveedor' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarProveedor($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'transportistas' && $accion === 'listar_transportistas' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarTransportistas(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            5,
            trim((string) ($_GET['busqueda'] ?? ''))
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'transportistas' && $accion === 'listar_sugerencias_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJson(['exito' => true, 'datos' => listarTransportistasSugeridosCompra($conexion)]);
    }

    if ($modulo === 'transportistas' && $accion === 'buscar_catalogo_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = buscarTransportistasCatalogoCompra(
            $conexion,
            trim((string) ($_GET['busqueda'] ?? '')),
            (int) ($_GET['pagina'] ?? 1)
        );
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'transportistas' && $accion === 'crear_transportista_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = crearTransportistaRegistroCompra($conexion);
        responderJson([
            'exito' => true,
            'mensaje' => 'El transportista fue registrado correctamente.',
            'datos' => $resultado,
        ], 201);
    }

    if ($modulo === 'transportistas' && $accion === 'obtener_transportista' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerTransportista($conexion, (int) ($_GET['id_transportista'] ?? 0));
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'transportistas' && $accion === 'actualizar_transportista' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarTransportista($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'historial_compras' && $accion === 'listar_historial_compras' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarHistorialCompras(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            (int) ($_GET['por_pagina'] ?? 7),
            [
                'busqueda' => $_GET['busqueda'] ?? '',
                'fecha' => $_GET['fecha'] ?? '',
                'id_proveedor' => $_GET['id_proveedor'] ?? 0,
                'id_transportista' => $_GET['id_transportista'] ?? 0,
                'id_estado_compra' => $_GET['id_estado_compra'] ?? 0,
            ]
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'historial_compras' && $accion === 'obtener_detalle_compra' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerDetalleCompra($conexion, (int) ($_GET['id_compra'] ?? 0));
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'historial_compras' && $accion === 'actualizar_estado_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarEstadoCompra($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'historial_compras' && $accion === 'guardar_evaluacion_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = guardarEvaluacionCompra($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'historial_compras' && $accion === 'guardar_comentario_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = guardarComentarioCompra($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'historial_compras' && $accion === 'subir_documento_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = subirDocumentoCompra($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje'], 'datos' => $resultado]);
    }

    if ($modulo === 'historial_compras' && $accion === 'eliminar_documento_compra' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = eliminarDocumentoCompra($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'inventario' && $accion === 'listar_inventario' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarInventario(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            (int) ($_GET['por_pagina'] ?? 7),
            [
                'busqueda' => $_GET['busqueda'] ?? '',
                'id_categoria' => $_GET['id_categoria'] ?? 0,
                'id_proveedor' => $_GET['id_proveedor'] ?? 0,
                'id_estado_producto' => $_GET['id_estado_producto'] ?? 0,
                'stock' => $_GET['stock'] ?? '',
            ]
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'inventario' && $accion === 'buscar_coincidencias_productos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = buscarCoincidenciasProductos(
            $conexion,
            trim((string) ($_GET['nombre'] ?? '')),
            trim((string) ($_GET['categoria'] ?? ''))
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'inventario' && $accion === 'obtener_detalle_producto' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerDetalleProducto(
            $conexion,
            (int) ($_GET['id_producto'] ?? 0),
            (int) ($_GET['pagina'] ?? 1),
            (int) ($_GET['por_pagina'] ?? 9),
            [
                'busqueda' => $_GET['busqueda_unidades'] ?? '',
                'usuario_asignado' => $_GET['usuario_asignado'] ?? '',
                'fecha_compra' => $_GET['fecha_compra'] ?? '',
                'id_proveedor' => $_GET['id_proveedor_unidad'] ?? 0,
                'id_transportista' => $_GET['id_transportista_unidad'] ?? 0,
            ]
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'inventario' && $accion === 'listar_usuarios_asignacion' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJson(['exito' => true, 'datos' => listarUsuariosDisponiblesUnidad($conexion)]);
    }

    if ($modulo === 'inventario' && $accion === 'asignar_unidad_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = asignarUnidadUsuario($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'inventario' && $accion === 'listar_estados_unidad' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        responderJson(['exito' => true, 'datos' => listarEstadosDisponiblesUnidad($conexion)]);
    }

    if ($modulo === 'inventario' && $accion === 'actualizar_estado_unidad' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarEstadoUnidad($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'inventario' && $accion === 'subir_imagen_producto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = subirImagenProducto($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'inventario' && $accion === 'eliminar_imagen_producto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = eliminarImagenProducto($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'inventario' && $accion === 'marcar_imagen_principal_producto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = marcarImagenPrincipalProducto($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($accion === 'listar_usuarios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $pagina = (int) ($_GET['pagina'] ?? 1);
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $resultado = listarUsuarios($conexion, $pagina, 7, $estado, $busqueda);

        responderJson([
            'exito' => true,
            'datos' => $resultado,
        ]);
    }

    if ($accion === 'obtener_usuario' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $idUsuario = (int) ($_GET['id_usuario'] ?? 0);
        $usuario = obtenerUsuario($conexion, $idUsuario);

        responderJson([
            'exito' => true,
            'datos' => $usuario,
        ]);
    }

    if ($accion === 'actualizar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarUsuario($conexion);

        responderJson([
            'exito' => true,
            'mensaje' => $resultado['mensaje'],
            'datos' => [
                'sesion_actualizada' => $resultado['sesion_actualizada'],
                'rol_nombre' => $resultado['rol_nombre'],
            ],
        ]);
    }

    if ($accion === 'eliminar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = eliminarUsuario($conexion);

        responderJson([
            'exito' => true,
            'mensaje' => $resultado['mensaje'],
        ]);
    }

    if ($accion !== 'crear_usuario' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson([
            'exito' => false,
            'mensaje' => 'Método HTTP no válido para la acción solicitada.',
        ], 405);
    }

    $resultado = crearUsuario($conexion);

    responderJson([
        'exito' => true,
        'mensaje' => $resultado['mensaje'],
        'datos' => $resultado,
    ]);
} catch (InvalidArgumentException $error) {
    responderJson([
        'exito' => false,
        'mensaje' => $error->getMessage(),
    ], 422);
} catch (PDOException $error) {
    $esDuplicado = $error->errorInfo[1] ?? null;
    $mensaje = $modulo === 'dashboard'
        ? 'No se pudieron consultar los datos del dashboard.'
        : 'No fue posible guardar los cambios en este momento.';
    if ($esDuplicado === 1062) {
        $mensaje = $accion === 'crear_proveedor_compra'
            ? 'Ya existe un proveedor registrado con ese RUT.'
            : ($accion === 'crear_transportista_compra'
                ? 'Ya existe un transportista registrado con ese nombre.'
                : ($accion === 'registrar_compra'
                    ? 'El código de compra o de producto ya está registrado. Actualiza los datos e inténtalo nuevamente.'
                    : 'El correo electrónico o nombre de usuario ya existe.'));
    }

    responderJson([
        'exito' => false,
        'mensaje' => $mensaje,
    ], 500);
} catch (Throwable $error) {
    responderJson([
        'exito' => false,
        'mensaje' => $modulo === 'dashboard'
            ? 'No fue posible cargar los datos del dashboard. Inténtalo nuevamente.'
            : 'No fue posible completar el registro de la compra. Inténtalo nuevamente.',
    ], 500);
}
