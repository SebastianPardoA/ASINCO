/**
 * detalle_compra.js
 *
 * Consulta y renderiza el detalle real de una compra seleccionada desde el historial
 */

let documentosCompraActuales = [];
let comentariosCompraActuales = [];

/* Evita que el texto se interprete como HTML */
function escaparDetalleCompra(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* Muestra la fecha en formato local */
function formatearFechaDetalle(fecha, vacio = 'No registrada') {
    if (!fecha) {
        return vacio;
    }

    const fechaLocal = new Date(`${fecha.substring(0, 10)}T00:00:00`);
    return Number.isNaN(fechaLocal.getTime())
        ? escaparDetalleCompra(fecha)
        : fechaLocal.toLocaleDateString('es-CL');
}

/* Formatea montos sin agregar conversiones entre CLP y USD */
function formatearMontoDetalle(monto, moneda) {
    const codigoMoneda = moneda || '';
    const valor = Number(monto || 0);
    return `${escaparDetalleCompra(codigoMoneda)} ${new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: codigoMoneda === 'CLP' ? 0 : 2,
        maximumFractionDigits: codigoMoneda === 'CLP' ? 0 : 2,
    }).format(valor)}`;
}

/* Ajusta el estado para usarlo como clase CSS */
function normalizarEstadoDetalle(estado) {
    return String(estado || 'sin estado')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-');
}

/* Devuelve una etiqueta visual para un estado de compra */
function crearEtiquetaDetalle(estado) {
    const estadoTexto = estado || 'Sin estado';
    const clase = normalizarEstadoDetalle(estadoTexto);
    return `<span class="etiqueta etiqueta--estado-${clase}">${escaparDetalleCompra(estadoTexto)}</span>`;
}

/* Construye el control que permite abrir la edición del estado de la compra */
function crearEstadoEditableDetalle(compra) {
    return `<button type="button" class="detalle-estado__boton" data-cambiar-estado aria-label="Cambiar estado de la compra" title="Cambiar estado">${crearEtiquetaDetalle(compra.estado)}<i class="bi bi-pencil" aria-hidden="true"></i></button>`;
}

/* Construye la etiqueta de estado del encabezado manteniendo la acción de edición */
function crearEstadoEncabezadoDetalle(compra) {
    const clase = normalizarEstadoDetalle(compra.estado);
    return `<button type="button" class="etiqueta etiqueta--estado-${clase} detalle-estado__encabezado" data-detalle-compra-estado data-cambiar-estado aria-label="Cambiar estado de la compra" title="Cambiar estado">${escaparDetalleCompra(compra.estado || 'Sin estado')}</button>`;
}

/* Genera una miniatura para un proveedor o transportista usando la imagen por defecto si corresponde */
function crearMiniaturaEntidadDetalle(entidad, tipo) {
    const nombre = entidad?.nombre || tipo;
    const iniciales = nombre.split(/\s+/).filter(Boolean).slice(0, 2).map((parte) => parte[0]).join('').toUpperCase();
    const rutaImagen = entidad?.imagen || obtenerRutaImagenSinRegistro(tipo);
    return `<span class="detalle-dato__miniatura"><img src="${escaparDetalleCompra(rutaImagen)}" alt="Imagen de ${escaparDetalleCompra(nombre)}" data-imagen-respaldo data-iniciales="${escaparDetalleCompra(iniciales)}"></span>`;
}

/* Genera el avatar con iniciales del usuario responsable de la compra */
function crearAvatarResponsableDetalle(nombre) {
    const iniciales = String(nombre || '').split(/\s+/).filter(Boolean).slice(0, 2).map((parte) => parte[0]).join('').toUpperCase() || '?';
    return `<span class="detalle-dato__avatar" aria-hidden="true">${escaparDetalleCompra(iniciales)}</span>`;
}

/* Construye un dato del resumen superior */
function crearDatoResumenDetalle(icono, titulo, contenido, extra = '', visual = '') {
    const indicador = visual || `<i class="bi ${icono}" aria-hidden="true"></i>`;
    return `<div class="detalle-dato">${indicador}<div><strong>${escaparDetalleCompra(titulo)}</strong>${contenido}${extra}</div></div>`;
}

/* Renderiza el resumen general de la compra */
function renderizarResumenDetalle(datos) {
    const { compra, proveedor, transportista, responsable, totales } = datos;
    const resumen = document.querySelector('[data-detalle-resumen]');
    if (!resumen) {
        return;
    }

    resumen.innerHTML = `
        <div class="detalle-resumen__columna">
            ${crearDatoResumenDetalle('bi-person-badge', 'Proveedor', `<span>${escaparDetalleCompra(proveedor.razon_social || proveedor.nombre)}</span>`, '', crearMiniaturaEntidadDetalle(proveedor, 'proveedor'))}
            ${crearDatoResumenDetalle('bi-truck', 'Transportista', `<span>${escaparDetalleCompra(transportista?.nombre || 'Sin transportista')}</span>`, '', transportista ? crearMiniaturaEntidadDetalle(transportista, 'transportista') : '')}
            ${crearDatoResumenDetalle('bi-person', 'Responsable de la compra', `<span>${escaparDetalleCompra(responsable.nombre)}</span><b>${escaparDetalleCompra(responsable.rol)}</b>`, '', crearAvatarResponsableDetalle(responsable.nombre))}
        </div>
        <div class="detalle-resumen__columna">
            ${crearDatoResumenDetalle('bi-calendar3', 'Fecha de compra', `<span>${formatearFechaDetalle(compra.fecha_compra)}</span>`)}
            ${crearDatoResumenDetalle('bi-tag', 'Número de pedido', `<span>${escaparDetalleCompra(compra.numero_pedido || compra.codigo_compra || 'No registrado')}</span>`)}
            ${crearDatoResumenDetalle('bi-info-circle', 'Estado', crearEstadoEditableDetalle(compra))}
        </div>
        <div class="detalle-resumen__columna">
            ${crearDatoResumenDetalle('bi-currency-dollar', `Total (${compra.moneda})`, `<span class="detalle-valor">${formatearMontoDetalle(totales.total, totales.moneda)}</span><strong>Productos</strong><span>${formatearMontoDetalle(totales.subtotal_productos, totales.moneda)}</span><strong>Envío</strong><span>${formatearMontoDetalle(totales.costo_envio, totales.moneda)}</span>`)}
        </div>
        <div class="detalle-resumen__observaciones">
            <strong>Observaciones</strong>
            <p>${escaparDetalleCompra(compra.observaciones || 'Sin observaciones registradas.')}</p>
        </div>`;
}

/* Renderiza la tabla de productos y sus totales */
function renderizarArticulosDetalle(datos) {
    const cuerpo = document.querySelector('[data-detalle-articulos]');
    const pie = document.querySelector('[data-detalle-articulos-total]');
    const encabezadoPrecio = document.querySelector('[data-detalle-columna-precio]');
    const encabezadoSubtotal = document.querySelector('[data-detalle-columna-subtotal]');
    if (!cuerpo || !pie) {
        return;
    }

    if (encabezadoPrecio) {
        encabezadoPrecio.textContent = `Precio unitario (${datos.totales.moneda})`;
    }
    if (encabezadoSubtotal) {
        encabezadoSubtotal.textContent = `Subtotal (${datos.totales.moneda})`;
    }

    if (!datos.articulos.length) {
        cuerpo.innerHTML = '<tr><td colspan="6" class="detalle-vacio">No hay articulos asociados.</td></tr>';
    } else {
        cuerpo.innerHTML = datos.articulos.map((articulo, indice) => `
            <tr>
                <td>${indice + 1}</td>
                <td>${escaparDetalleCompra(articulo.producto)}</td>
                <td>${escaparDetalleCompra(articulo.codigo || 'Sin código')}</td>
                <td>${articulo.cantidad}</td>
                <td>${formatearMontoDetalle(articulo.precio_unitario, datos.totales.moneda)}</td>
                <td>${formatearMontoDetalle(articulo.subtotal, datos.totales.moneda)}</td>
            </tr>`).join('');
    }

    pie.innerHTML = `<tr><td colspan="3">Total</td><td>${datos.totales.unidades}</td><td></td><td>${formatearMontoDetalle(datos.totales.subtotal_productos, datos.totales.moneda)}</td></tr>`;
}

/* Renderiza los comentarios asociados a la compra */
function renderizarComentariosDetalle(comentarios) {
    const contenedor = document.querySelector('[data-detalle-comentarios]');
    if (!contenedor) {
        return;
    }

    comentariosCompraActuales = comentarios || [];
    if (!comentarios.length) {
        contenedor.innerHTML = '<p class="detalle-vacio">No hay comentarios registrados para esta compra.</p>';
        return;
    }

    contenedor.innerHTML = comentarios.map((comentario) => {
        const iniciales = comentario.usuario.split(/\s+/).filter(Boolean).slice(0, 2).map((parte) => parte[0]).join('').toUpperCase();
        const botonEditar = comentario.puede_editar
            ? `<button type="button" class="detalle-comentario__editar" data-editar-comentario="${comentario.id_comentario}" aria-label="Editar comentario" title="Editar comentario"><i class="bi bi-pencil" aria-hidden="true"></i></button>`
            : '';
        return `<div class="detalle-comentario"><span class="detalle-comentario__avatar">${escaparDetalleCompra(iniciales)}</span><p><strong>${escaparDetalleCompra(comentario.usuario)} <small>${formatearFechaDetalle(comentario.fecha)}</small></strong>${escaparDetalleCompra(comentario.comentario)}</p>${botonEditar}</div>`;
    }).join('');
}

/* Restablece el formulario de comentarios al modo de publicación */
function reiniciarFormularioComentarioDetalle() {
    const formulario = document.querySelector('[data-formulario-comentario]');
    if (!formulario) {
        return;
    }

    formulario.reset();
    formulario.querySelector('[data-comentario-id]').value = '';
    formulario.querySelector('[data-comentario-guardar]').textContent = 'Publicar';
    formulario.querySelector('[data-comentario-cancelar]').hidden = true;
    const mensaje = document.querySelector('[data-comentario-mensaje]');
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.className = 'detalle-comentarios__mensaje';
    }
}

