/**
 * administración.js
 *
 * Controla las pestañas, los formularios de usuarios y sus modales
 * Consulta y actualiza usuarios mediante la API del módulo de administración
 */

let filtroEstadoUsuarios = '';
let filtroBusquedaUsuarios = '';
let temporizadorBusquedaUsuarios;
let idUsuarioPendienteEliminacion = null;

/**
 * Cambia el panel visible y actualiza el estado visual de las pestañas
 */
function cambiarPestanaAdministracion(pestanaSeleccionada) {
    const pestanas = document.querySelectorAll('[data-administracion-pestana]');
    const paneles = document.querySelectorAll('[data-administracion-panel]');
    const accionesFormulario = document.querySelector('[data-administracion-form-actions]');

    /* Los botones Volver y Guardar solo pertenecen al formulario de creación */
    if (accionesFormulario) {
        accionesFormulario.hidden = true;
        accionesFormulario.style.display = 'none';
    }

    pestanas.forEach((pestana) => {
        const estaActiva = pestana.dataset.administracionPestana === pestanaSeleccionada;
        pestana.classList.toggle('administracion-pestana--activa', estaActiva);
        pestana.setAttribute('aria-selected', estaActiva ? 'true' : 'false');
    });

    paneles.forEach((panel) => {
        const debeMostrarse = panel.dataset.administracionPanel === pestanaSeleccionada;
        panel.hidden = !debeMostrarse;
        panel.classList.toggle('administracion-panel--oculto', !debeMostrarse);
    });
}

/*  Muestra el formulario de creación o el listado de usuarios */
function cambiarVistaAdministracion(vistaSeleccionada) {
    const pestanas = document.querySelector('.administracion-pestanas');
    const paneles = document.querySelectorAll('[data-administracion-panel]');
    const accionesFormulario = document.querySelector('[data-administracion-form-actions]');
    const mostrarFormulario = vistaSeleccionada === 'crear-usuario';

    if (pestanas) {
        pestanas.hidden = mostrarFormulario;
    }

    if (accionesFormulario) {
        accionesFormulario.hidden = !mostrarFormulario;
        accionesFormulario.style.display = mostrarFormulario ? 'flex' : 'none';
    }

    paneles.forEach((panel) => {
        const debeMostrarse = panel.dataset.administracionPanel === vistaSeleccionada;
        panel.hidden = !debeMostrarse;
        panel.classList.toggle('administracion-panel--oculto', !debeMostrarse);
    });
}

/* Abre o cierra la visibilidad del campo de contraseña */
function alternarVisibilidadContrasena(boton) {
    const campo = boton.closest('.administracion-campo__password').querySelector('input');
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    boton.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
}

/* Muestra el modal de confirmación si el formulario cumple sus validaciones HTML */
function abrirConfirmacionCreacion() {
    const formulario = document.getElementById('formCrearUsuario');
    const modal = document.querySelector('[data-administracion-modal]');

    if (!formulario || !modal) {
        return;
    }

    if (!formulario.reportValidity()) {
        return;
    }

    modal.hidden = false;
}

/* Cierra el modal de confirmación sin enviar datos */
function cerrarConfirmacionCreacion() {
    const modal = document.querySelector('[data-administracion-modal]');

    if (modal) {
        modal.hidden = true;
    }
}

