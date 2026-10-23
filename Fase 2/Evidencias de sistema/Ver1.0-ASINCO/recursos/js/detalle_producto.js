/**
 * detalle_producto.js
 *
 * Consulta y muestra los datos del producto seleccionado desde el inventario
 */

let paginaDetalleProducto = 1;
let idImagenProductoAEliminar = 0;
let urlVistaPreviaImagenProducto = '';
let idUnidadAsignacionActual = 0;
let idUnidadEstadoActual = 0;
let temporizadorFiltrosUnidadesDetalle;

/* Evita que el texto se interprete como HTML */
function escaparDetalleProducto(valor)
{
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[caracter]));
}

/* Formatea fechas SQL para mostrarlas en formato chileno */
function formatearFechaDetalleProducto(fecha)
{
    if (!fecha) return '--';
    const partes = String(fecha).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : escaparDetalleProducto(fecha);
}

/* Formatea precios conservando la moneda de la compra */
function formatearPrecioDetalleProducto(valor, moneda)
{
    return `${escaparDetalleProducto(moneda || '')} ${Number(valor || 0).toLocaleString('es-CL', { maximumFractionDigits: 2 })}`.trim();
}

/* Traduce y clasifica los estados de las unidades */
function estadoDetalleProducto(estado)
{
    const textos = { disponible: 'Disponible', asignada: 'Asignada', en_uso: 'En uso', reservada: 'Reservada', danada: 'Dañada', dada_de_baja: 'Dada de baja' };
    const clases = { disponible: 'disponible', asignada: 'asignado', en_uso: 'uso', reservada: 'reservado' };
    return { texto: textos[estado] || estado || 'Sin estado', clase: clases[estado] || 'disponible' };
}

/* Construye estrellas y valor numérico para una calificación */
function mostrarEstrellasDetalleProducto(calificacion)
{
    if (calificacion === null || calificacion === undefined) return 'Sin calificación';
    const llenas = Math.max(0, Math.min(5, Math.round(Number(calificacion))));
    return `<span class="detalle-estrellas">${'★'.repeat(llenas)}${'☆'.repeat(5 - llenas)}</span> ${Number(calificacion).toFixed(1)}`;
}

/* Renderiza la galería y permite cambiar la imagen principal */
function renderizarGaleriaDetalleProducto(imagenes, nombreProducto)
{
    const principal = document.querySelector('[data-producto-imagen-principal]');
    const miniaturas = document.querySelector('[data-producto-miniaturas]');
    const botonAnterior = document.querySelector('[data-producto-carrusel-anterior]');
    const botonSiguiente = document.querySelector('[data-producto-carrusel-siguiente]');
    const controlPrincipal = document.querySelector('[data-producto-principal]');
    const checkboxPrincipal = document.querySelector('[data-producto-marcar-principal]');
    const rutaRespaldo = obtenerRutaImagenSinRegistro('producto');
    const lista = Array.isArray(imagenes) && imagenes.length
        ? imagenes.filter((imagen) => imagen.imagen)
        : [];
    const imagenesMostrar = lista.length ? lista : [{ imagen: rutaRespaldo, nombre_archivo: 'Imagen predeterminada', predeterminada: true }];

    let indiceActual = 0;
    const actualizarPrincipal = (indice) => {
        indiceActual = (indice + imagenesMostrar.length) % imagenesMostrar.length;
        const imagen = imagenesMostrar[indiceActual];
        principal.src = imagen.imagen;
        principal.alt = `Imagen de ${nombreProducto}`;
        miniaturas.querySelectorAll('button').forEach((item, indiceMiniatura) => item.classList.toggle('detalle-miniatura-activa', indiceMiniatura === indiceActual));
        const esImagenReal = !imagen.predeterminada;
        controlPrincipal.hidden = !esImagenReal;
        checkboxPrincipal.checked = esImagenReal && indiceActual === 0;
    };
    miniaturas.innerHTML = imagenesMostrar.map((imagen, indice) => imagen.predeterminada
        ? `<div class="detalle-producto__miniatura detalle-producto__miniatura--predeterminada"><button type="button" class="detalle-miniatura-boton ${indice === 0 ? 'detalle-miniatura-activa' : ''}" data-detalle-imagen-indice="${indice}" aria-label="Imagen predeterminada"><img src="${escaparDetalleProducto(imagen.imagen)}" alt=""></button></div>`
        : `<div class="detalle-producto__miniatura"><button type="button" class="detalle-miniatura-boton ${indice === 0 ? 'detalle-miniatura-activa' : ''}" data-detalle-imagen-indice="${indice}" aria-label="${escaparDetalleProducto(imagen.nombre_archivo || `Imagen ${indice + 1}`)}"><img src="${escaparDetalleProducto(imagen.imagen)}" alt=""></button><button type="button" class="detalle-miniatura__eliminar" data-producto-eliminar-imagen="${Number(imagen.id_imagen)}" aria-label="Eliminar imagen" title="Eliminar imagen"><i class="bi bi-trash" aria-hidden="true"></i></button></div>`).join('');
    actualizarPrincipal(0);
    const tieneVariasImagenes = imagenesMostrar.length > 1;
    botonAnterior.hidden = !tieneVariasImagenes;
    botonSiguiente.hidden = !tieneVariasImagenes;
    botonAnterior.onclick = () => actualizarPrincipal(indiceActual - 1);
    botonSiguiente.onclick = () => actualizarPrincipal(indiceActual + 1);
    miniaturas.querySelectorAll('[data-detalle-imagen-indice]').forEach((boton) => boton.addEventListener('click', () => {
        actualizarPrincipal(Number(boton.dataset.detalleImagenIndice));
    }));
    checkboxPrincipal.onchange = () => {
        const imagenActual = imagenesMostrar[indiceActual];
        if (!checkboxPrincipal.checked || imagenActual.predeterminada) {
            checkboxPrincipal.checked = imagenActual && !imagenActual.predeterminada && indiceActual === 0;
            return;
        }
        marcarImagenPrincipalProducto(Number(imagenActual.id_imagen));
    };
}

