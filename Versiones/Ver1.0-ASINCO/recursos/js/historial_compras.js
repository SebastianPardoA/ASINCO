/**
 * historial_compras.js
 *
 * Consulta y muestra el historial de compras
 */

let temporizadorHistorial;
let temporizadorPaginaHistorial;
let paginaHistorial = 1;

/* Evita que el texto se interprete como HTML */
function escaparHistorial(valor)
{
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[caracter]));
}

/* Formatea una fecha SQL para mostrarla en formato chileno */
function formatearFechaHistorial(fecha)
{
    if (!fecha) return 'Sin fecha';
    const partes = String(fecha).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : escaparHistorial(fecha);
}

/* Formatea montos conservando la moneda registrada en cada compra */
function formatearMontoHistorial(valor, moneda)
{
    return `${escaparHistorial(moneda)} ${Number(valor || 0).toLocaleString('es-CL', { maximumFractionDigits: 0 })}`.trim();
}

/* Traduce los estados internos de compra a nombres visibles */
function textoEstadoHistorial(estado)
{
    const estados = {
        registrada: 'Registrada',
        confirmada: 'Confirmada',
        en_preparacion: 'En preparacion',
        en_transito: 'En transito',
        recibida: 'Recibida',
        con_problemas: 'Con problemas',
        atrasada: 'Atrasada',
        cancelada: 'Cancelada',
    };
    return estados[estado] || estado || 'Sin estado';
}

/* Asigna una clase visual al estado real de la compra */
function claseEstadoHistorial(estado)
{
    const clases = {
        registrada: 'historial-estado--registrada',
        confirmada: 'historial-estado--confirmada',
        en_preparacion: 'historial-estado--preparacion',
        en_transito: 'historial-estado--transito',
        recibida: 'historial-estado--recibida',
        con_problemas: 'historial-estado--problemas',
        atrasada: 'historial-estado--atrasada',
        cancelada: 'historial-estado--cancelada',
    };
    return clases[estado] || 'historial-estado--registrada';
}

/* Actualiza los indicadores superiores usando el resumen de la API */
function renderizarResumenHistorial(resumen)
{
    document.querySelector('[data-historial-compras]').textContent = Number(resumen.compras || 0).toLocaleString('es-CL');
    document.querySelector('[data-historial-articulos]').textContent = Number(resumen.articulos || 0).toLocaleString('es-CL');
    document.querySelector('[data-historial-recibidas]').textContent = Number(resumen.recibidas || 0).toLocaleString('es-CL');
    document.querySelector('[data-historial-montos]').textContent = resumen.montos?.length
        ? resumen.montos.map((monto) => formatearMontoHistorial(monto.total, monto.moneda)).join(' · ')
        : 'Sin datos';
}

/* Actualiza la opción seleccionada */
function actualizarTextoSelector(tipo, opciones, seleccionado, textoTodos, traducirEstado = false)
{
    const opcionSeleccionada = Number(seleccionado) === 0
        ? null
        : (opciones || []).find((opcion) => Number(opcion.id) === Number(seleccionado));
    const texto = opcionSeleccionada
        ? (traducirEstado ? textoEstadoHistorial(opcionSeleccionada.nombre) : opcionSeleccionada.nombre)
        : textoTodos;
    const valor = document.querySelector(`[data-historial-selector-valor="${tipo}"]`);
    const radio = document.querySelector(`[data-historial-selector-boton="${tipo}"] .historial-selector__radio`);
    if (valor) valor.textContent = texto;
    if (radio) radio.classList.add('historial-selector__radio--seleccionado');
}

/* Cierra todos los paneles flotantes de filtros, excepto el indicado */
function cerrarPanelesHistorial(excepto = '')
{
    document.querySelectorAll('[data-historial-selector-panel]').forEach((panel) => {
        const tipo = panel.dataset.historialSelectorPanel;
        if (tipo === excepto) return;
        panel.hidden = true;
        document.querySelector(`[data-historial-selector-boton="${tipo}"]`)?.setAttribute('aria-expanded', 'false');
    });
}