/* Envía el nuevo usuario a la API y vuelve al listado cuando se crea correctamente */
async function confirmarCreacionUsuario() {
    const formulario = document.getElementById('formCrearUsuario');

    if (!formulario) {
        return;
    }

    const botonConfirmar = document.querySelector('[data-administracion-accion="confirmar-creacion"]');
    const datosFormulario = new FormData(formulario);

    if (botonConfirmar) {
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Guardando...';
    }

    try {
        const respuesta = await fetch('../api/api.php?modulo=administracion&accion=crear_usuario', {
            method: 'POST',
            body: datosFormulario,
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible crear el usuario.');
        }

        cerrarConfirmacionCreacion();
        formulario.reset();
        cambiarVistaAdministracion('usuarios');
        cargarUsuarios(1);
        window.alert(resultado.mensaje);
    } catch (error) {
        cerrarConfirmacionCreacion();
        window.alert(error.message);
    } finally {
        if (botonConfirmar) {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Aceptar';
        }
    }
}

/* Carga los datos de un usuario y abre el modal de edición */
async function abrirModalEdicionUsuario(idUsuario) {
    const modal = document.querySelector('[data-administracion-modal-edicion]');
    const formulario = document.getElementById('formEditarUsuario');

    if (!modal || !formulario || !idUsuario) {
        return;
    }

    try {
        const parametros = new URLSearchParams({
            modulo: 'administracion',
            accion: 'obtener_usuario',
            id_usuario: String(idUsuario),
        });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`);
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible cargar el usuario.');
        }

        const usuario = resultado.datos;
        Object.entries(usuario).forEach(([campo, valor]) => {
            const control = formulario.elements.namedItem(campo);

            if (control) {
                control.value = valor ?? '';
            }
        });

        formulario.elements.namedItem('contrasena_nueva').value = '';
        formulario.elements.namedItem('contrasena_confirmacion_nueva').value = '';
        const mensajeContrasena = document.querySelector('[data-cambio-contrasena-mensaje]');
        if (mensajeContrasena) {
            mensajeContrasena.hidden = true;
        }

        modal.hidden = false;
    } catch (error) {
        window.alert(error.message);
    }
}

/* Cierra el modal de edición sin guardar cambios */
function cerrarModalEdicionUsuario() {
    const modal = document.querySelector('[data-administracion-modal-edicion]');

    if (modal) {
        modal.hidden = true;
    }
}

/* Abre la confirmación antes de guardar los cambios editados */
function abrirConfirmacionEdicion() {
    const formulario = document.getElementById('formEditarUsuario');
    const modal = document.querySelector('[data-administracion-modal-confirmar-edicion]');

    if (!formulario || !modal || !formulario.reportValidity()) {
        return;
    }

    modal.hidden = false;
}

/* Cierra la confirmación de edición sin cerrar el formulario principal */
function cerrarConfirmacionEdicion() {
    const modal = document.querySelector('[data-administracion-modal-confirmar-edicion]');

    if (modal) {
        modal.hidden = true;
    }
}

/* Abre el modal donde se prepara una nueva contraseña sin guardarla aún */
function abrirCambioContrasena() {
    const modal = document.querySelector('[data-administracion-modal-contrasena]');
    const formulario = document.getElementById('formCambiarContrasena');

    if (modal && formulario) {
        formulario.reset();
        modal.hidden = false;
    }
}

/* Cierra el modal de cambio de contraseña y descarta sus campos temporales */
function cerrarCambioContrasena() {
    const modal = document.querySelector('[data-administracion-modal-contrasena]');
    const formulario = document.getElementById('formCambiarContrasena');

    if (modal) {
        modal.hidden = true;
    }

    if (formulario) {
        formulario.reset();
    }
}

/* Copia la nueva contraseña al formulario principal para guardarla después */
function aceptarCambioContrasena() {
    const formulario = document.getElementById('formCambiarContrasena');
    const formularioEdicion = document.getElementById('formEditarUsuario');
    const mensaje = document.querySelector('[data-cambio-contrasena-mensaje]');

    if (!formulario || !formularioEdicion || !formulario.reportValidity()) {
        return;
    }

    const contrasena = formulario.elements.namedItem('contrasena').value;
    const confirmacion = formulario.elements.namedItem('contrasena_confirmacion').value;

    if (contrasena.length < 8) {
        window.alert('La contraseña debe tener al menos 8 caracteres.');
        return;
    }

    if (contrasena !== confirmacion) {
        window.alert('Las contrasenas no coinciden.');
        return;
    }

    formularioEdicion.elements.namedItem('contrasena_nueva').value = contrasena;
    formularioEdicion.elements.namedItem('contrasena_confirmacion_nueva').value = confirmacion;

    if (mensaje) {
        mensaje.hidden = false;
    }

    cerrarCambioContrasena();
}

/* Guarda los cambios editables del usuario y actualiza el listado */
async function guardarEdicionUsuario() {
    const formulario = document.getElementById('formEditarUsuario');
    const botonConfirmar = document.querySelector('[data-administracion-accion="confirmar-edicion"]');

    if (!formulario || !formulario.reportValidity()) {
        return;
    }

    if (botonConfirmar) {
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Guardando...';
    }

    try {
        const respuesta = await fetch('../api/api.php?modulo=administracion&accion=actualizar_usuario', {
            method: 'POST',
            body: new FormData(formulario),
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible actualizar el usuario.');
        }

        cerrarConfirmacionEdicion();
        cerrarModalEdicionUsuario();
        await cargarUsuarios(1, filtroEstadoUsuarios, filtroBusquedaUsuarios);
        window.alert(resultado.mensaje);
    } catch (error) {
        cerrarConfirmacionEdicion();
        window.alert(error.message);
    } finally {
        if (botonConfirmar) {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Sí, guardar';
        }
    }
}

/* Evita que el texto se interprete como HTML */
function escaparHtmlAdministracion(valor) {
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[caracter]));
}

/* Obtiene las iniciales del usuario */
function obtenerInicialesUsuario(usuario) {
    const nombre = String(usuario.nombre || '').trim();
    const apellido = String(usuario.apellido || '').trim();
    return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
}

/* Abre el modal de confirmación para eliminar un usuario */
function abrirConfirmacionEliminacion(idUsuario) {
    const modal = document.querySelector('[data-administracion-modal-eliminacion]');

    if (!modal || !idUsuario) {
        return;
    }

    idUsuarioPendienteEliminacion = idUsuario;
    modal.hidden = false;
}

/* Cierra el modal de eliminación y limpia el usuario seleccionado */
function cerrarConfirmacionEliminacion() {
    const modal = document.querySelector('[data-administracion-modal-eliminacion]');

    if (modal) {
        modal.hidden = true;
    }

    idUsuarioPendienteEliminacion = null;
}

/* Elimina el usuario confirmado y recarga el listado actual */
async function confirmarEliminacionUsuario() {
    const botonConfirmar = document.querySelector('[data-administracion-accion="confirmar-eliminacion"]');

    if (!idUsuarioPendienteEliminacion) {
        return;
    }

    if (botonConfirmar) {
        botonConfirmar.disabled = true;
        botonConfirmar.textContent = 'Eliminando...';
    }

    try {
        const datos = new FormData();
        datos.append('id_usuario', String(idUsuarioPendienteEliminacion));
        const respuesta = await fetch('../api/api.php?modulo=administracion&accion=eliminar_usuario', {
            method: 'POST',
            body: datos,
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible eliminar el usuario.');
        }

        cerrarConfirmacionEliminacion();
        await cargarUsuarios(1, filtroEstadoUsuarios, filtroBusquedaUsuarios);
        window.alert(resultado.mensaje);
    } catch (error) {
        cerrarConfirmacionEliminacion();
        window.alert(error.message);
    } finally {
        if (botonConfirmar) {
            botonConfirmar.disabled = false;
            botonConfirmar.textContent = 'Sí, eliminar';
        }
    }
}

/* Crea la paginación de los usuarios */
function renderizarPaginacionUsuarios(paginacion, estado = filtroEstadoUsuarios, busqueda = filtroBusquedaUsuarios) {
    const contenedor = document.querySelector('[data-usuarios-paginacion]');

    if (!contenedor) {
        return;
    }

    const paginaActual = Number(paginacion.pagina_actual || 1);
    const totalPaginas = Number(paginacion.total_paginas || 1);
    let botones = '';

    botones += `<button type="button" aria-label="Página anterior" data-usuarios-pagina="${Math.max(1, paginaActual - 1)}" ${paginaActual === 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>`;

    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
        botones += `<button type="button" class="${pagina === paginaActual ? 'administracion-pagina--activa' : ''}" data-usuarios-pagina="${pagina}">${pagina}</button>`;
    }

    botones += `<button type="button" aria-label="Página siguiente" data-usuarios-pagina="${Math.min(totalPaginas, paginaActual + 1)}" ${paginaActual === totalPaginas ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = botones;

    contenedor.querySelectorAll('[data-usuarios-pagina]').forEach((boton) => {
        boton.addEventListener('click', () => cargarUsuarios(Number(boton.dataset.usuariosPagina), estado, busqueda));
    });
}

/* Muestra los usuarios en la tabla recibidas desde la API*/
function renderizarUsuarios(usuarios, paginacion) {
    const lista = document.querySelector('[data-usuarios-lista]');
    const contador = document.querySelector('[data-usuarios-contador]');
    const coloresAvatar = ['celeste', 'morado', 'verde', 'naranja'];

    if (!lista || !contador) {
        return;
    }

    if (!usuarios.length) {
        lista.innerHTML = '<tr><td class="administracion-tabla__mensaje" colspan="7">Ningun usuario cumple con el filtro seleccionado.</td></tr>';
        contador.textContent = 'Mostrando 0 usuarios';
        renderizarPaginacionUsuarios(paginacion);
        return;
    }

    lista.innerHTML = usuarios.map((usuario, indice) => {
        const nombreCompleto = `${usuario.nombre} ${usuario.apellido}`;
        const estadoActivo = usuario.estado === 'activo';
        const claseEstado = estadoActivo ? 'activo' : 'inactivo';
        const textoEstado = estadoActivo ? 'Activo' : 'Inactivo';
        const claseAvatar = coloresAvatar[indice % coloresAvatar.length];

        const numeroUsuario = ((paginacion.pagina_actual - 1) * paginacion.usuarios_por_pagina) + indice + 1;

        return `
            <tr>
                <td class="administracion-tabla__numero">${numeroUsuario}</td>
                <td><div class="administracion-usuario"><span class="administracion-avatar administracion-avatar--${claseAvatar}">${escaparHtmlAdministracion(obtenerInicialesUsuario(usuario))}</span><strong>${escaparHtmlAdministracion(nombreCompleto)}</strong></div></td>
                <td>${escaparHtmlAdministracion(usuario.correo_electronico)}</td>
                <td><span class="administracion-estado administracion-estado--${claseEstado}">${textoEstado}</span></td>
                <td>${escaparHtmlAdministracion(usuario.rol_nombre)}</td>
                <td>${escaparHtmlAdministracion(usuario.ultimo_acceso)}</td>
                <td><div class="administracion-acciones"><button type="button" aria-label="Editar usuario" data-usuario-editar="${usuario.id_usuario}"><i class="bi bi-pencil"></i></button><button class="administracion-accion--eliminar" type="button" aria-label="Eliminar usuario" data-usuario-eliminar="${usuario.id_usuario}"><i class="bi bi-trash3"></i></button></div></td>
            </tr>`;
    }).join('');

    const inicio = ((paginacion.pagina_actual - 1) * paginacion.usuarios_por_pagina) + 1;
    const fin = inicio + usuarios.length - 1;
    contador.textContent = `Mostrando ${inicio} a ${fin} de ${paginacion.total_usuarios} usuarios`;
    renderizarPaginacionUsuarios(paginacion);
}

/* Carga el listado de usuarios */
async function cargarUsuarios(pagina = 1, estado = filtroEstadoUsuarios, busqueda = filtroBusquedaUsuarios) {
    const lista = document.querySelector('[data-usuarios-lista]');
    const contador = document.querySelector('[data-usuarios-contador]');

    if (!lista || !contador) {
        return;
    }

    filtroEstadoUsuarios = estado;
    filtroBusquedaUsuarios = busqueda;

    try {
        const parametros = new URLSearchParams({
            modulo: 'administracion',
            accion: 'listar_usuarios',
            pagina: String(pagina),
            estado,
            busqueda,
        });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`);
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible cargar los usuarios.');
        }

        renderizarUsuarios(resultado.datos.usuarios, resultado.datos.paginacion);
    } catch (error) {
        lista.innerHTML = `<tr><td class="administracion-tabla__mensaje" colspan="7">${escaparHtmlAdministracion(error.message)}</td></tr>`;
        contador.textContent = 'No fue posible cargar el listado';
    }
}

/* Registra los eventos de las pestañas cuando el documento ya está disponible */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-administracion-pestana]').forEach((pestana) => {
        pestana.addEventListener('click', () => {
            const pestanaSeleccionada = pestana.dataset.administracionPestana;

            if (pestanaSeleccionada === 'usuarios' || pestanaSeleccionada === 'roles') {
                cambiarPestanaAdministracion(pestanaSeleccionada);
            }
        });
    });

    /* Abre o cierra el formulario de creación de usuarios */
    document.querySelectorAll('[data-administracion-accion]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const accion = boton.dataset.administracionAccion;

            if (accion === 'crear-usuario') {
                cambiarVistaAdministracion('crear-usuario');
            }

            if (accion === 'volver-usuarios') {
                cambiarVistaAdministracion('usuarios');
            }

            if (accion === 'guardar-usuario') {
                abrirConfirmacionCreacion();
            }

            if (accion === 'cancelar-creacion') {
                cerrarConfirmacionCreacion();
            }

            if (accion === 'confirmar-creacion') {
                confirmarCreacionUsuario();
            }

            if (accion === 'cancelar-edicion') {
                cerrarModalEdicionUsuario();
            }

            if (accion === 'guardar-edicion') {
                abrirConfirmacionEdicion();
            }

            if (accion === 'cancelar-confirmacion-edicion') {
                cerrarConfirmacionEdicion();
            }

            if (accion === 'confirmar-edicion') {
                guardarEdicionUsuario();
            }

            if (accion === 'abrir-cambio-contrasena') {
                abrirCambioContrasena();
            }

            if (accion === 'cancelar-cambio-contrasena') {
                cerrarCambioContrasena();
            }

            if (accion === 'aceptar-cambio-contrasena') {
                aceptarCambioContrasena();
            }

            if (accion === 'cancelar-eliminacion') {
                cerrarConfirmacionEliminacion();
            }

            if (accion === 'confirmar-eliminacion') {
                confirmarEliminacionUsuario();
            }
        });
    });

    /* Abre el modal al seleccionar el icono de una fila */
    const listaUsuarios = document.querySelector('[data-usuarios-lista]');
    if (listaUsuarios) {
        listaUsuarios.addEventListener('click', (evento) => {
            const botonEditar = evento.target.closest('[data-usuario-editar]');
            const botonEliminar = evento.target.closest('[data-usuario-eliminar]');

            if (botonEditar) {
                abrirModalEdicionUsuario(Number(botonEditar.dataset.usuarioEditar));
            }

            if (botonEliminar) {
                abrirConfirmacionEliminacion(Number(botonEliminar.dataset.usuarioEliminar));
            }
        });
    }

    /* Registra los botones de los campos de contraseña */
    document.querySelectorAll('[data-toggle-password]').forEach((boton) => {
        boton.addEventListener('click', () => alternarVisibilidadContrasena(boton));
    });

    /* Recarga el listado cuando cambia el filtro de estado */
    const filtroEstado = document.querySelector('[data-usuarios-filtro-estado]');
    if (filtroEstado) {
        filtroEstado.addEventListener('change', () => {
            cargarUsuarios(1, filtroEstado.value, filtroBusquedaUsuarios);
        });
    }

    /* Busca por nombre completo mientras el usuario escribe */
    const campoBusqueda = document.querySelector('[data-usuarios-busqueda]');
    if (campoBusqueda) {
        campoBusqueda.addEventListener('input', () => {
            clearTimeout(temporizadorBusquedaUsuarios);
            temporizadorBusquedaUsuarios = setTimeout(() => {
                cargarUsuarios(1, filtroEstadoUsuarios, campoBusqueda.value.trim());
            }, 300);
        });
    }

    /* Carga el listado al abrir la vista de administración */
    cargarUsuarios(1);
});
