/**
 * metodo_despacho.js
 *
 * Carga y presenta transportistas, estados de compra, costos y evaluaciones.
 */

let busquedaTransportistas = '';
let temporizadorTransportistas;
let transportistaDetalleActual = null;
let cambiosTransportista = {};
let archivoImagenTransportista = null;
let imagenSeleccionadaTransportista = null;
let campoEdicionTransportista = '';

/* Evita que el texto se interprete como HTML */
function escaparTransportistas(valor)
{
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[caracter]));
}

/* Devuelve el valor editable sin textos de respaldo de la interfaz */
function obtenerValorEditableTransportista(campo)
{
    if (Object.prototype.hasOwnProperty.call(cambiosTransportista, campo)) {
        return cambiosTransportista[campo];
    }

    const valor = String(transportistaDetalleActual?.[campo] ?? '');
    return valor.startsWith('Sin ') ? '' : valor;
}

/* Crea el botón reutilizable para editar un campo del transportista */
function botonEditarTransportista(campo, etiqueta)
{
    return `<button class="modal-detalle__editar" type="button" data-editar-entidad-campo="${campo}" aria-label="Editar ${etiqueta}"><i class="bi bi-pencil" aria-hidden="true"></i></button>`;
}

/* Formatea montos de envío conservando la moneda registrada en la compra */
function formatearCostoTransportistas(valor, moneda = '')
{
    return `${escaparTransportistas(moneda)} ${Number(valor || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 })}`.trim();
}

/* Formatea una fecha SQL para la tabla */
function formatearFechaTransportistas(fecha)
{
    if (!fecha) return 'Sin compras';
    const partes = String(fecha).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : escaparTransportistas(fecha);
}

/* Traduce estados internos de compra a texto visible */
function textoEstadoTransportista(estado)
{
    const estados = { registrada: 'Registrada', confirmada: 'Confirmada', en_preparacion: 'En preparacion', en_transito: 'En transito', recibida: 'Recibida', con_problemas: 'Con problemas', atrasada: 'Atrasada', cancelada: 'Cancelada' };
    return estados[estado] || estado;
}

/* Elige el color del estado según la etapa real de la compra */
function claseEstadoTransportista(estado)
{
    const clases = { registrada: 'despacho-estado--bueno', confirmada: 'despacho-estado--bueno', en_preparacion: 'despacho-estado--regular', en_transito: 'despacho-estado--regular', recibida: 'despacho-estado--excelente', con_problemas: 'despacho-estado--regular', atrasada: 'despacho-estado--regular', cancelada: 'despacho-estado--regular' };
    return clases[estado] || 'despacho-estado--regular';
}

/* Genera estrellas para una calificación */
function estrellasTransportistas(calificacion)
{
    if (calificacion === null || calificacion === undefined) return '<span class="despacho-sin-dato">Sin evaluar</span>';
    const llenas = Math.max(0, Math.min(5, Math.round(Number(calificacion))));
    return `<span class="despacho-estrellas" aria-label="Calificación ${Number(calificacion).toFixed(1)} de 5">${'★'.repeat(llenas)}${'☆'.repeat(5 - llenas)}</span> <strong>${Number(calificacion).toFixed(1)}</strong>`;
}

/* Genera la miniatura BLOB o la imagen comun de respaldo */
function miniaturaTransportista(transportista)
{
    const iniciales = escaparTransportistas(transportista.nombre.slice(0, 2).toUpperCase());
    const rutaImagen = transportista.imagen || obtenerRutaImagenSinRegistro('transportista');
    return `<span class="entidad-miniatura"><img src="${escaparTransportistas(rutaImagen)}" alt="Imagen de ${escaparTransportistas(transportista.nombre)}" data-imagen-respaldo data-iniciales="${iniciales}"></span>`;
}

