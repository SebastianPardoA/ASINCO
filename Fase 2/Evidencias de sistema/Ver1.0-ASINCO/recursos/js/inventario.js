/**
 * inventario.js
 *
 * Consulta el inventario, controla los filtros por radio y actualiza indicadores
 */

let temporizadorInventario;
let temporizadorPaginaInventario;

/* Evita que el texto se interprete como HTML */
function escaparInventario(valor)
{
    return String(valor ?? '').replace(/[&<>'"]/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    }[caracter]));
}

/* Formatea cantidades usando la separación numérica local */
function formatearCantidadInventario(valor)
{
    return Number(valor || 0).toLocaleString('es-CL');
}

/* Traduce los estados internos de las unidades a texto visible */
function textoEstadoInventario(estado)
{
    const estados = {
        disponible: 'Disponible',
        asignada: 'Asignada',
        en_uso: 'En uso',
        reservada: 'Reservada',
        danada: 'Dañada',
        dada_de_baja: 'Dada de baja',
    };
    return estados[estado] || estado || 'Sin estado';
}

/* Actualiza los cuatro indicadores con el resumen filtrado */
function renderizarResumenInventario(resumen)
{
    document.querySelector('[data-inventario-productos]').textContent = formatearCantidadInventario(resumen?.productos);
    document.querySelector('[data-inventario-stock-total]').textContent = formatearCantidadInventario(resumen?.stock_total);
    document.querySelector('[data-inventario-stock-asignado]').textContent = formatearCantidadInventario(resumen?.stock_asignado);
    document.querySelector('[data-inventario-stock-disponible]').textContent = formatearCantidadInventario(resumen?.stock_disponible);
}

/* Cierra todos los paneles flotantes, excepto el filtro indicado */
function cerrarPanelesInventario(excepto = '')
{
    document.querySelectorAll('[data-inventario-selector-panel]').forEach((panel) => {
        const tipo = panel.dataset.inventarioSelectorPanel;
        if (tipo === excepto) return;
        panel.hidden = true;
        document.querySelector(`[data-inventario-selector-boton="${tipo}"]`)?.setAttribute('aria-expanded', 'false');
    });
}

/* Abre o cierra un listado de opciones por radio */
function alternarSelectorInventario(tipo)
{
    const panel = document.querySelector(`[data-inventario-selector-panel="${tipo}"]`);
    const boton = document.querySelector(`[data-inventario-selector-boton="${tipo}"]`);
    const seAbrira = panel.hidden;
    cerrarPanelesInventario(seAbrira ? tipo : '');
    panel.hidden = !seAbrira;
    boton.setAttribute('aria-expanded', String(seAbrira));
    if (seAbrira) panel.querySelector('input[type="search"]')?.focus();
}

/* Inicializa la apertura y cierre de los filtros flotantes */
function inicializarSelectoresInventario()
{
    document.querySelectorAll('[data-inventario-selector-boton]').forEach((boton) => {
        boton.addEventListener('click', () => alternarSelectorInventario(boton.dataset.inventarioSelectorBoton));
    });
    document.addEventListener('click', (evento) => {
        if (!evento.target.closest('[data-inventario-selector]')) cerrarPanelesInventario();
    });
}

/* Actualiza el texto y el circulo del selector seleccionado */
function actualizarTextoSelectorInventario(tipo, opciones, seleccionado, textoTodos)
{
    const opcion = (opciones || []).find((item) => String(item.id) === String(seleccionado));
    const valor = document.querySelector(`[data-inventario-selector-valor="${tipo}"]`);
    const radio = document.querySelector(`[data-inventario-selector-boton="${tipo}"] .inventario-selector__radio`);
    if (valor) valor.textContent = opcion ? opcion.nombre : textoTodos;
    if (radio) radio.classList.toggle('inventario-selector__radio--seleccionado', Boolean(opcion));
}

/* Oculta las opciones que no coinciden con el texto de búsqueda */
function filtrarOpcionesInventario(selector, valorBusqueda)
{
    const texto = String(valorBusqueda || '').trim().toLocaleLowerCase('es');
    document.querySelectorAll(`${selector} .inventario-radio-opcion`).forEach((opcion) => {
        opcion.hidden = texto !== '' && !opcion.textContent.toLocaleLowerCase('es').includes(texto);
    });
}

/* Crea los filtros y actualiza el inventario */
function renderizarOpcionesRadioInventario(opciones, selector, nombre, seleccionado, textoTodos, tipo, traducir = false)
{
    const contenedor = document.querySelector(selector);
    const lista = Array.isArray(opciones) ? opciones : [];
    const valorTodos = nombre === 'inventario_stock' ? '' : '0';
    contenedor.innerHTML = [
        `<label class="inventario-radio-opcion"><input type="radio" name="${nombre}" value="${valorTodos}" ${String(seleccionado) === valorTodos ? 'checked' : ''}><span>${textoTodos}</span></label>`,
        ...lista.map((opcion) => `<label class="inventario-radio-opcion"><input type="radio" name="${nombre}" value="${escaparInventario(opcion.id)}" ${String(seleccionado) === String(opcion.id) ? 'checked' : ''}><span>${escaparInventario(traducir ? textoEstadoInventario(opcion.nombre) : opcion.nombre)}</span></label>`),
    ].join('');

    contenedor.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            actualizarTextoSelectorInventario(tipo, lista, radio.value, textoTodos);
            cerrarPanelesInventario();
            cargarInventario(1);
        });
    });
    actualizarTextoSelectorInventario(tipo, lista, seleccionado, textoTodos);
}