/* Renderiza los datos generales y los indicadores de stock */
function renderizarFichaDetalleProducto(datos)
{
    const producto = datos.producto;
    const stock = datos.stock || {};
    document.querySelector('[data-producto-estado]').textContent = producto.activo ? 'Activo' : 'Inactivo';
    document.querySelector('[data-producto-estado]').classList.toggle('detalle-producto__estado--inactivo', !producto.activo);
    document.querySelector('[data-producto-nombre]').textContent = producto.nombre;
    document.querySelector('[data-producto-codigo]').textContent = producto.codigo_producto;
    document.querySelector('[data-producto-categoria]').textContent = producto.categoria;
    document.querySelector('[data-producto-descripcion]').textContent = producto.descripcion || 'Sin descripción registrada.';
    document.querySelector('[data-producto-stock-total]').textContent = Number(stock.total || 0).toLocaleString('es-CL');
    document.querySelector('[data-producto-stock-asignado]').textContent = Number(stock.asignado || 0).toLocaleString('es-CL');
    document.querySelector('[data-producto-stock-disponible]').textContent = Number(stock.disponible || 0).toLocaleString('es-CL');
    document.querySelector('[data-producto-unidad-medida]').textContent = producto.unidad_medida || 'Unidades';
    document.querySelector('[data-producto-unidad-medida-asignado]').textContent = producto.unidad_medida || 'Unidades';
    document.querySelector('[data-producto-unidad-medida-disponible]').textContent = producto.unidad_medida || 'Unidades';
    renderizarGaleriaDetalleProducto(datos.imagenes || [], producto.nombre);
}

/* Renderiza las tarjetas de mejores calificaciones relacionadas */
function renderizarCalificacionesDetalleProducto(calificaciones)
{
    const contenedor = document.querySelector('[data-producto-calificaciones]');
    const rutaRespaldoProveedor = obtenerRutaImagenSinRegistro('proveedor');
    const rutaRespaldoTransportista = obtenerRutaImagenSinRegistro('transportista');
    const tarjetas = [
        { titulo: 'Calificación del mejor proveedor', tipo: 'proveedor', dato: calificaciones?.proveedor },
        { titulo: 'Calificación del mejor transportista', tipo: 'transportista', dato: calificaciones?.transportista },
    ];
    contenedor.innerHTML = tarjetas.map((tarjeta) => tarjeta.dato
        ? `<article class="detalle-calificacion"><h3>${tarjeta.titulo}</h3><img class="detalle-calificacion__imagen" src="${escaparDetalleProducto(tarjeta.dato.imagen || (tarjeta.tipo === 'proveedor' ? rutaRespaldoProveedor : rutaRespaldoTransportista))}" alt="Imagen de ${escaparDetalleProducto(tarjeta.dato.nombre)}"><div><strong>${mostrarEstrellasDetalleProducto(tarjeta.dato.calificacion)}</strong><p>${escaparDetalleProducto(tarjeta.dato.nombre)}</p><small>Basado en ${Number(tarjeta.dato.compras || 0).toLocaleString('es-CL')} compras</small><span class="detalle-calificacion__estado">Confiable</span></div></article>`
        : `<article class="detalle-calificacion detalle-calificacion--vacia"><h3>${tarjeta.titulo}</h3><div class="detalle-calificacion__vacio"><span class="detalle-calificacion__vacio-icon"><i class="bi bi-star" aria-hidden="true"></i></span><div><strong>Sin calificaciones registradas</strong><small>Aún no hay evaluaciones disponibles.</small></div></div></article>`).join('');
}