/* Carga un comentario propio en el formulario para que pueda editarse */
function prepararEdicionComentarioDetalle(comentario) {
    const formulario = document.querySelector('[data-formulario-comentario]');
    if (!formulario || !comentario) {
        return;
    }

    formulario.querySelector('[data-comentario-id]').value = comentario.id_comentario;
    formulario.querySelector('[data-comentario-texto]').value = comentario.comentario;
    formulario.querySelector('[data-comentario-guardar]').textContent = 'Guardar cambios';
    formulario.querySelector('[data-comentario-cancelar]').hidden = false;
    document.querySelector('[data-comentario-mensaje]').textContent = '';
    formulario.querySelector('[data-comentario-texto]').focus();
}

/* Renderiza solo el estado actual de recepción dentro del panel combinado */
function renderizarRecepcionDetalle(datos) {
    const contenedor = document.querySelector('[data-detalle-recepcion]');
    if (!contenedor) {
        return;
    }

    const estadoNormalizado = normalizarEstadoDetalle(datos.compra.estado);
    const recibida = estadoNormalizado === 'recibida';
    const conProblemas = estadoNormalizado.includes('problema');
    const titulo = recibida ? 'Recepcion completada' : (conProblemas ? 'Recepcion con novedades' : 'Recepcion pendiente');
    const mensaje = recibida
        ? 'Todos los articulos fueron recibidos correctamente.'
        : (datos.compra.estado_descripcion || 'La compra aún no registra una recepción completa.');
    const icono = recibida ? 'check-lg' : (conProblemas ? 'exclamation-lg' : 'clock');
    const colorClase = recibida ? 'detalle-recepcion--completa' : 'detalle-recepcion--pendiente';

    contenedor.innerHTML = `
        <div class="detalle-recepcion__estado ${colorClase}"><span><i class="bi bi-${icono}" aria-hidden="true"></i></span><div><strong>${escaparDetalleCompra(titulo)}</strong><small>${escaparDetalleCompra(mensaje)}</small></div></div>`;
}