/* Muestra los productos en el inventario */
function renderizarProductosInventario(productos)
{
    const lista = document.querySelector('[data-inventario-lista]');
    if (!productos.length) {
        lista.innerHTML = '<tr><td colspan="8">No hay productos que coincidan con los filtros seleccionados.</td></tr>';
        return;
    }

    lista.innerHTML = productos.map((producto) => {
        const stockBajo = producto.stock_disponible > 0 && producto.stock_disponible <= 5;
        const rutaImagen = producto.imagen || obtenerRutaImagenSinRegistro('producto');
        const imagen = `<img class="producto-imagen__foto" src="${escaparInventario(rutaImagen)}" alt="Imagen de ${escaparInventario(producto.nombre)}" data-imagen-respaldo>`;
        return `<tr>
            <td><span class="producto-imagen producto-imagen--real">${imagen}</span></td>
            <td>${escaparInventario(producto.nombre)}</td>
            <td>${escaparInventario(producto.codigo_producto)}</td>
            <td>${escaparInventario(producto.categoria)}</td>
            <td>${formatearCantidadInventario(producto.stock_total)}</td>
            <td>${formatearCantidadInventario(producto.stock_asignado)}</td>
            <td class="stock-disponible ${stockBajo ? 'stock-disponible--bajo' : ''}">${formatearCantidadInventario(producto.stock_disponible)}</td>
            <td><a class="inventario-accion-ver" href="detalle_producto.php?id_producto=${encodeURIComponent(producto.id_producto)}" aria-label="Ver detalle de ${escaparInventario(producto.nombre)}" title="Ver detalle"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
        </tr>`;
    }).join('');
}

/* Renderiza los botones de paginación y conserva la página actual */
function renderizarPaginacionInventario(paginacion)
{
    const contenedor = document.querySelector('[data-inventario-paginacion]');
    const totalPaginas = Number(paginacion?.total_paginas || 1);
    const paginaActual = Number(paginacion?.pagina_actual || 1);
    const paginas = [];
    for (let pagina = 1; pagina <= totalPaginas; pagina += 1) {
        if (totalPaginas <= 7 || pagina === 1 || pagina === totalPaginas || Math.abs(pagina - paginaActual) <= 1) paginas.push(pagina);
        else if (paginas[paginas.length - 1] !== '...') paginas.push('...');
    }

    let html = `<button type="button" data-inventario-pagina="${Math.max(1, paginaActual - 1)}" ${paginaActual === 1 ? 'disabled' : ''} aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>`;
    html += paginas.map((pagina) => pagina === '...'
        ? '<span>...</span>'
        : `<button type="button" class="${pagina === paginaActual ? 'inventario-pagina-activa' : ''}" data-inventario-pagina="${pagina}" ${pagina === paginaActual ? 'aria-current="page"' : ''}>${pagina}</button>`).join('');
    html += `<button type="button" data-inventario-pagina="${Math.min(totalPaginas, paginaActual + 1)}" ${paginaActual === totalPaginas ? 'disabled' : ''} aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>`;
    contenedor.innerHTML = html;
    contenedor.querySelectorAll('[data-inventario-pagina]').forEach((boton) => boton.addEventListener('click', () => cargarInventario(Number(boton.dataset.inventarioPagina))));
}

/* Obtiene los filtros seleccionados en pantalla */
function obtenerFiltrosInventario()
{
    return {
        busqueda: document.querySelector('[data-inventario-busqueda]').value.trim(),
        id_categoria: document.querySelector('input[name="inventario_categoria"]:checked')?.value || '0',
        id_proveedor: document.querySelector('input[name="inventario_proveedor"]:checked')?.value || '0',
        id_estado_producto: document.querySelector('input[name="inventario_estado"]:checked')?.value || '0',
        stock: document.querySelector('input[name="inventario_stock"]:checked')?.value || '',
    };
}

/* Obtiene la cantidad de productos por página y aplica sus límites */
function obtenerTamanoPaginaInventario()
{
    const selector = document.querySelector('[data-inventario-pagina-tamano]');
    const personalizada = document.querySelector('[data-inventario-pagina-personalizada]');
    const valor = selector.value === 'personalizada' ? personalizada.value : selector.value;
    const tamano = Number(valor);
    return Number.isInteger(tamano) && tamano >= 1 && tamano <= 50 ? tamano : 7;
}