/* Abre o cierra el listado flotante de un filtro */
function alternarSelectorHistorial(tipo)
{
    const panel = document.querySelector(`[data-historial-selector-panel="${tipo}"]`);
    const boton = document.querySelector(`[data-historial-selector-boton="${tipo}"]`);
    const seAbrira = panel.hidden;
    cerrarPanelesHistorial(seAbrira ? tipo : '');
    panel.hidden = !seAbrira;
    boton.setAttribute('aria-expanded', String(seAbrira));
    if (seAbrira) {
        const buscador = panel.querySelector('input[type="search"]');
        if (buscador) buscador.focus();
    }
}

/* Inicializa la apertura flotante de los filtros */
function inicializarSelectoresHistorial()
{
    document.querySelectorAll('[data-historial-selector-boton]').forEach((boton) => {
        boton.addEventListener('click', () => alternarSelectorHistorial(boton.dataset.historialSelectorBoton));
    });
    document.addEventListener('click', (evento) => {
        if (!evento.target.closest('[data-historial-selector]')) cerrarPanelesHistorial();
    });
}

/* Renderiza las opciones internas de un filtro */
function renderizarOpcionesRadio(opciones, selector, nombre, seleccionado, textoTodos, tipo)
{
    const contenedor = document.querySelector(selector);
    const opcionesSeguras = Array.isArray(opciones) ? opciones : [];
    contenedor.innerHTML = [
        `<label class="historial-radio-opcion"><input type="radio" name="${nombre}" value="0" ${Number(seleccionado) === 0 ? 'checked' : ''}><span>${textoTodos}</span></label>`,
        ...opcionesSeguras.map((opcion) => `
            <label class="historial-radio-opcion">
                <input type="radio" name="${nombre}" value="${Number(opcion.id)}" ${Number(seleccionado) === Number(opcion.id) ? 'checked' : ''}>
                <span>${escaparHistorial(opcion.nombre)}</span>
            </label>`),
    ].join('');

    contenedor.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const texto = radio.closest('.historial-radio-opcion')?.querySelector('span')?.textContent || textoTodos;
            const valor = document.querySelector(`[data-historial-selector-valor="${tipo}"]`);
            if (valor) valor.textContent = texto;
            cerrarPanelesHistorial();
            cargarHistorial(1);
        });
    });
    actualizarTextoSelector(tipo, opcionesSeguras, seleccionado, textoTodos);
}

/* Carga los estados de compra */
function renderizarOpcionesEstado(estados, seleccionado)
{
    const contenedor = document.querySelector('[data-historial-estado-opciones]');
    contenedor.innerHTML = [
        `<label class="historial-radio-opcion"><input type="radio" name="historial_estado" value="0" ${Number(seleccionado) === 0 ? 'checked' : ''}><span>Todos los estados</span></label>`,
        ...(Array.isArray(estados) ? estados : []).map((estado) => `
            <label class="historial-radio-opcion">
                <input type="radio" name="historial_estado" value="${Number(estado.id)}" ${Number(seleccionado) === Number(estado.id) ? 'checked' : ''}>
                <span>${escaparHistorial(textoEstadoHistorial(estado.nombre))}</span>
            </label>`),
    ].join('');
    contenedor.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const texto = radio.closest('.historial-radio-opcion')?.querySelector('span')?.textContent || 'Todos los estados';
            const valor = document.querySelector('[data-historial-selector-valor="estado"]');
            if (valor) valor.textContent = texto;
            cerrarPanelesHistorial();
            cargarHistorial(1);
        });
    });
    actualizarTextoSelector('estado', estados, seleccionado, 'Todos los estados', true);
}

/* Oculta las opciones de proveedor o transportista que no coinciden con su buscador */
function filtrarOpcionesRadio(selectorOpciones, valorBusqueda)
{
    const texto = String(valorBusqueda || '').trim().toLocaleLowerCase('es');
    document.querySelectorAll(`${selectorOpciones} .historial-radio-opcion`).forEach((opcion) => {
        opcion.hidden = texto !== '' && !opcion.textContent.toLocaleLowerCase('es').includes(texto);
    });
}