/* Renderiza las calificaciones y habilita su edición solo después de recibir la compra */
function renderizarCalificacionesDetalle(datos) {
    const contenedor = document.querySelector('[data-detalle-calificaciones]');
    if (!contenedor) {
        return;
    }

    const compraRecibida = normalizarEstadoDetalle(datos.compra.estado) === 'recibida';
    const calificaciones = [
        { tipo: 'proveedor', titulo: 'Calificación del proveedor', entidad: datos.proveedor, evaluacion: datos.calificaciones.proveedor },
        { tipo: 'transportista', titulo: 'Calificación del transportista', entidad: datos.transportista, evaluacion: datos.calificaciones.transportista },
    ];

    contenedor.innerHTML = calificaciones.map(({ tipo, titulo, entidad, evaluacion }) => {
        const puedeCalificar = compraRecibida && Boolean(entidad);
        const estrellasCompletas = evaluacion ? Math.round(Number(evaluacion.promedio)) : 0;
        const estrellas = evaluacion
            ? '★'.repeat(estrellasCompletas) + '☆'.repeat(Math.max(0, 5 - estrellasCompletas))
            : '☆☆☆☆☆';
        const comentario = evaluacion?.comentario || (entidad ? 'Sin comentario registrado.' : 'No hay transportista asociado.');
        const textoBoton = evaluacion ? 'Editar calificación' : 'Calificar';
        const estadoBoton = puedeCalificar ? '' : 'disabled';
        const claseDeshabilitada = puedeCalificar ? '' : ' detalle-calificacion--deshabilitada';
        return `<div class="detalle-calificacion${claseDeshabilitada}"><div class="detalle-calificacion__titulo"><div><strong>${escaparDetalleCompra(titulo)}</strong><small class="detalle-calificacion__entidad">${escaparDetalleCompra(entidad?.nombre || 'Sin entidad asociada')}</small></div><span>${evaluacion ? `${evaluacion.cantidad} evaluación(es)` : 'Pendiente'}</span></div><p><span>${estrellas}</span>${evaluacion ? `<b>${Number(evaluacion.promedio).toFixed(1)}</b>` : ''}</p><div class="detalle-calificacion__comentario"><strong>Comentario registrado:</strong><span>${escaparDetalleCompra(comentario)}</span></div><button type="button" class="detalle-calificacion__boton" data-calificar-compra="${tipo}" ${estadoBoton}>${puedeCalificar ? `<i class="bi bi-star" aria-hidden="true"></i>${textoBoton}` : 'Disponible al recibir'}</button></div>`;
    }).join('');
}

