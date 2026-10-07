/**
 * proveedores.js
 *
 * Carga y presenta proveedores, compras, montos y evaluaciones reales
 */

let busquedaProveedores = '';
let temporizadorProveedores;
let proveedorDetalleActual = null;
let cambiosProveedor = {};
let archivoImagenProveedor = null;
let imagenSeleccionadaProveedor = null;
let campoEdicionProveedor = '';

/* Evita que el texto se interprete como HTML */
function escaparProveedores(valor)
{
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[caracter]));
}

/* Formatea montos sin convertir monedas distintas */
function formatearMontoProveedores(valor, moneda = '')
{
    return `${escaparProveedores(moneda)} ${Number(valor || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 })}`.trim();
}

/* Formatea fechas SQL para mostrarlas en formato chileno */
function formatearFechaProveedores(fecha)
{
    if (!fecha) return 'Sin compras';
    const partes = String(fecha).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : escaparProveedores(fecha);
}

/* Genera una representación simple de estrellas para una calificación */
function estrellasProveedores(calificacion)
{
    if (calificacion === null || calificacion === undefined) return '<span class="proveedor-sin-dato">Sin evaluar</span>';
    const llenas = Math.max(0, Math.min(5, Math.round(Number(calificacion))));
    return `<span class="proveedor-estrellas" aria-label="Calificación ${Number(calificacion).toFixed(1)} de 5">${'★'.repeat(llenas)}${'☆'.repeat(5 - llenas)}</span> <strong>${Number(calificacion).toFixed(1)}</strong>`;
}

/* Genera la miniatura BLOB o la imagen comun de respaldo */
function miniaturaProveedor(proveedor)
{
    const iniciales = escaparProveedores(proveedor.nombre.slice(0, 2).toUpperCase());
    const rutaImagen = proveedor.imagen || obtenerRutaImagenSinRegistro('proveedor');
    return `<span class="entidad-miniatura"><img src="${escaparProveedores(rutaImagen)}" alt="Imagen de ${escaparProveedores(proveedor.nombre)}" data-imagen-respaldo data-iniciales="${iniciales}"></span>`;
}

/* Formatea la fecha de una evaluación para el historial de comentarios */
function formatearFechaComentarioProveedor(fecha)
{
    if (!fecha) return 'Fecha no disponible';
    const partes = String(fecha).split(' ');
    return `${formatearFechaProveedores(partes[0])}${partes[1] ? ` ${partes[1].slice(0, 5)}` : ''}`;
}

/* Renderiza los últimos comentarios registrados para un proveedor */
function renderizarComentariosProveedor(comentarios)
{
    if (!comentarios || !comentarios.length) {
        return '<p class="modal-detalle__sin-datos">No hay comentarios registrados.</p>';
    }

    return `<div class="modal-detalle__comentarios">${comentarios.map((comentario) => `
        <article class="modal-detalle__comentario">
            <div class="modal-detalle__comentario-encabezado">
                <strong>${escaparProveedores(comentario.usuario)}</strong>
                <span>${estrellasProveedores(comentario.calificacion)}</span>
            </div>
            <p>${escaparProveedores(comentario.comentario)}</p>
            <small>${escaparProveedores(formatearFechaComentarioProveedor(comentario.fecha))} · ${escaparProveedores(comentario.compra)}</small>
        </article>`).join('')}</div>`;
}

/* Actualiza los indicadores superiores */
function renderizarResumenProveedores(resumen)
{
    document.querySelector('[data-proveedores-total]').textContent = resumen.proveedores;
    document.querySelector('[data-proveedores-compras]').textContent = resumen.compras;
    document.querySelector('[data-proveedores-articulos]').textContent = Number(resumen.articulos || 0).toLocaleString('es-CL');
    document.querySelector('[data-proveedores-calificacion]').textContent = resumen.calificacion_promedio === null ? 'Sin datos' : `${Number(resumen.calificacion_promedio).toFixed(1)}/5`;
    document.querySelector('[data-proveedores-montos]').textContent = resumen.montos.length ? resumen.montos.map((monto) => formatearMontoProveedores(monto.total, monto.moneda)).join(' · ') : 'Sin datos';
}