/* Carga las compras en el historial */
function renderizarComprasHistorial(compras)
{
    const lista = document.querySelector('[data-historial-lista]');
    if (!compras.length) {
        lista.innerHTML = '<tr><td colspan="9">No hay compras que coincidan con los filtros seleccionados.</td></tr>';
        return;
    }

    lista.innerHTML = compras.map((compra) => `
        <tr>
            <td>${escaparHistorial(compra.codigo_compra)}</td>
            <td>${escaparHistorial(formatearFechaHistorial(compra.fecha_compra))}</td>
            <td>${escaparHistorial(compra.proveedor)}</td>
            <td>${escaparHistorial(compra.transportista)}</td>
            <td>${Number(compra.productos || 0).toLocaleString('es-CL')} productos</td>
            <td>${formatearMontoHistorial(compra.total, compra.moneda)}</td>
            <td><span class="historial-estado ${claseEstadoHistorial(compra.estado)}">${escaparHistorial(textoEstadoHistorial(compra.estado))}</span></td>
            <td>${escaparHistorial(compra.responsable)}</td>
            <td><a class="historial-opciones" href="detalle_compra.php?id_compra=${encodeURIComponent(compra.id_compra)}" aria-label="Ver detalle de la compra ${escaparHistorial(compra.codigo_compra)}" title="Ver detalle"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
        </tr>`).join('');
}