/* Abre el formulario para seleccionar y guardar un nuevo estado de compra */
function abrirModalEstadoDetalle(datos) {
    document.querySelector('[data-modal-estado]')?.remove();
    const estados = datos.estados_compra || [];
    const opciones = estados.map((estado) => {
        const nombre = String(estado.nombre || '').replace(/_/g, ' ');
        const etiqueta = nombre ? nombre.charAt(0).toUpperCase() + nombre.slice(1) : 'Sin estado';
        return `<label class="detalle-estado-radio-opcion"><input type="radio" name="estado_modal" value="${escaparDetalleCompra(estado.id)}" ${Number(estado.id) === Number(datos.compra.id_estado_compra) ? 'checked' : ''}><span>${escaparDetalleCompra(etiqueta)}</span></label>`;
    }).join('');
    const estadoActual = estados.find((estado) => Number(estado.id) === Number(datos.compra.id_estado_compra));
    const nombreEstadoActual = String(estadoActual?.nombre || datos.compra.estado || 'Sin estado').replace(/_/g, ' ');
    const etiquetaEstadoActual = nombreEstadoActual ? nombreEstadoActual.charAt(0).toUpperCase() + nombreEstadoActual.slice(1) : 'Sin estado';
    const modal = document.createElement('div');
    modal.className = 'detalle-calificacion-modal';
    modal.dataset.modalEstado = 'true';
    modal.innerHTML = `
        <div class="detalle-calificacion-modal__contenido detalle-estado-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="detalleEstadoTitulo">
            <div class="detalle-calificacion-modal__encabezado"><h2 id="detalleEstadoTitulo">Cambiar estado</h2><button type="button" data-cerrar-estado aria-label="Cerrar">&times;</button></div>
            <p>Selecciona el nuevo estado para la compra <strong>${escaparDetalleCompra(datos.compra.codigo_compra)}</strong>.</p>
            <form data-formulario-estado>
                <fieldset class="detalle-estado-modal__campo">
                    <legend>Estado de la compra</legend>
                    <div class="detalle-estado-selector" data-estado-selector>
                        <button class="detalle-estado-selector__boton" type="button" data-estado-selector-boton aria-expanded="false" aria-haspopup="listbox">
                            <span class="detalle-estado-selector__radio detalle-estado-selector__radio--seleccionado" aria-hidden="true"></span>
                            <span data-estado-label>${escaparDetalleCompra(etiquetaEstadoActual)}</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div class="detalle-estado-selector__panel" data-estado-selector-panel hidden>
                            <input class="detalle-estado-radio-busqueda" type="search" data-estado-busqueda placeholder="Buscar estado" autocomplete="off">
                            <div class="detalle-estado-radio-opciones" data-estado-opciones role="listbox">${opciones}</div>
                        </div>
                    </div>
                    <input type="hidden" name="id_estado_compra" data-estado-valor value="${escaparDetalleCompra(datos.compra.id_estado_compra)}" required>
                </fieldset>
                <p class="detalle-calificacion-modal__mensaje" data-mensaje-estado></p>
                <div class="detalle-calificacion-modal__acciones"><button type="button" class="detalle-calificacion-modal__cancelar" data-cerrar-estado>Cancelar</button><button type="submit" class="detalle-calificacion-modal__guardar">Guardar</button></div>
            </form>
        </div>`;
    document.body.appendChild(modal);
    modal.querySelectorAll('[data-cerrar-estado]').forEach((boton) => boton.addEventListener('click', () => modal.remove()));
    const botonSelector = modal.querySelector('[data-estado-selector-boton]');
    const panelSelector = modal.querySelector('[data-estado-selector-panel]');
    const busquedaEstado = modal.querySelector('[data-estado-busqueda]');
    const valorEstado = modal.querySelector('[data-estado-valor]');
    const etiquetaEstado = modal.querySelector('[data-estado-label]');
    const indicadorEstado = modal.querySelector('.detalle-estado-selector__radio');
    botonSelector.addEventListener('click', () => {
        const abrir = panelSelector.hidden;
        panelSelector.hidden = !abrir;
        botonSelector.setAttribute('aria-expanded', String(abrir));
        if (abrir) {
            busquedaEstado.focus();
        }
    });
    busquedaEstado.addEventListener('input', () => {
        const termino = busquedaEstado.value.trim().toLowerCase();
        modal.querySelectorAll('[data-estado-opciones] .detalle-estado-radio-opcion').forEach((opcion) => {
            opcion.hidden = !opcion.textContent.toLowerCase().includes(termino);
        });
    });
    modal.querySelectorAll('[data-estado-opciones] input[name="estado_modal"]').forEach((opcion) => opcion.addEventListener('change', () => {
        valorEstado.value = opcion.value;
        etiquetaEstado.textContent = opcion.parentElement.textContent.trim();
        indicadorEstado.classList.toggle('detalle-estado-selector__radio--seleccionado', Boolean(opcion.value));
        panelSelector.hidden = true;
        botonSelector.setAttribute('aria-expanded', 'false');
    }));
    modal.addEventListener('click', (evento) => {
        if (evento.target === modal) {
            modal.remove();
        } else if (!evento.target.closest('[data-estado-selector]')) {
            panelSelector.hidden = true;
            botonSelector.setAttribute('aria-expanded', 'false');
        }
    });
    modal.querySelector('[data-formulario-estado]').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const mensaje = modal.querySelector('[data-mensaje-estado]');
        const botonGuardar = modal.querySelector('.detalle-calificacion-modal__guardar');
        const datosFormulario = new FormData(evento.currentTarget);
        datosFormulario.append('id_compra', datos.compra.id_compra);
        botonGuardar.disabled = true;
        mensaje.textContent = 'Guardando...';
        try {
            const respuesta = await fetch('../api/api.php?modulo=historial_compras&accion=actualizar_estado_compra', { method: 'POST', body: datosFormulario });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.exito) {
                throw new Error(resultado.mensaje || 'No fue posible actualizar el estado.');
            }
            modal.remove();
            await cargarDetalleCompra();
        } catch (error) {
            botonGuardar.disabled = false;
            mensaje.textContent = error.message;
        }
    });
}