/* Formatea la fecha de una evaluación para el historial de comentarios */
function formatearFechaComentarioTransportista(fecha)
{
    if (!fecha) return 'Fecha no disponible';
    const partes = String(fecha).split(' ');
    return `${formatearFechaTransportistas(partes[0])}${partes[1] ? ` ${partes[1].slice(0, 5)}` : ''}`;
}

/* Renderiza los últimos comentarios registrados para un transportista */
function renderizarComentariosTransportista(comentarios)
{
    if (!comentarios || !comentarios.length) {
        return '<p class="modal-detalle__sin-datos">No hay comentarios registrados.</p>';
    }

    return `<div class="modal-detalle__comentarios">${comentarios.map((comentario) => `
        <article class="modal-detalle__comentario">
            <div class="modal-detalle__comentario-encabezado">
                <strong>${escaparTransportistas(comentario.usuario)}</strong>
                <span>${estrellasTransportistas(comentario.calificacion)}</span>
            </div>
            <p>${escaparTransportistas(comentario.comentario)}</p>
            <small>${escaparTransportistas(formatearFechaComentarioTransportista(comentario.fecha))} · ${escaparTransportistas(comentario.compra)}</small>
        </article>`).join('')}</div>`;
}

/* Actualiza los indicadores superiores */
function renderizarResumenTransportistas(resumen)
{
    document.querySelector('[data-transportistas-total]').textContent = resumen.transportistas;
    document.querySelector('[data-transportistas-compras]').textContent = resumen.compras;
    document.querySelector('[data-transportistas-costos]').textContent = resumen.costos.length
        ? resumen.costos.map((costo) => formatearCostoTransportistas(costo.total, costo.moneda)).join(' · ')
        : 'Sin datos';
    document.querySelector('[data-transportistas-calificacion]').textContent = resumen.calificacion_promedio === null ? 'Sin datos' : `${Number(resumen.calificacion_promedio).toFixed(1)}/5`;
}

/* Muestra los transportistas en la tabla */
function renderizarTransportistas(transportistas, paginacion)
{
    const lista = document.querySelector('[data-transportistas-lista]');
    const contador = document.querySelector('[data-transportistas-contador]');

    if (!transportistas.length) {
        lista.innerHTML = '<tr><td colspan="7">No hay transportistas que coincidan con la búsqueda.</td></tr>';
    } else {
        lista.innerHTML = transportistas.map((transportista) => `
            <tr>
                <td>${miniaturaTransportista(transportista)}</td>
                <td><div class="despacho-identidad"><strong>${escaparTransportistas(transportista.nombre)}</strong></div></td>
                <td>${transportista.compras}</td>
                <td>${formatearCostoTransportistas(transportista.costo_envios, transportista.moneda)}</td>
                <td>${formatearFechaTransportistas(transportista.ultima_compra)}</td>
                <td><span class="despacho-estado ${claseEstadoTransportista(transportista.estado_reciente)}">${escaparTransportistas(textoEstadoTransportista(transportista.estado_reciente))}</span></td>
                <td><span class="despacho-calificacion">${estrellasTransportistas(transportista.calificacion)}</span></td>
                <td><button class="entidad-ver" type="button" data-ver-transportista="${transportista.id_transportista}" aria-label="Ver detalle de ${escaparTransportistas(transportista.nombre)}"><i class="bi bi-eye" aria-hidden="true"></i></button></td>
            </tr>`).join('');
    }

    configurarBotonesDetalleTransportistas();
    const inicio = transportistas.length ? ((paginacion.pagina_actual - 1) * paginacion.por_pagina) + 1 : 0;
    const fin = inicio + transportistas.length - 1;
    contador.textContent = `Mostrando ${inicio} a ${Math.max(0, fin)} de ${paginacion.total} transportistas`;
    renderizarPaginacionTransportistas(paginacion);
}