/* Crea la paginación del historial con el resultado recibido desde la API */
function renderizarPaginacionHistorial(paginacion)
{
    const contenedor = document.querySelector('[data-historial-paginacion]');
    const totalPaginas = Number(paginacion.total_paginas || 1);
    const paginaActual = Number(paginacion.pagina_actual || 1);
    let html = `<button type="button" data-historial-pagina="${Math.max(1, paginaActual - 1)}" ${paginaActual === 1 ? 'disabled' : ''} aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>`;

    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
        html += `<button type="button" class="${pagina === paginaActual ? 'historial-pagina-activa' : ''}" data-historial-pagina="${pagina}" ${pagina === paginaActual ? 'aria-current="page"' : ''}>${pagina}</button>`;
    }

    html += `<button type="button" data-historial-pagina="${Math.min(totalPaginas, paginaActual + 1)}" ${paginaActual === totalPaginas ? 'disabled' : ''} aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = html;
    contenedor.querySelectorAll('[data-historial-pagina]').forEach((boton) => {
        boton.addEventListener('click', () => cargarHistorial(Number(boton.dataset.historialPagina)));
    });
}

/* Obtiene los valores actuales de todos los filtros visibles */
function obtenerFiltrosHistorial()
{
    return {
        busqueda: document.querySelector('[data-historial-busqueda]').value.trim(),
        fecha: document.querySelector('[data-historial-fecha]').value,
        id_proveedor: document.querySelector('input[name="historial_proveedor"]:checked')?.value || '0',
        id_transportista: document.querySelector('input[name="historial_transportista"]:checked')?.value || '0',
        id_estado_compra: document.querySelector('input[name="historial_estado"]:checked')?.value || '0',
    };
}

/* Obtiene y valida la cantidad de compras que debe mostrar cada página */
function obtenerTamanoPaginaHistorial()
{
    const selector = document.querySelector('[data-historial-pagina-tamano]');
    const entradaPersonalizada = document.querySelector('[data-historial-pagina-personalizada]');
    const valor = selector.value === 'personalizada' ? entradaPersonalizada.value : selector.value;
    const tamano = Number(valor);
    return Number.isInteger(tamano) && tamano >= 1 && tamano <= 50 ? tamano : 7;
}

/* Solicita una página del historial con los filtros seleccionados */
async function cargarHistorial(pagina = 1)
{
    const lista = document.querySelector('[data-historial-lista]');
    paginaHistorial = Math.max(1, pagina);
    const filtros = obtenerFiltrosHistorial();
    const parametros = new URLSearchParams({
        modulo: 'historial_compras',
        accion: 'listar_historial_compras',
        pagina: String(paginaHistorial),
        por_pagina: String(obtenerTamanoPaginaHistorial()),
        ...filtros,
    });

    try {
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el historial.');

        renderizarResumenHistorial(resultado.datos.resumen);
        renderizarComprasHistorial(resultado.datos.compras || []);
        renderizarOpcionesRadio(resultado.datos.opciones?.proveedores || [], '[data-historial-proveedor-opciones]', 'historial_proveedor', filtros.id_proveedor, 'Todos los proveedores', 'proveedor');
        renderizarOpcionesRadio(resultado.datos.opciones?.transportistas || [], '[data-historial-transportista-opciones]', 'historial_transportista', filtros.id_transportista, 'Todos los transportistas', 'transportista');
        renderizarOpcionesEstado(resultado.datos.opciones?.estados || [], filtros.id_estado_compra);
        filtrarOpcionesRadio('[data-historial-proveedor-opciones]', document.querySelector('[data-historial-proveedor-busqueda]').value);
        filtrarOpcionesRadio('[data-historial-transportista-opciones]', document.querySelector('[data-historial-transportista-busqueda]').value);
        renderizarPaginacionHistorial(resultado.datos.paginacion);

        const inicio = resultado.datos.compras.length ? ((resultado.datos.paginacion.pagina_actual - 1) * resultado.datos.paginacion.por_pagina) + 1 : 0;
        const fin = inicio + resultado.datos.compras.length - 1;
        document.querySelector('[data-historial-contador]').textContent = `Mostrando ${inicio} a ${Math.max(0, fin)} de ${resultado.datos.paginacion.total} compras`;
    } catch (error) {
        lista.innerHTML = `<tr><td colspan="9">${escaparHistorial(error.message)}</td></tr>`;
    }
}

/* Reinicia los filtros y vuelve a consultar el historial completo */
function limpiarFiltrosHistorial()
{
    document.querySelector('[data-historial-busqueda]').value = '';
    document.querySelector('[data-historial-fecha]').value = '';
    document.querySelector('[data-historial-proveedor-busqueda]').value = '';
    document.querySelector('[data-historial-transportista-busqueda]').value = '';
    document.querySelectorAll('input[name^="historial_"]').forEach((radio) => {
        radio.checked = radio.value === '0';
    });
    cerrarPanelesHistorial();
    cargarHistorial(1);
}

/* Inicializa los controles y carga los datos al abrir la vista */
document.addEventListener('DOMContentLoaded', () => {
    inicializarSelectoresHistorial();
    document.querySelector('[data-historial-busqueda]').addEventListener('input', () => {
        clearTimeout(temporizadorHistorial);
        temporizadorHistorial = setTimeout(() => cargarHistorial(1), 300);
    });
    document.querySelector('[data-historial-fecha]').addEventListener('change', () => cargarHistorial(1));
    document.querySelector('[data-historial-proveedor-busqueda]').addEventListener('input', (evento) => filtrarOpcionesRadio('[data-historial-proveedor-opciones]', evento.target.value));
    document.querySelector('[data-historial-transportista-busqueda]').addEventListener('input', (evento) => filtrarOpcionesRadio('[data-historial-transportista-opciones]', evento.target.value));
    document.querySelector('[data-historial-limpiar]').addEventListener('click', limpiarFiltrosHistorial);

    const selectorTamanoPagina = document.querySelector('[data-historial-pagina-tamano]');
    const entradaTamanoPagina = document.querySelector('[data-historial-pagina-personalizada]');
    selectorTamanoPagina.addEventListener('change', () => {
        const esPersonalizada = selectorTamanoPagina.value === 'personalizada';
        entradaTamanoPagina.hidden = !esPersonalizada;
        if (esPersonalizada) entradaTamanoPagina.focus();
        cargarHistorial(1);
    });
    entradaTamanoPagina.addEventListener('input', () => {
        clearTimeout(temporizadorPaginaHistorial);
        if (obtenerTamanoPaginaHistorial() !== Number(entradaTamanoPagina.value)) return;
        temporizadorPaginaHistorial = setTimeout(() => cargarHistorial(1), 300);
    });

    cargarHistorial();
});