/* Abre el formulario de calificación para el proveedor o transportista elegido. */
function abrirModalCalificacionDetalle(datos, tipo) {
    const entidad = tipo === 'proveedor' ? datos.proveedor : datos.transportista;
    const evaluacion = datos.calificaciones[tipo];
    if (!entidad || normalizarEstadoDetalle(datos.compra.estado) !== 'recibida') {
        return;
    }

    document.querySelector('[data-modal-calificacion]')?.remove();
    const calificacionInicial = evaluacion ? Math.round(Number(evaluacion.calificacion || evaluacion.promedio)) : 0;
    const modal = document.createElement('div');
    modal.className = 'detalle-calificacion-modal';
    modal.dataset.modalCalificacion = 'true';
    modal.innerHTML = `
        <div class="detalle-calificacion-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="detalleCalificacionTitulo">
            <div class="detalle-calificacion-modal__encabezado"><h2 id="detalleCalificacionTitulo">${evaluacion ? 'Editar calificación' : 'Agregar calificación'}</h2><button type="button" data-cerrar-calificacion aria-label="Cerrar">&times;</button></div>
            <p>Califica a ${tipo === 'proveedor' ? 'el proveedor' : 'el transportista'} <strong>${escaparDetalleCompra(entidad.nombre)}</strong>.</p>
            <form data-formulario-calificacion>
                <fieldset><legend>Calificación</legend><div class="detalle-calificacion-modal__estrellas">${[1, 2, 3, 4, 5].map((valor) => `<button type="button" class="${calificacionInicial >= valor ? 'seleccionada' : ''}" data-calificacion-valor="${valor}" aria-label="${valor} estrellas">★</button>`).join('')}</div></fieldset>
                <label for="detalleCalificacionComentario">Comentario (opcional)</label>
                <textarea id="detalleCalificacionComentario" name="comentario" maxlength="1000" rows="4" placeholder="Escribe un comentario...">${escaparDetalleCompra(evaluacion?.comentario || '')}</textarea>
                <p class="detalle-calificacion-modal__mensaje" data-mensaje-calificacion></p>
                <div class="detalle-calificacion-modal__acciones"><button type="button" class="detalle-calificacion-modal__cancelar" data-cerrar-calificacion>Cancelar</button><button type="submit" class="detalle-calificacion-modal__guardar">Guardar</button></div>
            </form>
        </div>`;
    document.body.appendChild(modal);

    let calificacion = calificacionInicial;
    const botonesEstrella = [...modal.querySelectorAll('[data-calificacion-valor]')];
    const actualizarEstrellas = () => botonesEstrella.forEach((boton) => boton.classList.toggle('seleccionada', Number(boton.dataset.calificacionValor) <= calificacion));
    botonesEstrella.forEach((boton) => boton.addEventListener('click', () => {
        calificacion = Number(boton.dataset.calificacionValor);
        actualizarEstrellas();
    }));
    modal.querySelectorAll('[data-cerrar-calificacion]').forEach((boton) => boton.addEventListener('click', () => modal.remove()));
    modal.addEventListener('click', (evento) => { if (evento.target === modal) modal.remove(); });
    modal.querySelector('[data-formulario-calificacion]').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const mensaje = modal.querySelector('[data-mensaje-calificacion]');
        if (!calificacion) {
            mensaje.textContent = 'Selecciona una calificación antes de guardar.';
            return;
        }

        const datosFormulario = new FormData(evento.currentTarget);
        datosFormulario.append('id_compra', datos.compra.id_compra);
        datosFormulario.append('tipo', tipo);
        datosFormulario.append('calificacion', calificacion);
        if (evaluacion?.id_evaluacion) {
            datosFormulario.append('id_evaluacion', evaluacion.id_evaluacion);
        }
        const botonGuardar = modal.querySelector('.detalle-calificacion-modal__guardar');
        botonGuardar.disabled = true;
        mensaje.textContent = 'Guardando...';
        try {
            const respuesta = await fetch('../api/api.php?modulo=historial_compras&accion=guardar_evaluacion_compra', { method: 'POST', body: datosFormulario });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible guardar la calificación.');
            modal.remove();
            await cargarDetalleCompra();
        } catch (error) {
            botonGuardar.disabled = false;
            mensaje.textContent = error.message;
        }
    });
}

/* Formatea el tamaño del archivo para mostrarlo en el listado */
function formatearTamanoDocumentoDetalle(bytes) {
    const tamano = Number(bytes || 0);
    if (tamano < 1024) return `${tamano} B`;
    if (tamano < 1024 * 1024) return `${(tamano / 1024).toFixed(1)} KB`;
    return `${(tamano / (1024 * 1024)).toFixed(1)} MB`;
}

/* Convierte el nombre interno del tipo de documento a una etiqueta legible */
function formatearTipoDocumentoDetalle(tipo) {
    const nombre = String(tipo || '').replace(/_/g, ' ');
    return nombre ? nombre.charAt(0).toUpperCase() + nombre.slice(1) : 'Sin tipo';
}

/* Actualiza el selector con los tipos disponibles en la base de datos */
function renderizarTiposDocumentosDetalle(tipos) {
    const opciones = document.querySelector('[data-documento-tipo-opciones]');
    const valor = document.querySelector('[data-documento-tipo-valor]');
    const etiqueta = document.querySelector('[data-documento-tipo-label]');
    if (!opciones || !valor || !etiqueta) {
        return;
    }

    valor.value = '';
    etiqueta.textContent = 'Seleccionar tipo';
    document.querySelector('[data-documento-selector-boton]')?.querySelector('.detalle-documento-selector__radio')?.classList.remove('detalle-documento-selector__radio--seleccionado');
    opciones.innerHTML = [`<label class="detalle-documento-radio-opcion"><input type="radio" name="documento_tipo" value=""><span>Seleccionar tipo</span></label>`, ...(tipos || []).map((tipo) => {
        const nombre = formatearTipoDocumentoDetalle(tipo.nombre);
        return `<label class="detalle-documento-radio-opcion"><input type="radio" name="documento_tipo" value="${escaparDetalleCompra(tipo.id)}"><span>${escaparDetalleCompra(nombre)}</span></label>`;
    })].join('');
}

/* Cierra el selector de tipo de documento cuando pierde el foco visual */
function cerrarSelectorTipoDocumentoDetalle() {
    const panel = document.querySelector('[data-documento-selector-panel]');
    const boton = document.querySelector('[data-documento-selector-boton]');
    if (panel) {
        panel.hidden = true;
    }
    if (boton) {
        boton.setAttribute('aria-expanded', 'false');
    }
}