/* Obtiene los filtros actuales del listado de unidades */
function obtenerFiltrosUnidadesDetalleProducto()
{
    return {
        busqueda: document.querySelector('[data-producto-filtro-busqueda]').value.trim(),
        usuario_asignado: document.querySelector('[data-producto-filtro-usuario]').value,
        fecha_compra: document.querySelector('[data-producto-filtro-fecha]').value,
        id_proveedor: document.querySelector('[data-producto-filtro-proveedor]').value,
        id_transportista: document.querySelector('[data-producto-filtro-transportista]').value,
    };
}

/* Carga los filtros del producto */
function renderizarFiltrosUnidadesDetalleProducto(opciones, filtros)
{
    const usuarios = opciones?.usuarios || [];
    const proveedores = opciones?.proveedores || [];
    const transportistas = opciones?.transportistas || [];
    const selectorUsuario = document.querySelector('[data-producto-filtro-usuario]');
    const selectorProveedor = document.querySelector('[data-producto-filtro-proveedor]');
    const selectorTransportista = document.querySelector('[data-producto-filtro-transportista]');
    selectorUsuario.innerHTML = '<option value="">Todos los usuarios</option><option value="sin_asignar">Sin asignar</option>' + usuarios.map((usuario) => `<option value="${Number(usuario.id_usuario)}">${escaparDetalleProducto(usuario.nombre)}</option>`).join('');
    selectorProveedor.innerHTML = '<option value="">Todos los proveedores</option>' + proveedores.map((proveedor) => `<option value="${Number(proveedor.id)}">${escaparDetalleProducto(proveedor.nombre)}</option>`).join('');
    selectorTransportista.innerHTML = '<option value="">Todos los transportistas</option>' + transportistas.map((transportista) => `<option value="${Number(transportista.id)}">${escaparDetalleProducto(transportista.nombre)}</option>`).join('');
    selectorUsuario.value = filtros.usuario_asignado || '';
    selectorProveedor.value = filtros.id_proveedor || '';
    selectorTransportista.value = filtros.id_transportista || '';
    document.querySelector('[data-producto-filtro-busqueda]').value = filtros.busqueda || '';
    document.querySelector('[data-producto-filtro-fecha]').value = filtros.fecha_compra || '';
}

/* Reinicia los filtros del listado de unidades y consulta la primera página */
function limpiarFiltrosUnidadesDetalleProducto()
{
    clearTimeout(temporizadorFiltrosUnidadesDetalle);
    document.querySelector('[data-producto-filtro-busqueda]').value = '';
    document.querySelector('[data-producto-filtro-usuario]').value = '';
    document.querySelector('[data-producto-filtro-fecha]').value = '';
    document.querySelector('[data-producto-filtro-proveedor]').value = '';
    document.querySelector('[data-producto-filtro-transportista]').value = '';
    cargarDetalleProducto(1);
}

/* Renderiza las unidades y todos los datos de su compra de origen */
function renderizarUnidadesDetalleProducto(unidades)
{
    const lista = document.querySelector('[data-producto-unidades]');
    if (!unidades.length) {
        lista.innerHTML = '<tr><td colspan="8">No hay unidades registradas para este producto.</td></tr>';
        return;
    }
    lista.innerHTML = unidades.map((unidad) => {
        const estado = estadoDetalleProducto(unidad.estado);
        return `<tr>
            <td>${escaparDetalleProducto(unidad.codigo_unidad)}</td>
            <td><button type="button" class="detalle-estado-boton" data-producto-cambiar-estado="${Number(unidad.id_unidad)}" data-producto-estado-actual="${Number(unidad.id_estado_producto)}" aria-label="Cambiar estado de la unidad ${escaparDetalleProducto(unidad.codigo_unidad)}" title="Cambiar estado"><span class="detalle-estado detalle-estado--${estado.clase}">${estado.texto}</span><i class="bi bi-pencil-square" aria-hidden="true"></i></button></td>
            <td><button type="button" class="detalle-asignacion-boton" data-producto-asignar-unidad="${Number(unidad.id_unidad)}" data-producto-usuario-asignado="${Number(unidad.id_usuario_asignado || 0)}" aria-label="Asignar usuario a la unidad ${escaparDetalleProducto(unidad.codigo_unidad)}" title="Asignar usuario"><span>${escaparDetalleProducto(unidad.asignado_a)}</span><i class="bi bi-person-gear" aria-hidden="true"></i></button></td>
            <td><span>${escaparDetalleProducto(unidad.codigo_compra)}</span><a class="detalle-compra-enlace" href="detalle_compra.php?id_compra=${encodeURIComponent(unidad.id_compra)}" target="_blank" rel="noopener noreferrer" aria-label="Abrir detalle de la compra ${escaparDetalleProducto(unidad.codigo_compra)}" title="Abrir detalle de la compra"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a></td>
            <td>${formatearFechaDetalleProducto(unidad.fecha_compra)}</td>
            <td>${formatearPrecioDetalleProducto(unidad.precio_unitario, unidad.moneda)}</td>
            <td>${escaparDetalleProducto(unidad.proveedor)}</td>
            <td>${escaparDetalleProducto(unidad.transportista)}</td>
        </tr>`;
    }).join('');
}