/* Muestra los proveedores en la tabla */
function renderizarProveedores(proveedores, paginacion)
{
    const lista = document.querySelector('[data-proveedores-lista]');
    const contador = document.querySelector('[data-proveedores-contador]');

    if (!proveedores.length) {
        lista.innerHTML = '<tr><td colspan="7">No hay proveedores que coincidan con la búsqueda.</td></tr>';
    } else {
        lista.innerHTML = proveedores.map((proveedor) => `
            <tr>
                <td>${miniaturaProveedor(proveedor)}</td>
                <td><div class="proveedor-identidad"><strong>${escaparProveedores(proveedor.nombre)}</strong></div></td>
                <td>${proveedor.compras}</td>
                <td>${formatearMontoProveedores(proveedor.monto_total, proveedor.moneda)}</td>
                <td>${proveedor.articulos}</td>
                <td>${formatearFechaProveedores(proveedor.ultima_compra)}</td>
                <td><span class="proveedor-calificacion">${estrellasProveedores(proveedor.calificacion)}</span></td>
                <td><button class="entidad-ver" type="button" data-ver-proveedor="${proveedor.id_proveedor}" aria-label="Ver detalle de ${escaparProveedores(proveedor.nombre)}"><i class="bi bi-eye" aria-hidden="true"></i></button></td>
            </tr>`).join('');
    }

    configurarBotonesDetalleProveedores();
    const inicio = proveedores.length ? ((paginacion.pagina_actual - 1) * paginacion.por_pagina) + 1 : 0;
    const fin = inicio + proveedores.length - 1;
    contador.textContent = `Mostrando ${inicio} a ${Math.max(0, fin)} de ${paginacion.total} proveedores`;
    renderizarPaginacionProveedores(paginacion);
}

/* Muestra el ranking de proveedores por calificación */
function renderizarRankingProveedores(proveedores, selector)
{
    const contenedor = document.querySelector(selector);
    if (!proveedores.length) {
        contenedor.innerHTML = '<p class="ranking-vacio">No hay evaluaciones registradas.</p>';
        return;
    }

    contenedor.innerHTML = proveedores.map((proveedor, indice) => `
        <div class="proveedores-ranking__fila">
            <span><b>${indice + 1}</b>${escaparProveedores(proveedor.nombre)}</span>
            <span>${proveedor.compras}</span>
            <span>${formatearMontoProveedores(proveedor.monto_total, proveedor.moneda)}</span>
            <span class="proveedor-calificacion">${estrellasProveedores(proveedor.calificacion)}</span>
        </div>`).join('');
}

/* Actualiza los paneles de mejores y peores proveedores */
function renderizarRankingsProveedores(rankings)
{
    renderizarRankingProveedores(rankings.mejores || [], '[data-proveedores-ranking-mejores]');
    renderizarRankingProveedores(rankings.peores || [], '[data-proveedores-ranking-peores]');
}