/* Abre o cierra el listado flotante de tipos y enfoca su buscador */
function alternarSelectorTipoDocumentoDetalle() {
    const panel = document.querySelector('[data-documento-selector-panel]');
    const boton = document.querySelector('[data-documento-selector-boton]');
    const busqueda = document.querySelector('[data-documento-tipo-busqueda]');
    if (!panel || !boton) {
        return;
    }

    const abrir = panel.hidden;
    panel.hidden = !abrir;
    boton.setAttribute('aria-expanded', String(abrir));
    if (abrir) {
        busqueda?.focus();
    }
}

/* Filtra las opciones del selector sin cambiar la altura del formulario */
function filtrarTiposDocumentoDetalle(valorBusqueda) {
    const busqueda = String(valorBusqueda || '').trim().toLowerCase();
    document.querySelectorAll('[data-documento-tipo-opciones] .detalle-documento-radio-opcion').forEach((opcion) => {
        opcion.hidden = !opcion.textContent.toLowerCase().includes(busqueda);
    });
}

/* Abre la previsualización y la descarga del documento seleccionado */
function abrirModalDocumentoDetalle(documento) {
    const idDocumento = Number(documento.id_documento);
    if (!idDocumento || !documento.tiene_archivo) {
        return;
    }

    document.querySelector('[data-modal-documento]')?.remove();
    const urlPrevisualizacion = `../api/documento_compra.php?id_documento=${encodeURIComponent(idDocumento)}&modo=previsualizar`;
    const urlDescarga = `../api/documento_compra.php?id_documento=${encodeURIComponent(idDocumento)}&modo=descargar`;
    const tipoArchivo = documento.tipo_archivo || '';
    let contenido = '';

    if (tipoArchivo.startsWith('image/')) {
        contenido = `<img class="detalle-documento-modal__imagen" src="${urlPrevisualizacion}" alt="${escaparDetalleCompra(documento.nombre_archivo || documento.tipo)}">`;
    } else if (tipoArchivo === 'application/pdf') {
        contenido = `<iframe class="detalle-documento-modal__visor" src="${urlPrevisualizacion}" title="Previsualizacion de ${escaparDetalleCompra(documento.nombre_archivo || documento.tipo)}"></iframe>`;
    } else {
        contenido = '<div class="detalle-documento-modal__no-disponible"><i class="bi bi-file-earmark-word" aria-hidden="true"></i><p>Este formato Word no se puede previsualizar directamente en el navegador.</p></div>';
    }

    const modal = document.createElement('div');
    modal.className = 'detalle-documento-modal';
    modal.dataset.modalDocumento = 'true';
    modal.innerHTML = `<div class="detalle-documento-modal__contenido" role="dialog" aria-modal="true" aria-labelledby="detalleDocumentoTitulo"><div class="detalle-documento-modal__encabezado"><div><h2 id="detalleDocumentoTitulo">${escaparDetalleCompra(documento.nombre_archivo || documento.tipo)}</h2><small>${escaparDetalleCompra(documento.tipo)} · ${formatearTamanoDocumentoDetalle(documento.tamano_bytes)}</small></div><button type="button" data-cerrar-documento aria-label="Cerrar">&times;</button></div><div class="detalle-documento-modal__previsualizacion">${contenido}</div><div class="detalle-documento-modal__acciones"><a class="detalle-documento-modal__descargar" href="${urlDescarga}"><i class="bi bi-download" aria-hidden="true"></i>Descargar</a><button type="button" class="detalle-documento-modal__cerrar" data-cerrar-documento>Cerrar</button></div></div>`;
    document.body.appendChild(modal);
    modal.querySelectorAll('[data-cerrar-documento]').forEach((boton) => boton.addEventListener('click', () => modal.remove()));
    modal.addEventListener('click', (evento) => { if (evento.target === modal) modal.remove(); });
}

/* Solicita confirmación y elimina el documento después de la respuesta afirmativa */
function abrirModalEliminarDocumentoDetalle(documento) {
    document.querySelector('[data-modal-documento-eliminar]')?.remove();
    const modal = document.createElement('div');
    modal.className = 'detalle-calificacion-modal';
    modal.dataset.modalDocumentoEliminar = 'true';
    modal.innerHTML = `<div class="detalle-calificacion-modal__contenido detalle-confirmacion-documento" role="dialog" aria-modal="true" aria-labelledby="eliminarDocumentoTitulo"><div class="detalle-calificacion-modal__encabezado"><h2 id="eliminarDocumentoTitulo">Eliminar documento</h2><button type="button" data-cancelar-eliminar-documento aria-label="Cerrar">&times;</button></div><p>¿Estás seguro de que deseas eliminar <strong>${escaparDetalleCompra(documento.nombre_archivo || documento.tipo)}</strong>?</p><p class="detalle-calificacion-modal__mensaje" data-mensaje-eliminar-documento></p><div class="detalle-calificacion-modal__acciones"><button type="button" class="detalle-calificacion-modal__cancelar" data-cancelar-eliminar-documento>Cancelar</button><button type="button" class="detalle-documento-modal__eliminar-confirmar" data-confirmar-eliminar-documento>Sí, eliminar</button></div></div>`;
    document.body.appendChild(modal);
    modal.querySelectorAll('[data-cancelar-eliminar-documento]').forEach((boton) => boton.addEventListener('click', () => modal.remove()));
    modal.addEventListener('click', (evento) => { if (evento.target === modal) modal.remove(); });
    modal.querySelector('[data-confirmar-eliminar-documento]').addEventListener('click', async () => {
        const mensaje = modal.querySelector('[data-mensaje-eliminar-documento]');
        const botonConfirmar = modal.querySelector('[data-confirmar-eliminar-documento]');
        const datosFormulario = new FormData();
        datosFormulario.append('id_documento', documento.id_documento);
        botonConfirmar.disabled = true;
        mensaje.textContent = 'Eliminando documento...';
        try {
            const respuesta = await fetch('../api/api.php?modulo=historial_compras&accion=eliminar_documento_compra', { method: 'POST', body: datosFormulario });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible eliminar el documento.');
            modal.remove();
            await cargarDetalleCompra();
        } catch (error) {
            botonConfirmar.disabled = false;
            mensaje.textContent = error.message;
        }
    });
}