/* Solicita al servidor una página del inventario con sus filtros actuales */
async function cargarInventario(pagina = 1)
{
    const lista = document.querySelector('[data-inventario-lista]');
    const filtros = obtenerFiltrosInventario();
    const parametros = new URLSearchParams({ modulo: 'inventario', accion: 'listar_inventario', pagina: String(Math.max(1, pagina)), por_pagina: String(obtenerTamanoPaginaInventario()), ...filtros });

    try {
        const respuesta = await fetch(`../api/api.php?${parametros}`);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No fue posible cargar el inventario.');

        const datos = resultado.datos || {};
        renderizarResumenInventario(datos.resumen || {});
        renderizarProductosInventario(datos.productos || []);
        renderizarOpcionesRadioInventario(datos.opciones?.categorias || [], '[data-inventario-categoria-opciones]', 'inventario_categoria', filtros.id_categoria, 'Todas las categorías', 'categoria');
        renderizarOpcionesRadioInventario(datos.opciones?.proveedores || [], '[data-inventario-proveedor-opciones]', 'inventario_proveedor', filtros.id_proveedor, 'Todos los proveedores', 'proveedor');
        renderizarOpcionesRadioInventario(datos.opciones?.estados || [], '[data-inventario-estado-opciones]', 'inventario_estado', filtros.id_estado_producto, 'Todos los estados', 'estado', true);
        renderizarOpcionesRadioInventario(datos.opciones?.stock || [], '[data-inventario-stock-opciones]', 'inventario_stock', filtros.stock || '', 'Todos', 'stock');
        filtrarOpcionesInventario('[data-inventario-categoria-opciones]', document.querySelector('[data-inventario-categoria-busqueda]').value);
        filtrarOpcionesInventario('[data-inventario-proveedor-opciones]', document.querySelector('[data-inventario-proveedor-busqueda]').value);
        filtrarOpcionesInventario('[data-inventario-estado-opciones]', document.querySelector('[data-inventario-estado-busqueda]').value);
        renderizarPaginacionInventario(datos.paginacion || {});

        const paginacion = datos.paginacion || {};
        const inicio = datos.productos?.length ? ((Number(paginacion.pagina_actual) - 1) * Number(paginacion.por_pagina)) + 1 : 0;
        const fin = inicio + (datos.productos?.length || 0) - 1;
        document.querySelector('[data-inventario-contador]').textContent = `Mostrando ${inicio} a ${Math.max(0, fin)} de ${Number(paginacion.total || 0)} productos`;
    } catch (error) {
        lista.innerHTML = `<tr><td colspan="8">${escaparInventario(error.message)}</td></tr>`;
    }
}

/* Reinicia todos los filtros y vuelve a consultar el inventario */
function limpiarFiltrosInventario()
{
    document.querySelector('[data-inventario-busqueda]').value = '';
    document.querySelector('[data-inventario-categoria-busqueda]').value = '';
    document.querySelector('[data-inventario-proveedor-busqueda]').value = '';
    document.querySelector('[data-inventario-estado-busqueda]').value = '';
    document.querySelectorAll('input[name^="inventario_"]').forEach((radio) => {
        const valorTodos = radio.name === 'inventario_stock' ? '' : '0';
        radio.checked = radio.value === valorTodos;
    });
    cerrarPanelesInventario();
    cargarInventario(1);
}

/* Inicializa controles y carga la primera página del inventario */
document.addEventListener('DOMContentLoaded', () => {
    inicializarSelectoresInventario();
    document.querySelector('[data-inventario-busqueda]').addEventListener('input', () => {
        clearTimeout(temporizadorInventario);
        temporizadorInventario = setTimeout(() => cargarInventario(1), 300);
    });
    document.querySelector('[data-inventario-categoria-busqueda]').addEventListener('input', (evento) => filtrarOpcionesInventario('[data-inventario-categoria-opciones]', evento.target.value));
    document.querySelector('[data-inventario-proveedor-busqueda]').addEventListener('input', (evento) => filtrarOpcionesInventario('[data-inventario-proveedor-opciones]', evento.target.value));
    document.querySelector('[data-inventario-estado-busqueda]').addEventListener('input', (evento) => filtrarOpcionesInventario('[data-inventario-estado-opciones]', evento.target.value));
    document.querySelector('[data-inventario-limpiar]').addEventListener('click', limpiarFiltrosInventario);

    const selectorTamanoPagina = document.querySelector('[data-inventario-pagina-tamano]');
    const entradaTamanoPagina = document.querySelector('[data-inventario-pagina-personalizada]');
    selectorTamanoPagina.addEventListener('change', () => {
        entradaTamanoPagina.hidden = selectorTamanoPagina.value !== 'personalizada';
        if (selectorTamanoPagina.value === 'personalizada') entradaTamanoPagina.focus();
        cargarInventario(1);
    });
    entradaTamanoPagina.addEventListener('input', () => {
        clearTimeout(temporizadorPaginaInventario);
        if (obtenerTamanoPaginaInventario() !== Number(entradaTamanoPagina.value)) return;
        temporizadorPaginaInventario = setTimeout(() => cargarInventario(1), 300);
    });

    cargarInventario();
});