/* Crea la paginación de los proveedores */
function renderizarPaginacionProveedores(paginacion)
{
    const contenedor = document.querySelector('[data-proveedores-paginacion]');
    let html = `<button type="button" data-proveedores-pagina="${Math.max(1, paginacion.pagina_actual - 1)}" ${paginacion.pagina_actual === 1 ? 'disabled' : ''} aria-label="Pagina anterior"><i class="bi bi-chevron-left"></i></button>`;
    for (let pagina = 1; pagina <= paginacion.total_paginas; pagina += 1) {
        html += `<button type="button" class="${pagina === paginacion.pagina_actual ? 'proveedores-pagina-activa' : ''}" data-proveedores-pagina="${pagina}">${pagina}</button>`;
    }
    html += `<button type="button" data-proveedores-pagina="${Math.min(paginacion.total_paginas, paginacion.pagina_actual + 1)}" ${paginacion.pagina_actual === paginacion.total_paginas ? 'disabled' : ''} aria-label="Pagina siguiente"><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = html;
    contenedor.querySelectorAll('[data-proveedores-pagina]').forEach((boton) => boton.addEventListener('click', () => cargarProveedores(Number(boton.dataset.proveedoresPagina))));
}

/* Solicita los proveedores a la API */
async function cargarProveedores(pagina = 1)
{
    const lista = document.querySelector('[data-proveedores-lista]');
    const parametros = new URLSearchParams({ modulo: 'proveedores', accion: 'listar_proveedores', pagina: String(pagina), busqueda: busquedaProveedores });

    try {
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar los proveedores.');
        renderizarResumenProveedores(resultado.datos.resumen);
        renderizarProveedores(resultado.datos.proveedores, resultado.datos.paginacion);
        renderizarRankingsProveedores(resultado.datos.rankings || {});
    } catch (error) {
        lista.innerHTML = `<tr><td colspan="8">${escaparProveedores(error.message)}</td></tr>`;
    }
}

/* Renderiza la información completa del proveedor dentro del modal */
function renderizarDetalleProveedor(proveedor)
{
    const modal = document.querySelector('[data-detalle-modal]');
    const imagen = document.querySelector('[data-detalle-imagen]');
    const fallback = document.querySelector('[data-detalle-imagen-fallback]');
    const principales = document.querySelector('[data-detalle-principales]');
    const informacion = document.querySelector('[data-detalle-informacion]');

    document.querySelector('[data-detalle-tipo]').textContent = 'Proveedor';
    document.querySelector('[data-detalle-titulo]').textContent = proveedor.nombre;
    document.querySelector('[data-detalle-principales-titulo]').textContent = 'Datos del proveedor';
    document.querySelector('[data-detalle-calificacion]').innerHTML = estrellasProveedores(proveedor.calificacion);
    imagen.src = proveedor.imagen || obtenerRutaImagenSinRegistro('proveedor');
    imagen.alt = `Imagen de ${proveedor.nombre}`;
    imagen.dataset.imagenRespaldo = 'true';
    imagen.dataset.iniciales = proveedor.nombre.slice(0, 2).toUpperCase();
    imagen.hidden = false;
    fallback.hidden = true;
    const botonEditarImagen = document.querySelector('[data-editar-entidad-campo="imagen"]');
    botonEditarImagen.hidden = false;

    principales.innerHTML = `
        <div class="modal-detalle__campo-con-accion">
            <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                <div class="modal-detalle__dato-icono"><i class="bi bi-building" aria-hidden="true"></i></div>
                <div><dt>Razon social</dt><dd>${escaparProveedores(proveedor.razon_social)}</dd></div>
            </dl>
            ${botonEditarProveedor('razon_social', 'razon social')}
        </div>
        <div class="modal-detalle__campo-con-accion">
            <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                <div class="modal-detalle__dato-icono"><i class="bi bi-person-vcard" aria-hidden="true"></i></div>
                <div><dt>RUT</dt><dd>${escaparProveedores(proveedor.rut)}</dd></div>
            </dl>
            ${botonEditarProveedor('rut', 'RUT')}
        </div>`;

    informacion.innerHTML = `
        <section class="modal-detalle__grupo">
            <h4 class="modal-detalle__subtitulo">Datos de contacto</h4>
            <div class="modal-detalle__grupo-lista">
                <div class="modal-detalle__campo-con-accion">
                    <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                        <div class="modal-detalle__dato-icono"><i class="bi bi-geo-alt" aria-hidden="true"></i></div>
                <div><dt>Dirección</dt><dd>${escaparProveedores(proveedor.direccion)}</dd></div>
                    </dl>
                    ${botonEditarProveedor('direccion', 'dirección')}
                </div>
                <div class="modal-detalle__campo-con-accion">
                    <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                        <div class="modal-detalle__dato-icono"><i class="bi bi-envelope" aria-hidden="true"></i></div>
                        <div><dt>Correo electrónico</dt><dd>${escaparProveedores(proveedor.correo_electronico)}</dd></div>
                    </dl>
                    ${botonEditarProveedor('correo_electronico', 'correo electrónico')}
                </div>
                <div class="modal-detalle__campo-con-accion">
                    <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                        <div class="modal-detalle__dato-icono"><i class="bi bi-telephone" aria-hidden="true"></i></div>
                        <div><dt>Teléfono</dt><dd>${escaparProveedores(proveedor.telefono)}</dd></div>
                    </dl>
                    ${botonEditarProveedor('telefono', 'teléfono')}
                </div>
            </div>
        </section>
        <section class="modal-detalle__grupo">
            <h4 class="modal-detalle__subtitulo">Resumen de compras</h4>
            <div class="modal-detalle__grupo-lista">
                <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                    <div class="modal-detalle__dato-icono"><i class="bi bi-cart3" aria-hidden="true"></i></div>
                    <div><dt>Compras asociadas</dt><dd>${proveedor.compras}</dd></div>
                </dl>
                <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                    <div class="modal-detalle__dato-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <div><dt>Articulos adquiridos</dt><dd>${Number(proveedor.articulos || 0).toLocaleString('es-CL')}</dd></div>
                </dl>
                <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                    <div class="modal-detalle__dato-icono"><i class="bi bi-coin" aria-hidden="true"></i></div>
                    <div><dt>Monto total</dt><dd>${formatearMontoProveedores(proveedor.monto_total, proveedor.moneda)}</dd></div>
                </dl>
            </div>
        </section>
        <section class="modal-detalle__grupo modal-detalle__grupo--comentarios">
            <h4 class="modal-detalle__subtitulo">Últimos comentarios</h4>
            ${renderizarComentariosProveedor(proveedor.comentarios)}
        </section>`;
    configurarBotonesEdicionProveedor();
    modal.hidden = false;
}

/* Devuelve el valor editable sin textos de respaldo de la interfaz */
function obtenerValorEditableProveedor(campo)
{
    if (Object.prototype.hasOwnProperty.call(cambiosProveedor, campo)) {
        return cambiosProveedor[campo];
    }

    const valor = String(proveedorDetalleActual?.[campo] ?? '');
    return valor.startsWith('Sin ') ? '' : valor;
}

/* Crea el botón para editar un campo del proveedor */
function botonEditarProveedor(campo, etiqueta)
{
    return `<button class="modal-detalle__editar" type="button" data-editar-entidad-campo="${campo}" aria-label="Editar ${etiqueta}"><i class="bi bi-pencil" aria-hidden="true"></i></button>`;
}

/* Abre el editor temporal para un campo del proveedor */
function abrirEditorProveedor(campo)
{
    const modal = document.querySelector('[data-edicion-transportista-modal]');
    const titulo = document.querySelector('[data-edicion-entidad-titulo]');
    const contenido = document.querySelector('[data-edicion-entidad-contenido]');
    campoEdicionProveedor = campo;
    imagenSeleccionadaProveedor = null;

    const configuracion = {
        razon_social: { titulo: 'Editar razon social', etiqueta: 'Razon social', tipo: 'text', ayuda: 'Ingresa la razon social del proveedor.' },
        rut: { titulo: 'Editar RUT', etiqueta: 'RUT', tipo: 'text', ayuda: 'Ingresa el RUT del proveedor.' },
        direccion: { titulo: 'Editar dirección', etiqueta: 'Dirección', tipo: 'text', ayuda: 'Ingresa la dirección del proveedor.' },
        correo_electronico: { titulo: 'Editar correo electrónico', etiqueta: 'Correo electrónico', tipo: 'email', ayuda: 'Ingresa el correo electrónico del proveedor.' },
        telefono: { titulo: 'Editar teléfono', etiqueta: 'Teléfono', tipo: 'text', ayuda: 'Ingresa el teléfono del proveedor.' },
    };

    if (campo === 'imagen') {
        titulo.textContent = 'Editar imagen';
        contenido.innerHTML = `
            <label class="transportista-edicion__etiqueta" for="proveedorImagenNueva">Imagen del proveedor</label>
            <input class="transportista-edicion__archivo" id="proveedorImagenNueva" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="transportista-edicion__ayuda">Formatos permitidos: JPG, PNG, WEBP o GIF. Máximo 5 MB.</small>
            <div class="transportista-edicion__preview-contenedor">
                <img class="transportista-edicion__preview" data-edicion-entidad-preview src="${escaparProveedores(proveedorDetalleActual.imagen || obtenerRutaImagenSinRegistro('proveedor'))}" alt="Vista previa de la imagen">
            </div>`;
        contenido.querySelector('input[type="file"]').addEventListener('change', prepararPreviewImagenProveedor);
    } else {
        const datos = configuracion[campo];
        titulo.textContent = datos.titulo;
        const valor = escaparProveedores(obtenerValorEditableProveedor(campo));
        contenido.innerHTML = `<label class="transportista-edicion__etiqueta" for="proveedorCampoEdicion">${datos.etiqueta}</label><input class="transportista-edicion__entrada" id="proveedorCampoEdicion" type="${datos.tipo}" value="${valor}"><small class="transportista-edicion__ayuda">${datos.ayuda}</small>`;
    }

    modal.hidden = false;
}

/* Prepara la vista previa de una nueva imagen del proveedor */
function prepararPreviewImagenProveedor(evento)
{
    const archivo = evento.target.files[0];
    if (!archivo) return;

    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!tiposPermitidos.includes(archivo.type) || archivo.size > 5 * 1024 * 1024) {
        window.alert('Selecciona una imagen JPG, PNG, WEBP o GIF de hasta 5 MB.');
        evento.target.value = '';
        return;
    }

    const lector = new FileReader();
    lector.onload = function () {
        imagenSeleccionadaProveedor = { archivo, dataUri: lector.result };
        document.querySelector('[data-edicion-entidad-preview]').src = lector.result;
    };
    lector.readAsDataURL(archivo);
}

/* Acepta un cambio temporal y actualiza inmediatamente el modal de detalle */
function aceptarEdicionProveedor()
{
    if (campoEdicionProveedor === 'imagen') {
        if (!imagenSeleccionadaProveedor && !archivoImagenProveedor) {
            window.alert('Selecciona una imagen antes de aceptar el cambio.');
            return;
        }
        if (imagenSeleccionadaProveedor) {
            archivoImagenProveedor = imagenSeleccionadaProveedor.archivo;
            proveedorDetalleActual.imagen = imagenSeleccionadaProveedor.dataUri;
        }
    } else {
        const entrada = document.querySelector('#proveedorCampoEdicion');
        const valor = entrada.value.trim();
        cambiosProveedor[campoEdicionProveedor] = valor;
        proveedorDetalleActual[campoEdicionProveedor] = valor || `Sin ${campoEdicionProveedor === 'razon_social' ? 'razón social' : campoEdicionProveedor === 'rut' ? 'RUT' : campoEdicionProveedor === 'direccion' ? 'dirección' : campoEdicionProveedor === 'correo_electronico' ? 'correo' : 'teléfono'} registrado`;
    }

    document.querySelector('[data-edicion-transportista-modal]').hidden = true;
    renderizarDetalleProveedor(proveedorDetalleActual);
}

/* Conecta los lapices del detalle del proveedor con el editor temporal */
function configurarBotonesEdicionProveedor()
{
    document.querySelectorAll('[data-editar-entidad-campo]').forEach((boton) => {
        boton.onclick = () => abrirEditorProveedor(boton.dataset.editarEntidadCampo);
    });
}

/* Guarda en la base de datos los cambios confirmados del proveedor */
async function guardarCambiosProveedor()
{
    const confirmacion = document.querySelector('[data-confirmacion-transportista-modal]');
    const botonConfirmar = document.querySelector('[data-confirmacion-entidad-aceptar]');
    const datos = new FormData();
    datos.append('id_proveedor', String(proveedorDetalleActual.id_proveedor));
    Object.entries(cambiosProveedor).forEach(([campo, valor]) => datos.append(campo, valor));
    if (archivoImagenProveedor) datos.append('imagen', archivoImagenProveedor);
    confirmacion.hidden = true;
    botonConfirmar.disabled = true;

    try {
        const parametros = new URLSearchParams({ modulo: 'proveedores', accion: 'actualizar_proveedor' });
        const respuesta = await fetch(`../api/api.php?${parametros}`, { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible guardar los cambios.');
        const modalDetalle = document.querySelector('[data-detalle-modal]');
        if (modalDetalle) modalDetalle.hidden = true;
        cargarProveedores();
    } catch (error) {
        window.alert(error.message);
    } finally {
        botonConfirmar.disabled = false;
    }
}

/* Inicializa los modales de edición y confirmación del proveedor */
function inicializarEdicionProveedor()
{
    const modalEdicion = document.querySelector('[data-edicion-transportista-modal]');
    const modalConfirmacion = document.querySelector('[data-confirmacion-transportista-modal]');
    if (!modalEdicion || !modalConfirmacion) return;

    document.querySelector('[data-confirmacion-entidad-mensaje]').textContent = '¿Estás seguro de que deseas guardar los cambios del proveedor?';
    document.querySelector('[data-detalle-guardar]').addEventListener('click', () => {
        if (!proveedorDetalleActual || (!Object.keys(cambiosProveedor).length && !archivoImagenProveedor)) {
            window.alert('No hay cambios para guardar.');
            return;
        }
        modalConfirmacion.hidden = false;
    });
    document.querySelector('[data-edicion-entidad-cancelar]').addEventListener('click', () => { modalEdicion.hidden = true; });
    document.querySelector('[data-edicion-entidad-aceptar]').addEventListener('click', aceptarEdicionProveedor);
    document.querySelector('[data-confirmacion-entidad-cancelar]').addEventListener('click', () => { modalConfirmacion.hidden = true; });
    document.querySelector('[data-confirmacion-entidad-aceptar]').addEventListener('click', guardarCambiosProveedor);
    modalEdicion.addEventListener('click', (evento) => { if (evento.target === modalEdicion) modalEdicion.hidden = true; });
    modalConfirmacion.addEventListener('click', (evento) => { if (evento.target === modalConfirmacion) modalConfirmacion.hidden = true; });
}

/* Consulta y abre el detalle de un proveedor */
async function abrirDetalleProveedor(idProveedor)
{
    try {
        const parametros = new URLSearchParams({ modulo: 'proveedores', accion: 'obtener_proveedor', id_proveedor: String(idProveedor) });
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el detalle.');
        proveedorDetalleActual = resultado.datos;
        cambiosProveedor = {};
        archivoImagenProveedor = null;
        imagenSeleccionadaProveedor = null;
        renderizarDetalleProveedor(resultado.datos);
    } catch (error) {
        window.alert(error.message);
    }
}

/* Conecta los botones de ver con el detalle correspondiente */
function configurarBotonesDetalleProveedores()
{
    document.querySelectorAll('[data-ver-proveedor]').forEach((boton) => {
        boton.addEventListener('click', () => abrirDetalleProveedor(Number(boton.dataset.verProveedor)));
    });
}

/* Inicializa búsqueda y carga inicial */
document.addEventListener('DOMContentLoaded', () => {
    inicializarEdicionProveedor();
    document.querySelector('[data-proveedores-busqueda]').addEventListener('input', (evento) => {
        clearTimeout(temporizadorProveedores);
        temporizadorProveedores = setTimeout(() => { busquedaProveedores = evento.target.value.trim(); cargarProveedores(1); }, 300);
    });
    cargarProveedores();
});