/* Renderiza los documentos asociados, incluyendo los archivos que pueden abrirse */
function renderizarDocumentosDetalle(documentos) {
    const contenedor = document.querySelector('[data-detalle-documentos]');
    if (!contenedor) {
        return;
    }

    documentosCompraActuales = documentos || [];
    if (!documentos.length) {
        contenedor.innerHTML = '<p class="detalle-vacio">No hay documentos asociados a esta compra.</p>';
        return;
    }

    contenedor.innerHTML = documentos.map((documento) => {
        const icono = documento.tipo_archivo === 'application/pdf' ? 'bi-filetype-pdf' : (documento.tipo_archivo?.startsWith('image/') ? 'bi-file-earmark-image' : 'bi-file-earmark-word');
        const nombre = documento.nombre_archivo || documento.numero_documento || documento.tipo;
        const detalle = documento.tiene_archivo ? `${formatearTamanoDocumentoDetalle(documento.tamano_bytes)} · Previsualizar` : 'Sin archivo cargado';
        return `<article><button type="button" class="detalle-documento-card" data-documento-id="${documento.id_documento}" ${documento.tiene_archivo ? '' : 'disabled'}><span class="detalle-documento-card__icono"><i class="bi ${icono}" aria-hidden="true"></i></span><span><strong>${escaparDetalleCompra(documento.tipo)}</strong><b>${escaparDetalleCompra(nombre)}</b><small>${escaparDetalleCompra(detalle)}</small></span></button>${documento.tiene_archivo ? `<button type="button" class="detalle-documento-card__eliminar" data-eliminar-documento="${documento.id_documento}" aria-label="Eliminar ${escaparDetalleCompra(nombre)}" title="Eliminar documento"><i class="bi bi-trash3" aria-hidden="true"></i></button>` : ''}</article>`;
    }).join('');
}

/* Pinta todos los bloques de la compra recibida desde el API */
function renderizarDetalleCompra(datos) {
    const { compra } = datos;
    const estado = document.querySelector('[data-detalle-compra-estado]');
    const titulo = document.querySelector('[data-detalle-compra-titulo]');
    const subtitulo = document.querySelector('[data-detalle-compra-subtitulo]');

    if (titulo) {
        titulo.textContent = `Compra # ${compra.codigo_compra}`;
    }
    if (estado) {
        estado.outerHTML = crearEstadoEncabezadoDetalle(compra);
    }
    if (subtitulo) {
        subtitulo.textContent = `Compra registrada el ${formatearFechaDetalle(compra.fecha_compra)}${compra.fecha_recepcion ? ` y recepcionada el ${formatearFechaDetalle(compra.fecha_recepcion)}` : ''}.`;
    }

    renderizarResumenDetalle(datos);
    renderizarArticulosDetalle(datos);
    renderizarComentariosDetalle(datos.comentarios);
    renderizarRecepcionDetalle(datos);
    renderizarCalificacionesDetalle(datos);
    renderizarTiposDocumentosDetalle(datos.tipos_documentos);
    renderizarDocumentosDetalle(datos.documentos);
}

/* Carga el detalle solicitado y muestra un mensaje claro cuando no puede obtenerse */
async function cargarDetalleCompra() {
    const idCompra = new URLSearchParams(window.location.search).get('id_compra');
    if (!idCompra || !/^\d+$/.test(idCompra)) {
        throw new Error('No se recibio una compra valida.');
    }

    const respuesta = await fetch(`../api/api.php?modulo=historial_compras&accion=obtener_detalle_compra&id_compra=${encodeURIComponent(idCompra)}`);
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) {
        throw new Error(resultado.mensaje || 'No fue posible cargar el detalle de la compra.');
    }

    renderizarDetalleCompra(resultado.datos);
    return resultado.datos;
}