/* Muestra los usuarios activos disponibles para asignar la unidad seleccionada */
function cargarUsuariosAsignacionUnidad(idUnidad, usuarioActual)
{
    const lista = document.querySelector('[data-producto-usuarios-asignacion]');
    const buscador = document.querySelector('[data-producto-buscar-asignacion]');
    buscador.value = '';
    lista.innerHTML = '<p>Cargando usuarios disponibles...</p>';
    const parametros = new URLSearchParams({ modulo: 'inventario', accion: 'listar_usuarios_asignacion' });
    fetch(`../api/api.php?${parametros}`)
        .then((respuesta) => respuesta.json().then((resultado) => ({ respuesta, resultado })))
        .then(({ respuesta, resultado }) => {
            if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar los usuarios.');
            const usuarios = resultado.datos || [];
            if (!usuarios.length) {
                lista.innerHTML = '<p>No hay usuarios activos disponibles.</p>';
                return;
            }
            const opcionSinAsignar = `<label class="detalle-asignacion__opcion detalle-asignacion__opcion--sin-asignar" data-opcion-sin-asignacion="true"><input type="radio" name="usuario_asignacion" value="0" ${Number(usuarioActual) === 0 ? 'checked' : ''} required><span><strong>Sin asignar</strong><small>La unidad no quedara asignada a ningun usuario.</small></span></label>`;
            const opcionesUsuarios = usuarios.map((usuario) => `<label class="detalle-asignacion__opcion" data-nombre-usuario="${escaparDetalleProducto(usuario.nombre)}"><input type="radio" name="usuario_asignacion" value="${Number(usuario.id_usuario)}" ${Number(usuario.id_usuario) === Number(usuarioActual) ? 'checked' : ''} required><span><strong>${escaparDetalleProducto(usuario.nombre)}</strong><small>${escaparDetalleProducto(usuario.nombre_usuario)} · ${escaparDetalleProducto(usuario.rol_nombre)}</small></span></label>`).join('');
            lista.innerHTML = opcionSinAsignar + opcionesUsuarios + '<p class="detalle-asignacion__sin-resultados" data-asignacion-sin-resultados hidden>No hay usuarios que coincidan con la búsqueda.</p>';
            buscador.oninput = () => {
                const termino = buscador.value.trim().toLocaleLowerCase();
                const opciones = lista.querySelectorAll('[data-nombre-usuario]');
                let cantidadVisible = 0;
                opciones.forEach((opcion) => {
                    opcion.hidden = !opcion.dataset.nombreUsuario.toLocaleLowerCase().includes(termino);
                    if (!opcion.hidden) cantidadVisible += 1;
                });
                lista.querySelector('[data-opcion-sin-asignacion]').hidden = false;
                lista.querySelector('[data-asignacion-sin-resultados]').hidden = cantidadVisible > 0;
            };
        })
        .catch((error) => {
            lista.innerHTML = `<p class="detalle-producto-modal__mensaje--error">${escaparDetalleProducto(error.message)}</p>`;
        });
}

/* Abre el modal de asignación y carga sus usuarios activos */
function abrirModalAsignacionUnidad(idUnidad, codigoUnidad, usuarioActual)
{
    idUnidadAsignacionActual = Number(idUnidad);
    document.querySelector('[data-producto-unidad-asignacion]').textContent = `Selecciona el usuario para la unidad ${codigoUnidad}.`;
    document.querySelector('[data-producto-mensaje-asignacion]').textContent = '';
    document.querySelector('[data-producto-buscar-asignacion]').value = '';
    document.querySelector('[data-producto-modal-asignacion]').hidden = false;
    cargarUsuariosAsignacionUnidad(idUnidadAsignacionActual, usuarioActual);
}

