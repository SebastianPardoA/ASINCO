<?php
/**
 * administración.php
 *
 * Muestra el módulo de administración del sistema
 */

$vistaActiva = 'administracion';
$tituloBarraSuperior = 'Administración';
$subtituloBarraSuperior = 'Gestiona usuarios y roles del sistema.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASINCO | Administración</title>
    <link rel="stylesheet" href="../recursos/css/global.css">
    <link rel="stylesheet" href="../recursos/css/administracion.css">
</head>
<body class="vista-interna">
    <?php include 'componentes/barra_lateral.php'; ?>

    <!-- Contenido principal de administración -->
    <main class="vista-interna__contenido">
        <?php include 'componentes/barra_superior.php'; ?>

        <section class="administracion-contenido">
            <!-- Pestañas principales para las secciones del módulo -->
            <nav class="administracion-pestanas" aria-label="Secciones de administración">
                <button class="administracion-pestana administracion-pestana--activa" type="button" data-administracion-pestana="usuarios" aria-selected="true">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                </button>
                <button class="administracion-pestana" type="button" data-administracion-pestana="roles" aria-selected="false">
                    <i class="bi bi-people-fill"></i>
                    <span>Roles</span>
                </button>
            </nav>

            <!-- Panel de usuarios con información obtenida desde la API -->
            <section class="administracion-panel" aria-labelledby="usuariosTitulo" data-administracion-panel="usuarios">
                <div class="administracion-panel__encabezado">
                    <h3 id="usuariosTitulo">Usuarios del sistema</h3>

                    <div class="administracion-filtros">
                        <label class="administracion-busqueda">
                            <i class="bi bi-search"></i>
                            <span class="sr-only">Buscar usuarios</span>
                            <input type="search" placeholder="Buscar Usuarios" data-usuarios-busqueda>
                        </label>

                        <label class="administracion-select">
                            <span class="sr-only">Filtrar por estado</span>
                            <select data-usuarios-filtro-estado>
                                <option value="">Estado: Todos</option>
                                <option value="activo">Estado: Activos</option>
                                <option value="inactivo">Estado: Inactivos</option>
                            </select>
                        </label>

                        <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="crear-usuario">
                            <i class="bi bi-plus-lg"></i>
                            <span>Crear usuario</span>
                        </button>
                    </div>
                </div>

                <!-- Tabla de los usuarios del sistema -->
                <div class="administracion-tabla-contenedor">
                    <table class="administracion-tabla">
                        <thead>
                            <tr>
                                <th class="administracion-tabla__numero">#</th>
                                <th>Nombre</th>
                                <th>Correo electrónico</th>
                                <th>Estado</th>
                                <th>Rol asignado</th>
                                <th>Último acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody data-usuarios-lista>
                            <tr>
                                <td class="administracion-tabla__mensaje" colspan="7">Cargando usuarios...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación del listado de usuarios -->
                <div class="administracion-paginacion">
                    <p data-usuarios-contador>Cargando usuarios...</p>
                    <div class="administracion-paginas" data-usuarios-paginacion></div>
                </div>
            </section>

            <!-- Formulario para crear a un usuario nuevo -->
            <section class="administracion-panel administracion-panel--formulario administracion-panel--oculto" aria-labelledby="crearUsuarioTitulo" data-administracion-panel="crear-usuario" hidden>
                <!-- Mensaje de orientación para completar el formulario -->
                <div class="administracion-formulario__ayuda" id="crearUsuarioTitulo" role="note">
                    <i class="bi bi-info-circle"></i>
                    <span>
                        <strong>Completa los datos del nuevo usuario.</strong>
                        <small>Los campos marcados con * son obligatorios.</small>
                    </span>
                </div>

                <!-- Campos del formulario de usuario -->
                <form class="administracion-formulario" id="formCrearUsuario" action="#" method="post">
                    <div class="administracion-formulario__campos">
                        <div class="administracion-formulario__grid">
                        <label class="administracion-campo">
                            <span>Nombre <b>*</b></span>
                            <input type="text" name="nombre" placeholder="Ej: Benjamin" autocomplete="given-name" required>
                        </label>

                        <label class="administracion-campo">
                            <span>Apellido <b>*</b></span>
                            <input type="text" name="apellido" placeholder="Ej: Jeria" autocomplete="family-name" required>
                        </label>

                        <label class="administracion-campo administracion-campo--ancho-completo">
                            <span>Correo electrónico <b>*</b></span>
                            <input type="email" name="correo_electronico" placeholder="Ej: usuario@asinco.cl" autocomplete="email" required>
                        </label>

                        <label class="administracion-campo">
                            <span>Nombre de usuario <b>*</b></span>
                            <input type="text" name="nombre_usuario" placeholder="Ej: bjeria" autocomplete="username" required>
                        </label>

                        <label class="administracion-campo">
                            <span>Rol asignado</span>
                            <select name="rol">
                                <option value="">Sin asignar</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Compras">Compras</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Usuario">Usuario</option>
                            </select>
                        </label>

                        <label class="administracion-campo">
                            <span>Contraseña <b>*</b></span>
                            <span class="administracion-campo__password">
                                <input type="password" name="contrasena" placeholder="Ingresa una contraseña" autocomplete="new-password" required>
                                <button class="administracion-mostrar-contrasena" type="button" data-toggle-password aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </span>
                        </label>

                        <label class="administracion-campo">
                            <span>Confirmar contraseña <b>*</b></span>
                            <span class="administracion-campo__password">
                                <input type="password" name="contrasena_confirmacion" placeholder="Repite la contraseña" autocomplete="new-password" required>
                                <button class="administracion-mostrar-contrasena" type="button" data-toggle-password aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </span>
                        </label>

                        <label class="administracion-campo">
                            <span>Estado inicial</span>
                            <select name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </label>

                        <label class="administracion-campo">
                            <span>Teléfono</span>
                            <input type="tel" name="telefono" placeholder="Ej: +56 9 1234 5678" autocomplete="tel">
                        </label>
                        </div>
                    </div>
                </form>

            </section>

            <!-- Botones para volver y guardar el usuario -->
            <div class="administracion-formulario__acciones" data-administracion-form-actions hidden>
                <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="volver-usuarios">
                    <i class="bi bi-arrow-left"></i>
                    <span>Volver</span>
                </button>
                <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="guardar-usuario">
                    <i class="bi bi-check-lg"></i>
                    <span>Guardar</span>
                </button>
            </div>

            <!-- Modal de confirmación antes de crear el usuario -->
            <div class="administracion-modal" data-administracion-modal hidden>
                <div class="administracion-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="confirmarUsuarioTitulo">
                    <div class="administracion-modal__encabezado">
                        <i class="bi bi-person-plus"></i>
                        <h3 id="confirmarUsuarioTitulo">Confirmar creación</h3>
                    </div>
                    <p>¿Estás seguro de que deseas crear este usuario?</p>
                    <div class="administracion-modal__acciones">
                        <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="cancelar-creacion">Cancelar</button>
                        <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="confirmar-creacion">Aceptar</button>
                    </div>
                </div>
            </div>

            <!-- Modal de edición de los datos de un usuario existente -->
            <div class="administracion-modal" data-administracion-modal-edicion hidden>
                <div class="administracion-modal__contenido administracion-modal__contenido--edicion" role="dialog" aria-modal="true" aria-labelledby="editarUsuarioTitulo">
                    <div class="administracion-modal__encabezado">
                        <i class="bi bi-pencil-square"></i>
                        <h3 id="editarUsuarioTitulo">Editar usuario</h3>
                    </div>

                    <form class="administracion-formulario-edicion" id="formEditarUsuario" action="#" method="post">
                        <!-- ID oculto del usuario -->
                        <input type="hidden" name="id_usuario" data-usuario-id>
                        <div class="administracion-formulario-edicion__campos">
                            <label class="administracion-campo">
                                <span>Nombre <b>*</b></span>
                                <input type="text" name="nombre" autocomplete="given-name" required>
                            </label>

                            <label class="administracion-campo">
                                <span>Apellido <b>*</b></span>
                                <input type="text" name="apellido" autocomplete="family-name" required>
                            </label>

                            <label class="administracion-campo administracion-formulario-edicion__campo--completo">
                                <span>Correo electrónico <b>*</b></span>
                                <input type="email" name="correo_electronico" autocomplete="email" required>
                            </label>

                            <label class="administracion-campo">
                                <span>Nombre de usuario <b>*</b></span>
                                <input type="text" name="nombre_usuario" autocomplete="username" required>
                            </label>

                            <label class="administracion-campo">
                                <span>Rol asignado</span>
                                <select name="rol">
                                    <option value="">Sin asignar</option>
                                    <option value="Administrador">Administrador</option>
                                    <option value="Compras">Compras</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Usuario">Usuario</option>
                                </select>
                            </label>

                            <label class="administracion-campo">
                                <span>Estado</span>
                                <select name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </label>

                            <label class="administracion-campo">
                                <span>Teléfono</span>
                                <input type="tel" name="telefono" autocomplete="tel">
                            </label>

                            <div class="administracion-cambio-contrasena">
                                <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="abrir-cambio-contrasena">
                                    <i class="bi bi-key"></i>
                                    <span>Cambiar contraseña</span>
                                </button>
                                <span class="administracion-cambio-contrasena__mensaje" data-cambio-contrasena-mensaje hidden>La contraseña fue editada. Guarda los cambios para actualizar la información del usuario.</span>
                            </div>

                        </div>

                        <input type="hidden" name="contrasena_nueva" data-contrasena-nueva>
                        <input type="hidden" name="contrasena_confirmacion_nueva" data-contrasena-confirmacion-nueva>

                        <div class="administracion-modal__acciones">
                            <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="cancelar-edicion">Cancelar</button>
                            <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="guardar-edicion">
                                <i class="bi bi-check-lg"></i>
                                <span>Guardar cambios</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de confirmación antes de guardar la edición -->
            <div class="administracion-modal" data-administracion-modal-confirmar-edicion hidden>
                <div class="administracion-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="confirmarEdicionTitulo">
                    <div class="administracion-modal__encabezado">
                        <i class="bi bi-pencil-square"></i>
                        <h3 id="confirmarEdicionTitulo">Confirmar cambios</h3>
                    </div>
                    <p>¿Estás seguro de que deseas guardar los cambios de este usuario?</p>
                    <div class="administracion-modal__acciones">
                        <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="cancelar-confirmacion-edicion">Cancelar</button>
                        <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="confirmar-edicion">Sí, guardar</button>
                    </div>
                </div>
            </div>

            <!-- Modal pequeño para preparar el cambio de contraseña -->
            <div class="administracion-modal" data-administracion-modal-contrasena hidden>
                <div class="administracion-modal__contenido administracion-modal__contenido--contrasena" role="dialog" aria-modal="true" aria-labelledby="cambiarContrasenaTitulo">
                    <div class="administracion-modal__encabezado">
                        <i class="bi bi-key"></i>
                        <h3 id="cambiarContrasenaTitulo">Cambiar contraseña</h3>
                    </div>

                    <form id="formCambiarContrasena" class="administracion-formulario-contrasena" action="#" method="post">
                        <label class="administracion-campo">
                            <span>Nueva contraseña <b>*</b></span>
                            <span class="administracion-campo__password">
                                <input type="password" name="contrasena" autocomplete="new-password" required>
                                <button class="administracion-mostrar-contrasena" type="button" data-toggle-password aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                            </span>
                        </label>

                        <label class="administracion-campo">
                            <span>Confirmar contraseña <b>*</b></span>
                            <span class="administracion-campo__password">
                                <input type="password" name="contrasena_confirmacion" autocomplete="new-password" required>
                                <button class="administracion-mostrar-contrasena" type="button" data-toggle-password aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                            </span>
                        </label>

                        <div class="administracion-modal__acciones">
                            <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="cancelar-cambio-contrasena">Cancelar</button>
                            <button class="administracion-boton administracion-boton--principal" type="button" data-administracion-accion="aceptar-cambio-contrasena">Aceptar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de confirmación antes de eliminar un usuario -->
            <div class="administracion-modal" data-administracion-modal-eliminacion hidden>
                <div class="administracion-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="eliminarUsuarioTitulo">
                    <div class="administracion-modal__encabezado">
                        <i class="bi bi-trash3"></i>
                        <h3 id="eliminarUsuarioTitulo">Eliminar usuario</h3>
                    </div>
                    <p>¿Estás seguro de que deseas eliminar este usuario? Sus datos no podrán recuperarse.</p>
                    <div class="administracion-modal__acciones">
                        <button class="administracion-boton administracion-boton--secundario" type="button" data-administracion-accion="cancelar-eliminacion">Cancelar</button>
                        <button class="administracion-boton administracion-boton--eliminar" type="button" data-administracion-accion="confirmar-eliminacion">Sí, eliminar</button>
                    </div>
                </div>
            </div>

            <!-- Sección para gestionar los roles -->
            <section class="administracion-panel administracion-panel--oculto" aria-labelledby="rolesTitulo" data-administracion-panel="roles" hidden>
                <div class="administracion-panel__encabezado">
                    <h3 id="rolesTitulo">Roles del sistema</h3>
                    <button class="administracion-boton administracion-boton--principal" type="button">
                        <i class="bi bi-plus-lg"></i>
                        <span>Crear rol</span>
                    </button>
                </div>

                <!-- Tabla de los roles del sistema -->
                <div class="administracion-tabla-contenedor">
                    <table class="administracion-tabla administracion-tabla--roles">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Descripción</th>
                                <th>Usuarios</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Administrador</strong></td>
                                <td>Acceso total al sistema y configuración.</td>
                                <td>2</td>
                                <td><div class="administracion-acciones"><button type="button" aria-label="Ver rol"><i class="bi bi-eye"></i></button><button type="button" aria-label="Editar rol"><i class="bi bi-pencil"></i></button><button class="administracion-accion--eliminar" type="button" aria-label="Eliminar rol"><i class="bi bi-trash3"></i></button></div></td>
                            </tr>
                            <tr>
                                <td><strong>Compras</strong></td>
                                <td>Gestiona solicitudes, compras y proveedores.</td>
                                <td>1</td>
                                <td><div class="administracion-acciones"><button type="button" aria-label="Ver rol"><i class="bi bi-eye"></i></button><button type="button" aria-label="Editar rol"><i class="bi bi-pencil"></i></button><button class="administracion-accion--eliminar" type="button" aria-label="Eliminar rol"><i class="bi bi-trash3"></i></button></div></td>
                            </tr>
                            <tr>
                                <td><strong>Supervisor</strong></td>
                                <td>Supervisa procesos y genera reportes.</td>
                                <td>1</td>
                                <td><div class="administracion-acciones"><button type="button" aria-label="Ver rol"><i class="bi bi-eye"></i></button><button type="button" aria-label="Editar rol"><i class="bi bi-pencil"></i></button><button class="administracion-accion--eliminar" type="button" aria-label="Eliminar rol"><i class="bi bi-trash3"></i></button></div></td>
                            </tr>
                            <tr>
                                <td><strong>Usuario</strong></td>
                                <td>Acceso limitado a los módulos asignados.</td>
                                <td>2</td>
                                <td><div class="administracion-acciones"><button type="button" aria-label="Ver rol"><i class="bi bi-eye"></i></button><button type="button" aria-label="Editar rol"><i class="bi bi-pencil"></i></button><button class="administracion-accion--eliminar" type="button" aria-label="Eliminar rol"><i class="bi bi-trash3"></i></button></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación de los roles -->
                <div class="administracion-paginacion">
                    <p>Mostrando 1 a 4 de 4 roles</p>
                    <div class="administracion-paginas">
                        <button type="button" aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>
                        <button class="administracion-pagina--activa" type="button">1</button>
                        <button type="button" aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            </section>
        </section>

        <?php include 'componentes/footer.php'; ?>
    </main>
    <script src="../recursos/js/administracion.js" defer></script>
</body>
</html>
