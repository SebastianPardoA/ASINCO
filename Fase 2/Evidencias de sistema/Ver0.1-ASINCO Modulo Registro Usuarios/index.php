<?php
/**
 * index.php
 *
 * Muestra la pantalla inicial de login del sistema ASINCO
 * Valida credenciales reales y redirige al dashboard al iniciar sesión correctamente
 */

require_once __DIR__ . '/configuracion/base_datos.php';
require_once __DIR__ . '/configuracion/iniciar_sesion.php';

/* Evita mostrar el login cuando ya existe una sesión válida */
if (haySesionActiva()) {
    header('Location: vistas/dashboard.php');
    exit;
}

$mensajeError = '';
$mensajeInformativo = '';
$notificacionSesionCerrada = '';

/* Procesa el formulario de acceso contra la tabla de usuarios */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreUsuario = trim((string) ($_POST['usuario'] ?? ''));
    $contrasena = (string) ($_POST['contrasena'] ?? '');

    if ($nombreUsuario === '' || $contrasena === '') {
        $mensajeError = 'Ingresa tu usuario y contraseña.';
    } else {
        try {
            if (autenticarUsuario(obtenerConexionBaseDatos(), $nombreUsuario, $contrasena)) {
                header('Location: vistas/dashboard.php');
                exit;
            }

            $mensajeError = 'El usuario o la contraseña no son válidos.';
        } catch (PDOException $error) {
            $mensajeError = 'No fue posible validar las credenciales en este momento.';
        }
    }
}

if (($_GET['mensaje'] ?? '') === 'sesion_requerida') {
    $mensajeInformativo = 'Debes iniciar sesión para acceder a la plataforma.';
}

if (($_GET['mensaje'] ?? '') === 'sesion_cerrada') {
    $notificacionSesionCerrada = 'La sesión se cerró correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Login</title>
    <link rel="stylesheet" href="recursos/css/global.css">
    <link rel="stylesheet" href="recursos/css/index.css">
</head>
<body class="login-pagina">
    <?php if ($notificacionSesionCerrada !== ''): ?>
        <!-- Notificación temporal mostrada después de cerrar la sesión -->
        <div class="login-notificacion" role="status" data-login-notificacion>
            <?php echo htmlspecialchars($notificacionSesionCerrada, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Contenedor principal del login -->
    <main class="login-contenedor">
        <!-- Tarjeta con el formulario de acceso visual -->
        <section class="login-panel">
            <div class="login-encabezado">
                <div class="login-marca" aria-label="ASINCO">
                    <span class="login-marca__icono" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                    <h1>ASINCO</h1>
                </div>
                <p>Ingreso al sistema</p>
            </div>

            <?php if ($mensajeInformativo !== ''): ?>
                <p class="login-mensaje login-mensaje--informativo"><?php echo htmlspecialchars($mensajeInformativo, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($mensajeError !== ''): ?>
                <p class="login-mensaje login-mensaje--error" role="alert"><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form class="login-formulario" action="index.php" method="post">
                <!-- Campo para ingresar el nombre de usuario -->
                <div class="campo-formulario">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" autocomplete="username" required>
                </div>

                <!-- Campo para ingresar la contraseña del usuario -->
                <div class="campo-formulario">
                    <label for="contrasena">Contraseña</label>
                    <div class="campo-contrasena">
                        <input type="password" id="contrasena" name="contrasena" autocomplete="current-password" required>
                        <button type="button" class="campo-contrasena__alternar" aria-label="Mostrar contraseña" data-login-toggle-password>
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="boton-principal">Entrar</button>
            </form>
        </section>
    </main>
    <script src="recursos/js/index.js" defer></script>
</body>
</html>