/* Carga y configura el detalle de la compra */
document.addEventListener('DOMContentLoaded', () => {
    cargarDetalleCompra().catch((error) => {
        const contenedor = document.querySelector('[data-detalle-compra]');
        if (contenedor) {
            contenedor.innerHTML = `<div class="detalle-error"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><h2>No fue posible cargar la compra</h2><p>${escaparDetalleCompra(error.message)}</p><a href="historial_compras.php">Volver al historial</a></div>`;
        }
    });

    document.addEventListener('click', (evento) => {
        const botonSelectorTipo = evento.target.closest('[data-documento-selector-boton]');
        if (botonSelectorTipo) {
            alternarSelectorTipoDocumentoDetalle();
            return;
        }

        if (!evento.target.closest('[data-documento-selector]')) {
            cerrarSelectorTipoDocumentoDetalle();
        }

        const botonCambiarEstado = evento.target.closest('[data-cambiar-estado]');
        if (botonCambiarEstado) {
            cargarDetalleCompra()
                .then((datos) => abrirModalEstadoDetalle(datos))
                .catch((error) => window.alert(error.message));
            return;
        }

        const botonEditarComentario = evento.target.closest('[data-editar-comentario]');
        if (botonEditarComentario) {
            const comentario = comentariosCompraActuales.find((item) => Number(item.id_comentario) === Number(botonEditarComentario.dataset.editarComentario));
            if (comentario?.puede_editar) {
                prepararEdicionComentarioDetalle(comentario);
            }
            return;
        }

        const botonCancelarComentario = evento.target.closest('[data-comentario-cancelar]');
        if (botonCancelarComentario) {
            reiniciarFormularioComentarioDetalle();
            return;
        }

        const boton = evento.target.closest('[data-calificar-compra]');
        if (boton && !boton.disabled) {
            cargarDetalleCompra()
                .then((datos) => abrirModalCalificacionDetalle(datos, boton.dataset.calificarCompra))
                .catch((error) => window.alert(error.message));
            return;
        }

        const botonEliminarDocumento = evento.target.closest('[data-eliminar-documento]');
        if (botonEliminarDocumento) {
            evento.preventDefault();
            evento.stopPropagation();
            const documento = documentosCompraActuales.find((item) => Number(item.id_documento) === Number(botonEliminarDocumento.dataset.eliminarDocumento));
            if (documento) {
                abrirModalEliminarDocumentoDetalle(documento);
            }
            return;
        }

        const botonDocumento = evento.target.closest('[data-documento-id]');
        if (botonDocumento && !botonDocumento.disabled) {
            const documento = documentosCompraActuales.find((item) => Number(item.id_documento) === Number(botonDocumento.dataset.documentoId));
            if (documento) {
                abrirModalDocumentoDetalle(documento);
            }
        }
    });

    document.addEventListener('input', (evento) => {
        if (evento.target.matches('[data-documento-tipo-busqueda]')) {
            filtrarTiposDocumentoDetalle(evento.target.value);
        }
    });

    document.addEventListener('change', (evento) => {
        const opcion = evento.target.matches('[data-documento-tipo-opciones] input[name="documento_tipo"]')
            ? evento.target
            : null;
        if (!opcion || !evento.target.closest('[data-documento-tipo-opciones]')) {
            return;
        }

        const valor = document.querySelector('[data-documento-tipo-valor]');
        const etiqueta = document.querySelector('[data-documento-tipo-label]');
        const indicador = document.querySelector('[data-documento-selector-boton] .detalle-documento-selector__radio');
        if (valor) {
            valor.value = opcion.value;
        }
        if (etiqueta) {
            etiqueta.textContent = opcion.parentElement.textContent.trim();
        }
        indicador?.classList.toggle('detalle-documento-selector__radio--seleccionado', Boolean(opcion.value));
        cerrarSelectorTipoDocumentoDetalle();
    });

    document.addEventListener('submit', async (evento) => {
        const formularioDocumento = evento.target.closest('[data-documento-form]');
        if (!formularioDocumento) {
            return;
        }

        evento.preventDefault();
        const mensaje = formularioDocumento.querySelector('[data-documento-mensaje]');
        const botonSubir = formularioDocumento.querySelector('.detalle-documentos__subir');
        const idCompra = new URLSearchParams(window.location.search).get('id_compra');
        const datosFormulario = new FormData(formularioDocumento);
        datosFormulario.append('id_compra', idCompra);
        botonSubir.disabled = true;
        mensaje.className = 'detalle-documentos__mensaje';
        mensaje.textContent = 'Cargando documento...';
        try {
            const respuesta = await fetch('../api/api.php?modulo=historial_compras&accion=subir_documento_compra', { method: 'POST', body: datosFormulario });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el documento.');
            await cargarDetalleCompra();
            const nuevoMensaje = document.querySelector('[data-documento-mensaje]');
            if (nuevoMensaje) {
                nuevoMensaje.className = 'detalle-documentos__mensaje detalle-documentos__mensaje--exito';
                nuevoMensaje.textContent = resultado.mensaje;
            }
        } catch (error) {
            mensaje.className = 'detalle-documentos__mensaje detalle-documentos__mensaje--error';
            mensaje.textContent = error.message;
        } finally {
            botonSubir.disabled = false;
        }
    });

    document.addEventListener('submit', async (evento) => {
        const formulario = evento.target.closest('[data-formulario-comentario]');
        if (!formulario) {
            return;
        }

        evento.preventDefault();
        const mensaje = document.querySelector('[data-comentario-mensaje]');
        const botonGuardar = formulario.querySelector('[data-comentario-guardar]');
        const datosFormulario = new FormData(formulario);
        datosFormulario.append('id_compra', new URLSearchParams(window.location.search).get('id_compra'));
        botonGuardar.disabled = true;
        mensaje.className = 'detalle-comentarios__mensaje';
        mensaje.textContent = 'Guardando comentario...';

        try {
            const respuesta = await fetch('../api/api.php?modulo=historial_compras&accion=guardar_comentario_compra', { method: 'POST', body: datosFormulario });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.exito) {
                throw new Error(resultado.mensaje || 'No fue posible guardar el comentario.');
            }

            await cargarDetalleCompra();
            reiniciarFormularioComentarioDetalle();
            mensaje.className = 'detalle-comentarios__mensaje detalle-comentarios__mensaje--exito';
            mensaje.textContent = resultado.mensaje;
        } catch (error) {
            mensaje.className = 'detalle-comentarios__mensaje detalle-comentarios__mensaje--error';
            mensaje.textContent = error.message;
        } finally {
            botonGuardar.disabled = false;
        }
    });
});