/* Guarda el usuario seleccionado y actualiza la tabla de unidades */
async function guardarAsignacionUnidad(evento)
{
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const seleccionado = formulario.querySelector('input[name="usuario_asignacion"]:checked');
    const mensaje = document.querySelector('[data-producto-mensaje-asignacion]');
    const botonGuardar = formulario.querySelector('[type="submit"]');
    if (!seleccionado || !idUnidadAsignacionActual) {
        mensaje.textContent = 'Selecciona un usuario para continuar.';
        mensaje.classList.add('detalle-producto-modal__mensaje--error');
        return;
    }

    const datos = new FormData();
    datos.append('id_unidad', String(idUnidadAsignacionActual));
    datos.append('id_usuario', seleccionado.value);
    botonGuardar.disabled = true;
    mensaje.textContent = 'Guardando asignacion...';
    mensaje.classList.remove('detalle-producto-modal__mensaje--error');
    try {
        const respuesta = await fetch('../api/api.php?modulo=inventario&accion=asignar_unidad_usuario', { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible guardar la asignacion.');
        document.querySelector('[data-producto-modal-asignacion]').hidden = true;
        idUnidadAsignacionActual = 0;
        cargarDetalleProducto(paginaDetalleProducto);
    } catch (error) {
        mensaje.textContent = error.message;
        mensaje.classList.add('detalle-producto-modal__mensaje--error');
    } finally {
        botonGuardar.disabled = false;
    }
}

/* Carga los estados activos que se pueden aplicar a una unidad */
function cargarEstadosUnidad(estadoActual)
{
    const lista = document.querySelector('[data-producto-estados-unidad]');
    const buscador = document.querySelector('[data-producto-buscar-estado]');
    buscador.value = '';
    lista.innerHTML = '<p>Cargando estados disponibles...</p>';
    fetch('../api/api.php?modulo=inventario&accion=listar_estados_unidad')
        .then((respuesta) => respuesta.json().then((resultado) => ({ respuesta, resultado })))
        .then(({ respuesta, resultado }) => {
            if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar los estados.');
            const estados = resultado.datos || [];
            if (!estados.length) {
                lista.innerHTML = '<p>No hay estados disponibles.</p>';
                return;
            }
            const opciones = estados.map((estado) => {
                const textoBusqueda = `${estado.nombre || ''} ${estado.descripcion || ''}`.toLocaleLowerCase();
                return `<label class="detalle-estado__opcion" data-busqueda-estado="${escaparDetalleProducto(textoBusqueda)}"><input type="radio" name="estado_unidad" value="${Number(estado.id_estado_producto)}" ${Number(estado.id_estado_producto) === Number(estadoActual) ? 'checked' : ''} required><span><strong>${escaparDetalleProducto(estado.nombre)}</strong><small>${escaparDetalleProducto(estado.descripcion || 'Estado disponible para la unidad.')}</small></span></label>`;
            }).join('');
            lista.innerHTML = opciones + '<p class="detalle-estado__sin-resultados" data-estado-sin-resultados hidden>No hay estados que coincidan con la búsqueda.</p>';
            buscador.oninput = () => {
                const termino = buscador.value.trim().toLocaleLowerCase();
                const opcionesVisibles = lista.querySelectorAll('[data-busqueda-estado]');
                let cantidadVisible = 0;
                opcionesVisibles.forEach((opcion) => {
                    opcion.hidden = !opcion.dataset.busquedaEstado.includes(termino);
                    if (!opcion.hidden) cantidadVisible += 1;
                });
                lista.querySelector('[data-estado-sin-resultados]').hidden = cantidadVisible > 0;
            };
        })
        .catch((error) => {
            lista.innerHTML = `<p class="detalle-producto-modal__mensaje--error">${escaparDetalleProducto(error.message)}</p>`;
        });
}

/* Abre el modal de estado y carga las opciones actuales desde la API */
function abrirModalEstadoUnidad(idUnidad, codigoUnidad, estadoActual)
{
    idUnidadEstadoActual = Number(idUnidad);
    document.querySelector('[data-producto-unidad-estado]').textContent = `Selecciona el estado para la unidad ${codigoUnidad}.`;
    document.querySelector('[data-producto-mensaje-estado]').textContent = '';
    document.querySelector('[data-producto-modal-estado-unidad]').hidden = false;
    cargarEstadosUnidad(estadoActual);
}

/* Guarda el estado seleccionado y refresca la ficha del producto */
async function guardarEstadoUnidad(evento)
{
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const seleccionado = formulario.querySelector('input[name="estado_unidad"]:checked');
    const mensaje = document.querySelector('[data-producto-mensaje-estado]');
    const botonGuardar = formulario.querySelector('[type="submit"]');
    if (!seleccionado || !idUnidadEstadoActual) {
        mensaje.textContent = 'Selecciona un estado para continuar.';
        mensaje.classList.add('detalle-producto-modal__mensaje--error');
        return;
    }

    const datos = new FormData();
    datos.append('id_unidad', String(idUnidadEstadoActual));
    datos.append('id_estado_producto', seleccionado.value);
    botonGuardar.disabled = true;
    mensaje.textContent = 'Guardando estado...';
    mensaje.classList.remove('detalle-producto-modal__mensaje--error');
    try {
        const respuesta = await fetch('../api/api.php?modulo=inventario&accion=actualizar_estado_unidad', { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible actualizar el estado.');
        document.querySelector('[data-producto-modal-estado-unidad]').hidden = true;
        idUnidadEstadoActual = 0;
        cargarDetalleProducto(paginaDetalleProducto);
    } catch (error) {
        mensaje.textContent = error.message;
        mensaje.classList.add('detalle-producto-modal__mensaje--error');
    } finally {
        botonGuardar.disabled = false;
    }
}

/* Abre un modal de gestión de imágenes */
function abrirModalImagenProducto(selector)
{
    document.querySelector(selector).hidden = false;
}

/* Cierra un modal de gestión de imágenes */
function cerrarModalImagenProducto(selector)
{
    document.querySelector(selector).hidden = true;
}

/* Limpia el formulario y la previsualización antes de agregar otra imagen */
function limpiarFormularioImagenProducto()
{
    const formulario = document.querySelector('[data-producto-form-imagen]');
    const preview = document.querySelector('[data-producto-preview-imagen]');
    const vacio = document.querySelector('[data-producto-preview-vacio]');
    const botonGuardar = formulario?.querySelector('[type="submit"]');
    if (!formulario || !preview || !vacio) return;
    if (urlVistaPreviaImagenProducto) URL.revokeObjectURL(urlVistaPreviaImagenProducto);
    urlVistaPreviaImagenProducto = '';
    formulario.reset();
    preview.removeAttribute('src');
    preview.hidden = true;
    vacio.hidden = false;
    if (botonGuardar) botonGuardar.disabled = false;
    mostrarMensajeModalImagenProducto('[data-producto-mensaje-imagen]', '', false);
}

/* Muestra un mensaje de validación o resultado dentro de un modal */
function mostrarMensajeModalImagenProducto(selector, mensaje, error = true)
{
    const elemento = document.querySelector(selector);
    elemento.textContent = mensaje;
    elemento.classList.toggle('detalle-producto-modal__mensaje--error', error);
    elemento.classList.toggle('detalle-producto-modal__mensaje--exito', !error);
}

/* Envía una imagen nueva a la API y refresca la galería */
async function guardarImagenProducto(formulario)
{
    const idProducto = new URLSearchParams(window.location.search).get('id_producto');
    const archivo = formulario.querySelector('input[type="file"]').files[0];
    const botonGuardar = formulario.querySelector('[type="submit"]');
    if (!archivo || !idProducto) return;
    const datosFormulario = new FormData(formulario);
    datosFormulario.append('id_producto', idProducto);
    botonGuardar.disabled = true;
    mostrarMensajeModalImagenProducto('[data-producto-mensaje-imagen]', 'Guardando imagen...', false);
    try {
        const respuesta = await fetch('../api/api.php?modulo=inventario&accion=subir_imagen_producto', { method: 'POST', body: datosFormulario });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible guardar la imagen.');
        cerrarModalImagenProducto('[data-producto-modal-agregar]');
        limpiarFormularioImagenProducto();
        cargarDetalleProducto(paginaDetalleProducto);
    } catch (error) {
        mostrarMensajeModalImagenProducto('[data-producto-mensaje-imagen]', error.message, true);
    } finally {
        botonGuardar.disabled = false;
    }
}

/* Abre la confirmación para eliminar la imagen indicada */
function prepararEliminacionImagenProducto(idImagen)
{
    idImagenProductoAEliminar = Number(idImagen);
    mostrarMensajeModalImagenProducto('[data-producto-mensaje-eliminar]', '', true);
    abrirModalImagenProducto('[data-producto-modal-eliminar]');
}

/* Elimina una imagen y vuelve a ordenar la galería */
async function confirmarEliminacionImagenProducto()
{
    const idProducto = new URLSearchParams(window.location.search).get('id_producto');
    if (!idProducto || !idImagenProductoAEliminar) return;
    const boton = document.querySelector('[data-producto-confirmar-eliminar]');
    const datos = new FormData();
    datos.append('id_producto', idProducto);
    datos.append('id_imagen', String(idImagenProductoAEliminar));
    boton.disabled = true;
    mostrarMensajeModalImagenProducto('[data-producto-mensaje-eliminar]', 'Eliminando imagen...', false);
    try {
        const respuesta = await fetch('../api/api.php?modulo=inventario&accion=eliminar_imagen_producto', { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible eliminar la imagen.');
        cerrarModalImagenProducto('[data-producto-modal-eliminar]');
        idImagenProductoAEliminar = 0;
        cargarDetalleProducto(paginaDetalleProducto);
    } catch (error) {
        mostrarMensajeModalImagenProducto('[data-producto-mensaje-eliminar]', error.message, true);
    } finally {
        boton.disabled = false;
    }
}

/* Marca la imagen seleccionada como principal y la mueve al orden 1 */
async function marcarImagenPrincipalProducto(idImagen)
{
    const idProducto = new URLSearchParams(window.location.search).get('id_producto');
    if (!idProducto || !idImagen) return;
    const datos = new FormData();
    datos.append('id_producto', idProducto);
    datos.append('id_imagen', String(idImagen));
    try {
        const respuesta = await fetch('../api/api.php?modulo=inventario&accion=marcar_imagen_principal_producto', { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible marcar la imagen principal.');
        cargarDetalleProducto(paginaDetalleProducto);
    } catch (error) {
        window.alert(error.message);
    }
}

/* Inicializa el modal de carga, previsualización, eliminación y principalidad */
function inicializarGestionImagenesProducto()
{
    const modalAgregar = document.querySelector('[data-producto-modal-agregar]');
    const modalEliminar = document.querySelector('[data-producto-modal-eliminar]');
    const modalAsignacion = document.querySelector('[data-producto-modal-asignacion]');
    const modalEstado = document.querySelector('[data-producto-modal-estado-unidad]');
    const entradaImagen = document.querySelector('[data-producto-form-imagen] input[type="file"]');
    entradaImagen.addEventListener('change', () => {
        const archivo = entradaImagen.files[0];
        const preview = document.querySelector('[data-producto-preview-imagen]');
        const vacio = document.querySelector('[data-producto-preview-vacio]');
        if (urlVistaPreviaImagenProducto) URL.revokeObjectURL(urlVistaPreviaImagenProducto);
        urlVistaPreviaImagenProducto = '';
        if (!archivo) {
            preview.removeAttribute('src');
            preview.hidden = true;
            vacio.hidden = false;
            return;
        }
        urlVistaPreviaImagenProducto = URL.createObjectURL(archivo);
        preview.src = urlVistaPreviaImagenProducto;
        preview.hidden = false;
        vacio.hidden = true;
    });
    document.querySelector('[data-producto-agregar-imagen]').addEventListener('click', () => {
        limpiarFormularioImagenProducto();
        abrirModalImagenProducto('[data-producto-modal-agregar]');
    });
    document.querySelector('[data-producto-form-imagen]').addEventListener('submit', (evento) => {
        evento.preventDefault();
        guardarImagenProducto(evento.currentTarget);
    });
    document.querySelectorAll('[data-producto-cerrar-modal-agregar]').forEach((boton) => boton.addEventListener('click', () => cerrarModalImagenProducto('[data-producto-modal-agregar]')));
    document.querySelectorAll('[data-producto-cerrar-modal-eliminar]').forEach((boton) => boton.addEventListener('click', () => cerrarModalImagenProducto('[data-producto-modal-eliminar]')));
    document.querySelectorAll('[data-producto-cerrar-modal-asignacion]').forEach((boton) => boton.addEventListener('click', () => { modalAsignacion.hidden = true; idUnidadAsignacionActual = 0; }));
    document.querySelector('[data-producto-form-asignacion]').addEventListener('submit', guardarAsignacionUnidad);
    document.querySelectorAll('[data-producto-cerrar-modal-estado]').forEach((boton) => boton.addEventListener('click', () => { modalEstado.hidden = true; idUnidadEstadoActual = 0; }));
    document.querySelector('[data-producto-form-estado]').addEventListener('submit', guardarEstadoUnidad);
    document.querySelector('[data-producto-confirmar-eliminar]').addEventListener('click', confirmarEliminacionImagenProducto);
    [modalAgregar, modalEliminar, modalAsignacion, modalEstado].forEach((modal) => modal.addEventListener('click', (evento) => { if (evento.target === modal) modal.hidden = true; }));
    document.addEventListener('click', (evento) => {
        const botonEstado = evento.target.closest('[data-producto-cambiar-estado]');
        if (botonEstado) {
            abrirModalEstadoUnidad(botonEstado.dataset.productoCambiarEstado, botonEstado.closest('tr').querySelector('td:first-child').textContent.trim(), botonEstado.dataset.productoEstadoActual);
            return;
        }
        const botonAsignar = evento.target.closest('[data-producto-asignar-unidad]');
        if (botonAsignar) {
            abrirModalAsignacionUnidad(botonAsignar.dataset.productoAsignarUnidad, botonAsignar.closest('tr').querySelector('td:first-child').textContent.trim(), botonAsignar.dataset.productoUsuarioAsignado);
            return;
        }
        const botonEliminar = evento.target.closest('[data-producto-eliminar-imagen]');
        if (botonEliminar) {
            evento.preventDefault();
            evento.stopPropagation();
            prepararEliminacionImagenProducto(botonEliminar.dataset.productoEliminarImagen);
        }
    });
}

/* Crea la paginación del detalle del producto */
function renderizarPaginacionDetalleProducto(paginacion)
{
    const contenedor = document.querySelector('[data-producto-paginacion]');
    const totalPaginas = Number(paginacion.total_paginas || 1);
    const paginaActual = Number(paginacion.pagina_actual || 1);
    let html = `<button type="button" data-detalle-pagina="${Math.max(1, paginaActual - 1)}" ${paginaActual === 1 ? 'disabled' : ''} aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>`;
    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
        html += `<button type="button" class="${pagina === paginaActual ? 'detalle-pagina-activa' : ''}" data-detalle-pagina="${pagina}" ${pagina === paginaActual ? 'aria-current="page"' : ''}>${pagina}</button>`;
    }
    html += `<button type="button" data-detalle-pagina="${Math.min(totalPaginas, paginaActual + 1)}" ${paginaActual === totalPaginas ? 'disabled' : ''} aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = html;
    contenedor.querySelectorAll('[data-detalle-pagina]').forEach((boton) => boton.addEventListener('click', () => cargarDetalleProducto(Number(boton.dataset.detallePagina))));
}

/* Solicita al servidor la ficha del producto y la página de unidades */
async function cargarDetalleProducto(pagina = 1)
{
    const idProducto = new URLSearchParams(window.location.search).get('id_producto');
    if (!idProducto) return;
    paginaDetalleProducto = Math.max(1, pagina);
    const porPagina = Number(document.querySelector('[data-producto-pagina-tamano]').value) || 9;
    const filtros = obtenerFiltrosUnidadesDetalleProducto();
    const parametros = new URLSearchParams({ modulo: 'inventario', accion: 'obtener_detalle_producto', id_producto: idProducto, pagina: String(paginaDetalleProducto), por_pagina: String(porPagina), busqueda_unidades: filtros.busqueda, usuario_asignado: filtros.usuario_asignado, fecha_compra: filtros.fecha_compra, id_proveedor_unidad: filtros.id_proveedor, id_transportista_unidad: filtros.id_transportista });
    try {
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el detalle del producto.');
        const datos = resultado.datos;
        renderizarFichaDetalleProducto(datos);
        renderizarCalificacionesDetalleProducto(datos.calificaciones);
        renderizarFiltrosUnidadesDetalleProducto(datos.opciones_unidades || {}, filtros);
        renderizarUnidadesDetalleProducto(datos.unidades || []);
        renderizarPaginacionDetalleProducto(datos.paginacion || {});
        const paginacion = datos.paginacion || {};
        const inicio = datos.unidades?.length ? ((Number(paginacion.pagina_actual) - 1) * Number(paginacion.por_pagina)) + 1 : 0;
        const fin = inicio + (datos.unidades?.length || 0) - 1;
        document.querySelector('[data-producto-contador]').textContent = `Mostrando ${inicio} a ${Math.max(0, fin)} de ${Number(paginacion.total || 0)} unidades`;
    } catch (error) {
        document.querySelector('[data-producto-nombre]').textContent = error.message;
        document.querySelector('[data-producto-unidades]').innerHTML = `<tr><td colspan="8">${escaparDetalleProducto(error.message)}</td></tr>`;
    }
}

/* Inicializa la carga del detalle y el selector de unidades por página */
document.addEventListener('DOMContentLoaded', () => {
    inicializarGestionImagenesProducto();
    const buscadorUnidades = document.querySelector('[data-producto-filtro-busqueda]');
    buscadorUnidades.addEventListener('input', () => {
        clearTimeout(temporizadorFiltrosUnidadesDetalle);
        temporizadorFiltrosUnidadesDetalle = setTimeout(() => cargarDetalleProducto(1), 300);
    });
    document.querySelectorAll('[data-producto-filtro-usuario], [data-producto-filtro-fecha], [data-producto-filtro-proveedor], [data-producto-filtro-transportista]').forEach((control) => control.addEventListener('change', () => cargarDetalleProducto(1)));
    document.querySelector('[data-producto-limpiar-filtros]').addEventListener('click', limpiarFiltrosUnidadesDetalleProducto);
    document.querySelector('[data-producto-pagina-tamano]').addEventListener('change', () => cargarDetalleProducto(1));
    cargarDetalleProducto();
});