/* Muestra el ranking de transportistas por calificación */
function renderizarRankingTransportistas(transportistas, selector)
{
    const contenedor = document.querySelector(selector);
    if (!transportistas.length) {
        contenedor.innerHTML = '<li class="ranking-vacio">No hay evaluaciones registradas.</li>';
        return;
    }

    contenedor.innerHTML = transportistas.map((transportista, indice) => `
        <li>
            <b>${indice + 1}</b>
            <div class="despachos-ranking__metodo">
                <span>${escaparTransportistas(transportista.nombre)}</span>
                <progress max="5" value="${Number(transportista.calificacion)}" aria-label="Calificación ${Number(transportista.calificacion).toFixed(1)} de 5"></progress>
            </div>
            <strong>${Number(transportista.calificacion).toFixed(1)} <i class="bi bi-star-fill" aria-hidden="true"></i></strong>
        </li>`).join('');
}

/* Actualiza los paneles de mejores y peores transportistas */
function renderizarRankingsTransportistas(rankings)
{
    renderizarRankingTransportistas(rankings.mejores || [], '[data-transportistas-ranking-mejores]');
    renderizarRankingTransportistas(rankings.peores || [], '[data-transportistas-ranking-peores]');
}

/* Crea la paginación de los transportistas */
function renderizarPaginacionTransportistas(paginacion)
{
    const contenedor = document.querySelector('[data-transportistas-paginacion]');
    let html = `<button type="button" data-transportistas-pagina="${Math.max(1, paginacion.pagina_actual - 1)}" ${paginacion.pagina_actual === 1 ? 'disabled' : ''} aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>`;
    for (let pagina = 1; pagina <= paginacion.total_paginas; pagina += 1) {
        html += `<button type="button" class="${pagina === paginacion.pagina_actual ? 'despachos-pagina-activa' : ''}" data-transportistas-pagina="${pagina}">${pagina}</button>`;
    }
    html += `<button type="button" data-transportistas-pagina="${Math.min(paginacion.total_paginas, paginacion.pagina_actual + 1)}" ${paginacion.pagina_actual === paginacion.total_paginas ? 'disabled' : ''} aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = html;
    contenedor.querySelectorAll('[data-transportistas-pagina]').forEach((boton) => boton.addEventListener('click', () => cargarTransportistas(Number(boton.dataset.transportistasPagina))));
}

/* Solicita los transportistas a la API */
async function cargarTransportistas(pagina = 1)
{
    const lista = document.querySelector('[data-transportistas-lista]');
    const parametros = new URLSearchParams({ modulo: 'transportistas', accion: 'listar_transportistas', pagina: String(pagina), busqueda: busquedaTransportistas });

    try {
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar los transportistas.');
        renderizarResumenTransportistas(resultado.datos.resumen);
        renderizarTransportistas(resultado.datos.transportistas, resultado.datos.paginacion);
        renderizarRankingsTransportistas(resultado.datos.rankings || {});
    } catch (error) {
        lista.innerHTML = `<tr><td colspan="8">${escaparTransportistas(error.message)}</td></tr>`;
    }
}

/* Renderiza la información completa del transportista dentro del modal */
function renderizarDetalleTransportista(transportista)
{
    const modal = document.querySelector('[data-detalle-modal]');
    const imagen = document.querySelector('[data-detalle-imagen]');
    const fallback = document.querySelector('[data-detalle-imagen-fallback]');
    const principales = document.querySelector('[data-detalle-principales]');
    const informacion = document.querySelector('[data-detalle-informacion]');
    const botonEditarImagen = document.querySelector('[data-editar-entidad-campo="imagen"]');

    document.querySelector('[data-detalle-tipo]').textContent = 'Transportista';
    document.querySelector('[data-detalle-titulo]').textContent = transportista.nombre;
    document.querySelector('[data-detalle-principales-titulo]').textContent = 'Datos del transportista';
    document.querySelector('[data-detalle-calificacion]').innerHTML = estrellasTransportistas(transportista.calificacion);
    imagen.src = transportista.imagen || obtenerRutaImagenSinRegistro('transportista');
    imagen.alt = `Imagen de ${transportista.nombre}`;
    imagen.dataset.imagenRespaldo = 'true';
    imagen.dataset.iniciales = transportista.nombre.slice(0, 2).toUpperCase();
    imagen.hidden = false;
    fallback.hidden = true;
    botonEditarImagen.hidden = false;

    principales.innerHTML = `
        <div class="modal-detalle__campo-con-accion">
            <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                <div class="modal-detalle__dato-icono"><i class="bi bi-envelope" aria-hidden="true"></i></div>
                <div><dt>Correo electrónico</dt><dd>${escaparTransportistas(transportista.correo_electronico)}</dd></div>
            </dl>
            ${botonEditarTransportista('correo_electronico', 'correo electrónico')}
        </div>
        <div class="modal-detalle__campo-con-accion">
            <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                <div class="modal-detalle__dato-icono"><i class="bi bi-telephone" aria-hidden="true"></i></div>
                <div><dt>Teléfono</dt><dd>${escaparTransportistas(transportista.telefono)}</dd></div>
            </dl>
            ${botonEditarTransportista('telefono', 'teléfono')}
        </div>`;

    informacion.innerHTML = `
        <section class="modal-detalle__grupo">
            <h4 class="modal-detalle__subtitulo">Información del transportista</h4>
            <div class="modal-detalle__grupo-lista">
                <div class="modal-detalle__campo-con-accion">
                    <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                        <div class="modal-detalle__dato-icono"><i class="bi bi-truck" aria-hidden="true"></i></div>
                        <div><dt>Descripción</dt><dd>${escaparTransportistas(transportista.descripcion)}</dd></div>
                    </dl>
                    ${botonEditarTransportista('descripcion', 'descripción')}
                </div>
            </div>
        </section>
        <section class="modal-detalle__grupo">
            <h4 class="modal-detalle__subtitulo">Resumen de despachos</h4>
            <div class="modal-detalle__grupo-lista">
                <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                    <div class="modal-detalle__dato-icono"><i class="bi bi-cart3" aria-hidden="true"></i></div>
                    <div><dt>Compras asociadas</dt><dd>${transportista.compras}</dd></div>
                </dl>
                <dl class="modal-detalle__dato modal-detalle__dato--tarjeta">
                    <div class="modal-detalle__dato-icono"><i class="bi bi-currency-dollar" aria-hidden="true"></i></div>
                    <div><dt>Costos de envíos</dt><dd>${formatearCostoTransportistas(transportista.costo_envios, transportista.moneda)}</dd></div>
                </dl>
            </div>
        </section>
        <section class="modal-detalle__grupo modal-detalle__grupo--comentarios">
            <h4 class="modal-detalle__subtitulo">Últimos comentarios</h4>
            ${renderizarComentariosTransportista(transportista.comentarios)}
        </section>`;
    configurarBotonesEdicionTransportista();
    modal.hidden = false;
}

/* Abre el editor temporal para un campo del transportista */
function abrirEditorTransportista(campo)
{
    const modal = document.querySelector('[data-edicion-transportista-modal]');
    const titulo = document.querySelector('[data-edicion-entidad-titulo]');
    const contenido = document.querySelector('[data-edicion-entidad-contenido]');
    campoEdicionTransportista = campo;
    imagenSeleccionadaTransportista = null;

    const configuracion = {
        correo_electronico: { titulo: 'Editar correo electrónico', etiqueta: 'Correo electrónico', tipo: 'email', ayuda: 'Ingresa el correo electrónico del transportista.' },
        telefono: { titulo: 'Editar teléfono', etiqueta: 'Teléfono', tipo: 'text', ayuda: 'Ingresa el teléfono del transportista.' },
        descripcion: { titulo: 'Editar descripción', etiqueta: 'Descripción', tipo: 'textarea', ayuda: 'Ingresa una descripción breve del transportista.' },
    };

    if (campo === 'imagen') {
        titulo.textContent = 'Editar imagen';
        contenido.innerHTML = `
            <label class="transportista-edicion__etiqueta" for="transportistaImagenNueva">Imagen del transportista</label>
            <input class="transportista-edicion__archivo" id="transportistaImagenNueva" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="transportista-edicion__ayuda">Formatos permitidos: JPG, PNG, WEBP o GIF. Máximo 5 MB.</small>
            <div class="transportista-edicion__preview-contenedor">
                <img class="transportista-edicion__preview" data-edicion-entidad-preview src="${escaparTransportistas(transportistaDetalleActual.imagen || obtenerRutaImagenSinRegistro('transportista'))}" alt="Vista previa de la imagen">
            </div>`;
        const entradaImagen = contenido.querySelector('input[type="file"]');
        entradaImagen.addEventListener('change', prepararPreviewImagenTransportista);
    } else {
        const datos = configuracion[campo];
        titulo.textContent = datos.titulo;
        const valor = escaparTransportistas(obtenerValorEditableTransportista(campo));
        contenido.innerHTML = datos.tipo === 'textarea'
            ? `<label class="transportista-edicion__etiqueta" for="transportistaCampoEdicion">${datos.etiqueta}</label><textarea class="transportista-edicion__entrada" id="transportistaCampoEdicion" rows="4">${valor}</textarea><small class="transportista-edicion__ayuda">${datos.ayuda}</small>`
            : `<label class="transportista-edicion__etiqueta" for="transportistaCampoEdicion">${datos.etiqueta}</label><input class="transportista-edicion__entrada" id="transportistaCampoEdicion" type="${datos.tipo}" value="${valor}"><small class="transportista-edicion__ayuda">${datos.ayuda}</small>`;
    }

    modal.hidden = false;
}

/* Prepara la vista previa de una nueva imagen sin guardarla aún en la base de datos */
function prepararPreviewImagenTransportista(evento)
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
        imagenSeleccionadaTransportista = { archivo, dataUri: lector.result };
        document.querySelector('[data-edicion-entidad-preview]').src = lector.result;
    };
    lector.readAsDataURL(archivo);
}

/* Acepta un cambio temporal y actualiza inmediatamente el modal de detalle */
function aceptarEdicionTransportista()
{
    if (campoEdicionTransportista === 'imagen') {
        if (!imagenSeleccionadaTransportista && !archivoImagenTransportista) {
            window.alert('Selecciona una imagen antes de aceptar el cambio.');
            return;
        }
        if (imagenSeleccionadaTransportista) {
            archivoImagenTransportista = imagenSeleccionadaTransportista.archivo;
            transportistaDetalleActual.imagen = imagenSeleccionadaTransportista.dataUri;
        }
    } else {
        const entrada = document.querySelector('#transportistaCampoEdicion');
        const valor = entrada.value.trim();
        cambiosTransportista[campoEdicionTransportista] = valor;
        transportistaDetalleActual[campoEdicionTransportista] = valor || (campoEdicionTransportista === 'descripcion' ? 'Sin descripción registrada' : `Sin ${campoEdicionTransportista === 'correo_electronico' ? 'correo' : 'teléfono'} registrado`);
    }

    document.querySelector('[data-edicion-transportista-modal]').hidden = true;
    renderizarDetalleTransportista(transportistaDetalleActual);
}

/* Conecta los lapices del detalle con el editor temporal */
function configurarBotonesEdicionTransportista()
{
    document.querySelectorAll('[data-editar-entidad-campo]').forEach((boton) => {
        boton.onclick = () => abrirEditorTransportista(boton.dataset.editarEntidadCampo);
    });
}

/* Guarda en la base de datos los cambios previamente confirmados por el usuario */
async function guardarCambiosTransportista()
{
    const confirmacion = document.querySelector('[data-confirmacion-transportista-modal]');
    const botonConfirmar = document.querySelector('[data-confirmacion-entidad-aceptar]');
    const datos = new FormData();
    datos.append('id_transportista', String(transportistaDetalleActual.id_transportista));
    Object.entries(cambiosTransportista).forEach(([campo, valor]) => datos.append(campo, valor));
    if (archivoImagenTransportista) datos.append('imagen', archivoImagenTransportista);
    confirmacion.hidden = true;
    botonConfirmar.disabled = true;

    try {
        const parametros = new URLSearchParams({ modulo: 'transportistas', accion: 'actualizar_transportista' });
        const respuesta = await fetch(`../api/api.php?${parametros}`, { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible guardar los cambios.');
        const modalDetalle = document.querySelector('[data-detalle-modal]');
        if (modalDetalle) {
            modalDetalle.hidden = true;
        }
        cargarTransportistas();
    } catch (error) {
        window.alert(error.message);
    } finally {
        botonConfirmar.disabled = false;
    }
}

/* Inicializa los modales de edición y confirmación del transportista */
function inicializarEdicionTransportista()
{
    const modalEdicion = document.querySelector('[data-edicion-transportista-modal]');
    const modalConfirmacion = document.querySelector('[data-confirmacion-transportista-modal]');
    if (!modalEdicion || !modalConfirmacion) return;

    document.querySelector('[data-confirmacion-entidad-mensaje]').textContent = '¿Estás seguro de que deseas guardar los cambios del transportista?';
    document.querySelector('[data-detalle-guardar]').addEventListener('click', () => {
        if (!transportistaDetalleActual || (!Object.keys(cambiosTransportista).length && !archivoImagenTransportista)) {
            window.alert('No hay cambios para guardar.');
            return;
        }
        modalConfirmacion.hidden = false;
    });
    document.querySelector('[data-edicion-entidad-cancelar]').addEventListener('click', () => { modalEdicion.hidden = true; });
    document.querySelector('[data-edicion-entidad-aceptar]').addEventListener('click', aceptarEdicionTransportista);
    document.querySelector('[data-confirmacion-entidad-cancelar]').addEventListener('click', () => { modalConfirmacion.hidden = true; });
    document.querySelector('[data-confirmacion-entidad-aceptar]').addEventListener('click', guardarCambiosTransportista);

    modalEdicion.addEventListener('click', (evento) => { if (evento.target === modalEdicion) modalEdicion.hidden = true; });
    modalConfirmacion.addEventListener('click', (evento) => { if (evento.target === modalConfirmacion) modalConfirmacion.hidden = true; });
}

/* Consulta y abre el detalle de un transportista */
async function abrirDetalleTransportista(idTransportista)
{
    try {
        const parametros = new URLSearchParams({ modulo: 'transportistas', accion: 'obtener_transportista', id_transportista: String(idTransportista) });
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el detalle.');
        transportistaDetalleActual = resultado.datos;
        cambiosTransportista = {};
        archivoImagenTransportista = null;
        imagenSeleccionadaTransportista = null;
        renderizarDetalleTransportista(resultado.datos);
    } catch (error) {
        window.alert(error.message);
    }
}

/* Conecta los botones de ver con el detalle correspondiente */
function configurarBotonesDetalleTransportistas()
{
    document.querySelectorAll('[data-ver-transportista]').forEach((boton) => {
        boton.addEventListener('click', () => abrirDetalleTransportista(Number(boton.dataset.verTransportista)));
    });
}

/* Inicializa búsqueda y carga inicial */
document.addEventListener('DOMContentLoaded', () => {
    inicializarEdicionTransportista();
    document.querySelector('[data-transportistas-busqueda]').addEventListener('input', (evento) => {
        clearTimeout(temporizadorTransportistas);
        temporizadorTransportistas = setTimeout(() => { busquedaTransportistas = evento.target.value.trim(); cargarTransportistas(1); }, 300);
    });
    cargarTransportistas();
});
