/**
 * registro_compra.js
 *
 * Contiene las interacciones visuales propias del registro de compra
 */

const CLAVE_PRODUCTOS_SOLICITADOS = 'asinco_compra_productos_solicitados_v1';
const CLAVE_PREPARACION_COMPRA = 'asinco_compra_preparacion_v1';
const OPCIONES_UNIDADES_REGISTRO = ['Unidades', 'Rollos', 'Cajas', 'Paquetes', 'Metros', 'Kilogramos', 'Litros'];
let temporizadorBusquedaCoincidencias = null;
let controladorBusquedaCoincidencias = null;
let coincidenciasProductoActuales = new Map();
let productoCoincidenciaSeleccionada = null;
let productoEnEdicionActual = null;
let imagenProductoFormulario = '';
let nombreArchivoImagenFormulario = '';
let idProductoPendienteEliminar = '';
let categoriasProductoRegistro = [];
let categoriasProductoRegistroCargadas = false;
let renderizarListaCategoriasRegistro = null;
let proveedoresSugeridosCompra = [];
let transportistasSugeridosCompra = [];
let proveedoresRecomendadosCompra = [];
let transportistasRecomendadosCompra = [];
let estadoBusquedaProveedoresCompra = { termino: '', pagina: 1, totalPaginas: 1, temporizador: null, controlador: null };
let estadoBusquedaTransportistasCompra = { termino: '', pagina: 1, totalPaginas: 1, temporizador: null, controlador: null };
let estadoTipoCambioDolarCompra = 'sin_consulta';

/* Escapa texto antes de insertarlo en las filas o coincidencias del registro */
function escaparRegistroCompra(valor)
{
    return String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[caracter]));
}

/* Recupera el borrador de productos de la pestaña actual */
function obtenerProductosSolicitadosRegistro()
{
    try {
        const productos = JSON.parse(sessionStorage.getItem(CLAVE_PRODUCTOS_SOLICITADOS) || '[]');
        if (!Array.isArray(productos)) return [];
        const requiereLimpieza = productos.some((producto) =>
            producto && typeof producto === 'object' && Object.prototype.hasOwnProperty.call(producto, 'notas')
        );
        if (!requiereLimpieza) return productos;

        const productosSinNotas = productos.map((producto) => {
            if (!producto || typeof producto !== 'object') return producto;
            const datosProducto = { ...producto };
            delete datosProducto.notas;
            return datosProducto;
        });
        guardarProductosSolicitadosRegistro(productosSinNotas);
        return productosSinNotas;
    } catch (error) {
        return [];
    }
}

/* Guarda los productos solicitados mientras el usuario completa el paso 1 */
function guardarProductosSolicitadosRegistro(productos)
{
    try {
        sessionStorage.setItem(CLAVE_PRODUCTOS_SOLICITADOS, JSON.stringify(productos));
        return true;
    } catch (error) {
        return false;
    }
}

/* Recupera los datos temporales elegidos durante la preparación de la compra */
function obtenerPreparacionCompraRegistro()
{
    try {
        const datos = JSON.parse(sessionStorage.getItem(CLAVE_PREPARACION_COMPRA) || '{}');
        return datos && typeof datos === 'object' ? datos : {};
    } catch (error) {
        return {};
    }
}

/* Guarda proveedores, transportistas, envío y precios mientras avanza el flujo */
function guardarPreparacionCompraRegistro(datos)
{
    try {
        sessionStorage.setItem(CLAVE_PREPARACION_COMPRA, JSON.stringify(datos));
        return true;
    } catch (error) {
        return false;
    }
}

/* Muestra un mensaje en la vista del listado de productos */
function mostrarMensajeListaRegistro(mensaje, esError = false)
{
    const salida = document.querySelector('[data-registro-mensaje-vacio]');
    if (!salida) return;
    salida.textContent = mensaje;
    salida.classList.toggle('registro-mensaje--error', esError);
}

/* Actualiza el desglose y vuelve a dibujar la tabla del paso 1 */
function renderizarSolicitudProductosRegistro()
{
    const tabla = document.querySelector('[data-productos-solicitados]');
    if (!tabla) return;

    const productos = obtenerProductosSolicitadosRegistro();
    const unidades = productos.reduce((total, producto) => total + Math.max(0, Number(producto.cantidad) || 0), 0);
    const nuevos = productos.filter((producto) => !Number(producto.id_producto)).length;

    document.querySelector('[data-resumen-productos]').textContent = productos.length.toLocaleString('es-CL');
    document.querySelector('[data-resumen-productos-texto]').textContent = productos.length === 1 ? 'producto' : 'productos';
    document.querySelector('[data-resumen-unidades]').textContent = unidades.toLocaleString('es-CL');
    document.querySelector('[data-resumen-unidades-texto]').textContent = unidades === 1 ? 'unidad total' : 'unidades totales';
    document.querySelector('[data-resumen-nuevos]').textContent = nuevos.toLocaleString('es-CL');
    document.querySelector('[data-resumen-nuevos-texto]').textContent = nuevos === 1 ? 'producto nuevo detectado' : 'productos nuevos detectados';

    const botonContinuar = document.querySelector('[data-continuar-registro]');
    botonContinuar?.setAttribute('aria-disabled', productos.length ? 'false' : 'true');

    if (!productos.length) {
        tabla.innerHTML = '<tr><td colspan="6">Todavía no se han agregado productos.</td></tr>';
        return;
    }

    const unidadesDisponibles = ['Unidades', 'Rollos', 'Cajas', 'Paquetes', 'Metros', 'Kilogramos', 'Litros', 'Piezas'];
    tabla.innerHTML = productos.map((producto, indice) => {
        const unidad = producto.unidad_medida || 'Unidades';
        const opcionesUnidad = Array.from(new Set([unidad, ...unidadesDisponibles])).map((opcion) =>
            `<option value="${escaparRegistroCompra(opcion)}" ${opcion === unidad ? 'selected' : ''}>${escaparRegistroCompra(opcion)}</option>`
        ).join('');
        return `<tr data-producto-solicitado-row="${escaparRegistroCompra(producto.id)}">
            <td>${indice + 1}</td>
            <td><strong>${escaparRegistroCompra(producto.nombre)}</strong>${producto.id_producto ? '' : '<small class="registro-producto-nuevo">Producto nuevo</small>'}</td>
            <td><input type="number" min="1" step="1" value="${Math.max(1, Number(producto.cantidad) || 1)}" aria-label="Cantidad de ${escaparRegistroCompra(producto.nombre)}" data-producto-solicitado-campo="cantidad"></td>
            <td><select aria-label="Unidad de ${escaparRegistroCompra(producto.nombre)}" data-producto-solicitado-campo="unidad_medida">${opcionesUnidad}</select></td>
            <td>${escaparRegistroCompra(producto.categoria || 'Sin categoría')}</td>
            <td><div class="registro-acciones">
                <a href="ingresar_productos.php?editar=${encodeURIComponent(producto.id)}" aria-label="Editar ${escaparRegistroCompra(producto.nombre)}" title="Editar producto"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                <button type="button" aria-label="Eliminar ${escaparRegistroCompra(producto.nombre)}" title="Eliminar producto" data-eliminar-producto-solicitado="${escaparRegistroCompra(producto.id)}"><i class="bi bi-trash" aria-hidden="true"></i></button>
            </div></td>
        </tr>`;
    }).join('');
}

/* Persiste los cambios rápidos de cantidad y unidad */
function actualizarCampoProductoSolicitado(evento)
{
    const campo = evento.target.closest('[data-producto-solicitado-campo]');
    const fila = evento.target.closest('[data-producto-solicitado-row]');
    if (!campo || !fila) return;

    const productos = obtenerProductosSolicitadosRegistro();
    const producto = productos.find((item) => item.id === fila.dataset.productoSolicitadoRow);
    if (!producto) return;

    const nombreCampo = campo.dataset.productoSolicitadoCampo;
    if (nombreCampo === 'cantidad') {
        const cantidad = Number(campo.value);
        if (!Number.isInteger(cantidad) || cantidad < 1) {
            if (evento.type === 'change') campo.value = Math.max(1, Number(producto.cantidad) || 1);
            return;
        }
        producto.cantidad = cantidad;
    } else {
        producto[nombreCampo] = campo.value.trim();
    }

    if (guardarProductosSolicitadosRegistro(productos)) {
        const unidades = productos.reduce((total, item) => total + (Number(item.cantidad) || 0), 0);
        document.querySelector('[data-resumen-unidades]').textContent = unidades.toLocaleString('es-CL');
        document.querySelector('[data-resumen-unidades-texto]').textContent = unidades === 1 ? 'unidad total' : 'unidades totales';
        mostrarMensajeListaRegistro('');
    } else {
        mostrarMensajeListaRegistro('No se pudieron guardar los cambios en esta pestaña.', true);
    }
}

/* Abre la confirmación de eliminación y conserva el foco para accesibilidad */
function abrirConfirmacionEliminarProducto(idProducto)
{
    const modal = document.querySelector('[data-modal-eliminar-producto]');
    const producto = obtenerProductosSolicitadosRegistro().find((item) => item.id === idProducto);
    if (!modal || !producto) return;

    idProductoPendienteEliminar = idProducto;
    document.querySelector('[data-modal-eliminar-producto-texto]').textContent = `¿Estás seguro de que quieres quitar “${producto.nombre}” de la solicitud?`;
    modal.hidden = false;
    document.body.classList.add('registro-modal-abierto');
    modal.querySelector('[data-cancelar-eliminar-producto]').focus();
}

/* Cierra la confirmación de eliminación */
function cerrarConfirmacionEliminarProducto()
{
    const modal = document.querySelector('[data-modal-eliminar-producto]');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('registro-modal-abierto');
    idProductoPendienteEliminar = '';
}

/* Quita el producto confirmado y actualiza el resumen */
function confirmarEliminarProductoSolicitado()
{
    if (!idProductoPendienteEliminar) return;
    const productos = obtenerProductosSolicitadosRegistro().filter((producto) => producto.id !== idProductoPendienteEliminar);
    if (!guardarProductosSolicitadosRegistro(productos)) {
        cerrarConfirmacionEliminarProducto();
        mostrarMensajeListaRegistro('No se pudo actualizar la solicitud en esta pestaña.', true);
        return;
    }
    cerrarConfirmacionEliminarProducto();
    mostrarMensajeListaRegistro('');
    renderizarSolicitudProductosRegistro();
}

/* Inicializa la tabla dinámica, sus controles rápidos y el modal de eliminación */
function inicializarPasoUnoRegistroCompra()
{
    const tabla = document.querySelector('[data-productos-solicitados]');
    if (!tabla) return;

    renderizarSolicitudProductosRegistro();
    tabla.addEventListener('input', actualizarCampoProductoSolicitado);
    tabla.addEventListener('change', actualizarCampoProductoSolicitado);
    tabla.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-eliminar-producto-solicitado]');
        if (boton) abrirConfirmacionEliminarProducto(boton.dataset.eliminarProductoSolicitado);
    });

    document.querySelector('[data-cancelar-eliminar-producto]')?.addEventListener('click', cerrarConfirmacionEliminarProducto);
    document.querySelector('[data-confirmar-eliminar-producto]')?.addEventListener('click', confirmarEliminarProductoSolicitado);
    document.querySelector('[data-modal-eliminar-producto]')?.addEventListener('click', (evento) => {
        if (evento.target.matches('[data-modal-eliminar-producto]')) cerrarConfirmacionEliminarProducto();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !document.querySelector('[data-modal-eliminar-producto]')?.hidden) {
            cerrarConfirmacionEliminarProducto();
        }
    });
    document.querySelector('[data-continuar-registro]')?.addEventListener('click', (evento) => {
        if (!obtenerProductosSolicitadosRegistro().length) {
            evento.preventDefault();
            mostrarMensajeListaRegistro('Agrega al menos un producto antes de continuar.', true);
        }
    });
}

/* Actualiza el texto de la miniatura del archivo opcional */
function actualizarVistaPreviaImagenProducto(imagen, nombreArchivo = '')
{
    const vistaPrevia = document.querySelector('[data-producto-imagen-preview]');
    const texto = document.querySelector('[data-producto-imagen-texto]');
    if (!vistaPrevia || !texto) return;

    if (imagen) {
        vistaPrevia.src = imagen;
        vistaPrevia.hidden = false;
        texto.textContent = nombreArchivo || 'Imagen seleccionada';
    } else {
        vistaPrevia.removeAttribute('src');
        vistaPrevia.hidden = true;
        texto.textContent = 'Seleccionar una imagen';
    }
}

/* Filtra y presenta las opciones de cualquier lista editable del formulario */
function renderizarOpcionesComboboxRegistro(campo, lista, opciones, mensajeVacio)
{
    if (!campo || !lista || lista.hidden) return;

    const termino = campo.value.trim().toLocaleLowerCase();
    const coincidencias = opciones().filter((opcion) =>
        opcion.toLocaleLowerCase().includes(termino)
    );

    if (!coincidencias.length) {
        lista.innerHTML = `<p class="campo-registro__opciones-vacias">${escaparRegistroCompra(mensajeVacio())}</p>`;
        return;
    }

    lista.innerHTML = coincidencias.map((opcion) =>
        `<button type="button" role="option" aria-selected="false" class="campo-registro__opcion" data-opcion-combobox="${escaparRegistroCompra(opcion)}">${escaparRegistroCompra(opcion)}</button>`
    ).join('');
}

/* Configura un combobox editable con opciones filtrables */
function configurarComboboxRegistro(campo, contenedor, lista, obtenerOpciones, obtenerMensajeVacio, alModificar = null, alSeleccionar = null)
{
    if (!campo || !contenedor || !lista) return null;

    const renderizar = () => renderizarOpcionesComboboxRegistro(campo, lista, obtenerOpciones, obtenerMensajeVacio);
    const cerrar = () => {
        lista.hidden = true;
        campo.setAttribute('aria-expanded', 'false');
    };
    const abrir = () => {
        lista.hidden = false;
        campo.setAttribute('aria-expanded', 'true');
        renderizar();
    };

    campo.addEventListener('focus', abrir);
    campo.addEventListener('click', abrir);
    campo.addEventListener('input', () => {
        abrir();
        if (alModificar) alModificar();
    });
    campo.addEventListener('keydown', (evento) => {
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            abrir();
            lista.querySelector('[data-opcion-combobox]')?.focus();
        } else if (evento.key === 'Escape') {
            cerrar();
        }
    });
    lista.addEventListener('mousedown', (evento) => evento.preventDefault());
    lista.addEventListener('click', (evento) => {
        const opcion = evento.target.closest('[data-opcion-combobox]');
        if (!opcion) return;
        campo.value = opcion.dataset.opcionCombobox;
        campo.focus();
        cerrar();
        if (alSeleccionar) alSeleccionar(campo.value);
    });
    document.addEventListener('click', (evento) => {
        if (!contenedor.contains(evento.target)) cerrar();
    });

    return { renderizar, cerrar, abrir };
}

/* Actualiza la lista de categorías cuando las opciones llegan desde la API */
function renderizarOpcionesCategoriasRegistro()
{
    if (renderizarListaCategoriasRegistro) renderizarListaCategoriasRegistro();
}

/* Consulta el catálogo y actualiza las sugerencias según el nombre y la categoría */
async function buscarCoincidenciasProductoRegistro()
{
    const nombre = document.querySelector('[data-producto-nombre]').value.trim();
    const categoria = document.querySelector('[data-producto-categoria]').value.trim();
    const lista = document.querySelector('[data-coincidencias-lista]');
    const mensaje = document.querySelector('[data-coincidencias-mensaje]');

    if (controladorBusquedaCoincidencias) controladorBusquedaCoincidencias.abort();
    controladorBusquedaCoincidencias = new AbortController();

    lista.innerHTML = '<p class="coincidencias__vacio">Buscando productos similares...</p>';
    try {
        const parametros = new URLSearchParams({
            modulo: 'inventario',
            accion: 'buscar_coincidencias_productos',
            nombre,
            categoria,
        });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, {
            signal: controladorBusquedaCoincidencias.signal,
            headers: { Accept: 'application/json' },
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron buscar coincidencias.');

        const datos = resultado.datos || {};
        categoriasProductoRegistro = Array.isArray(datos.categorias) ? datos.categorias : [];
        categoriasProductoRegistroCargadas = true;
        renderizarOpcionesCategoriasRegistro();

        const coincidencias = Array.isArray(datos.coincidencias) ? datos.coincidencias : [];
        coincidenciasProductoActuales = new Map(coincidencias.map((producto) => [String(producto.id_producto), producto]));
        if (!nombre && !categoria) {
            lista.innerHTML = '<p class="coincidencias__vacio">Ingresa un nombre o una categoría para buscar productos similares.</p>';
            mensaje.textContent = 'Las coincidencias te permiten completar automáticamente los datos del catálogo.';
            return;
        }
        if (!coincidencias.length) {
            lista.innerHTML = '<p class="coincidencias__vacio">No se encontraron productos similares. Puedes completar los datos manualmente.</p>';
            mensaje.textContent = 'No hay coincidencias para los criterios ingresados.';
            return;
        }

        mensaje.textContent = 'Selecciona un producto para completar automáticamente sus datos; luego puedes ajustarlos.';
        lista.innerHTML = coincidencias.map((producto) => {
            const seleccionado = Number(productoCoincidenciaSeleccionada?.id_producto) === Number(producto.id_producto);
            const imagen = producto.imagen || obtenerRutaImagenSinRegistro('producto');
            const stockDisponible = Math.max(0, Number(producto.stock_disponible) || 0);
            const claseStock = stockDisponible > 0 ? 'coincidencia__stock' : 'coincidencia__stock coincidencia__stock--agotado';
            return `<label class="coincidencia ${seleccionado ? 'coincidencia--activa' : ''}">
                <input type="radio" name="coincidencia" value="${Number(producto.id_producto)}" ${seleccionado ? 'checked' : ''} data-seleccionar-coincidencia>
                <span class="coincidencia__imagen"><img src="${escaparRegistroCompra(imagen)}" alt="" data-imagen-respaldo></span>
                <span><strong>${escaparRegistroCompra(producto.nombre)}</strong><small>Código: ${escaparRegistroCompra(producto.codigo_producto)} | Categoría: ${escaparRegistroCompra(producto.categoria)}</small><small class="${claseStock}">Stock disponible: ${stockDisponible.toLocaleString('es-CL')} ${stockDisponible === 1 ? 'unidad' : 'unidades'}</small></span>
            </label>`;
        }).join('');
    } catch (error) {
        if (error.name === 'AbortError') return;
        lista.innerHTML = '<p class="coincidencias__vacio">No fue posible cargar las coincidencias. Puedes completar los datos manualmente.</p>';
        mensaje.textContent = error.message;
    }
}

/* Inicializa el formulario para agregar un producto o editar uno de la solicitud */
function inicializarFormularioProductoRegistroCompra()
{
    const formulario = document.querySelector('[data-formulario-agregar-producto]');
    if (!formulario) return;

    const campoNombre = document.querySelector('[data-producto-nombre]');
    const campoCantidad = document.querySelector('[data-producto-cantidad]');
    const campoUnidad = document.querySelector('[data-producto-unidad]');
    const campoCodigo = document.querySelector('[data-producto-codigo]');
    const campoCategoria = document.querySelector('[data-producto-categoria]');
    const campoDescripcion = document.querySelector('[data-producto-descripcion]');
    const mensaje = document.querySelector('[data-producto-mensaje]');
    const botonGuardar = document.querySelector('[data-producto-guardar]');
    const archivo = document.querySelector('[data-producto-archivo]');
    const idEdicion = new URLSearchParams(window.location.search).get('editar');

    productoEnEdicionActual = idEdicion
        ? obtenerProductosSolicitadosRegistro().find((producto) => producto.id === idEdicion) || null
        : null;
    if (productoEnEdicionActual) {
        campoNombre.value = productoEnEdicionActual.nombre || '';
        campoCantidad.value = Math.max(1, Number(productoEnEdicionActual.cantidad) || 1);
        campoUnidad.value = productoEnEdicionActual.unidad_medida || '';
        campoCodigo.value = productoEnEdicionActual.codigo_producto || '';
        campoCategoria.value = productoEnEdicionActual.categoria || '';
        campoDescripcion.value = productoEnEdicionActual.descripcion || '';
        productoCoincidenciaSeleccionada = productoEnEdicionActual.producto_catalogo || null;
        imagenProductoFormulario = productoEnEdicionActual.imagen || productoCoincidenciaSeleccionada?.imagen || '';
        nombreArchivoImagenFormulario = productoEnEdicionActual.nombre_archivo || productoCoincidenciaSeleccionada?.nombre_archivo || '';
        actualizarVistaPreviaImagenProducto(imagenProductoFormulario, nombreArchivoImagenFormulario || 'Imagen del producto');
        botonGuardar.querySelector('span').textContent = 'Guardar cambios';
    } else {
        productoCoincidenciaSeleccionada = null;
        imagenProductoFormulario = '';
        nombreArchivoImagenFormulario = '';
    }

    const solicitarBusqueda = () => {
        clearTimeout(temporizadorBusquedaCoincidencias);
        temporizadorBusquedaCoincidencias = setTimeout(buscarCoincidenciasProductoRegistro, 300);
    };
    campoNombre.addEventListener('input', solicitarBusqueda);
    const contenedorUnidad = document.querySelector('[data-unidad-combobox]');
    const listaUnidades = document.querySelector('[data-unidades-medida]');
    configurarComboboxRegistro(
        campoUnidad,
        contenedorUnidad,
        listaUnidades,
        () => OPCIONES_UNIDADES_REGISTRO,
        () => 'Sin coincidencias. Puedes escribir una unidad nueva.'
    );

    const contenedorCategorias = document.querySelector('[data-categoria-combobox]');
    const listaCategorias = document.querySelector('[data-categorias-producto]');
    renderizarListaCategoriasRegistro = configurarComboboxRegistro(
        campoCategoria,
        contenedorCategorias,
        listaCategorias,
        () => categoriasProductoRegistro,
        () => !categoriasProductoRegistroCargadas
            ? 'Cargando categorías…'
            : (categoriasProductoRegistro.length
                ? 'Sin coincidencias. Puedes escribir una categoría nueva.'
                : 'Aún no hay categorías registradas. Puedes escribir una nueva.'),
        solicitarBusqueda,
        solicitarBusqueda
    )?.renderizar || null;
    solicitarBusqueda();

    document.querySelector('[data-coincidencias-lista]').addEventListener('change', (evento) => {
        const radio = evento.target.closest('[data-seleccionar-coincidencia]');
        if (!radio) return;

        const producto = coincidenciasProductoActuales.get(radio.value);
        if (!producto) return;
        productoCoincidenciaSeleccionada = producto;
        campoNombre.value = producto.nombre;
        campoCodigo.value = producto.codigo_producto;
        campoCategoria.value = producto.categoria;
        campoUnidad.value = producto.unidad_medida || '';
        campoDescripcion.value = producto.descripcion || '';
        imagenProductoFormulario = producto.imagen || '';
        nombreArchivoImagenFormulario = producto.nombre_archivo || '';
        actualizarVistaPreviaImagenProducto(imagenProductoFormulario, nombreArchivoImagenFormulario || 'Imagen del catálogo');
        solicitarBusqueda();
    });

    document.querySelector('[data-producto-seleccionar-imagen]').addEventListener('click', () => archivo.click());
    archivo.addEventListener('change', () => {
        const imagen = archivo.files?.[0];
        if (!imagen) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(imagen.type) || imagen.size > 1024 * 1024) {
            archivo.value = '';
            mensaje.textContent = 'Selecciona una imagen JPG, PNG o WEBP de hasta 1 MB.';
            mensaje.classList.add('registro-mensaje--error');
            return;
        }
        const lector = new FileReader();
        lector.onload = () => {
            imagenProductoFormulario = String(lector.result || '');
            nombreArchivoImagenFormulario = imagen.name;
            actualizarVistaPreviaImagenProducto(imagenProductoFormulario, imagen.name);
            mensaje.textContent = '';
            mensaje.classList.remove('registro-mensaje--error');
        };
        lector.readAsDataURL(imagen);
    });

    formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        mensaje.classList.remove('registro-mensaje--error');
        const nombre = campoNombre.value.trim();
        const cantidad = Number(campoCantidad.value);
        const unidad = campoUnidad.value.trim();
        const codigo = campoCodigo.value.trim();
        const categoria = campoCategoria.value.trim();
        if (!nombre || !Number.isInteger(cantidad) || cantidad < 1 || !unidad || !codigo || !categoria) {
            mensaje.textContent = 'Completa nombre, cantidad válida, unidad, código y categoría.';
            mensaje.classList.add('registro-mensaje--error');
            return;
        }

        const productoBase = productoCoincidenciaSeleccionada;
        const coincideConCatalogo = productoBase
            && productoBase.nombre.trim().toLocaleLowerCase() === nombre.toLocaleLowerCase()
            && productoBase.codigo_producto.trim().toLocaleLowerCase() === codigo.toLocaleLowerCase()
            && productoBase.categoria.trim().toLocaleLowerCase() === categoria.toLocaleLowerCase();
        const productos = obtenerProductosSolicitadosRegistro();
        const productoCatalogoGuardado = coincideConCatalogo ? {
            id_producto: Number(productoBase.id_producto),
            nombre: productoBase.nombre,
            codigo_producto: productoBase.codigo_producto,
            categoria: productoBase.categoria,
            unidad_medida: productoBase.unidad_medida,
            descripcion: productoBase.descripcion,
        } : null;
        const producto = {
            id: productoEnEdicionActual?.id || `${Date.now()}-${Math.random().toString(16).slice(2)}`,
            id_producto: coincideConCatalogo ? Number(productoBase.id_producto) : null,
            producto_catalogo: productoCatalogoGuardado,
            nombre,
            codigo_producto: codigo,
            categoria,
            unidad_medida: unidad,
            cantidad,
            descripcion: campoDescripcion.value.trim(),
            imagen: imagenProductoFormulario || '',
            nombre_archivo: nombreArchivoImagenFormulario || '',
        };
        const indiceExistente = productos.findIndex((item) => item.id === producto.id);
        if (indiceExistente >= 0) productos[indiceExistente] = producto;
        else productos.push(producto);

        if (!guardarProductosSolicitadosRegistro(productos)) {
            mensaje.textContent = 'No se pudo guardar el borrador. Reduce el tamaño de la imagen o intenta nuevamente.';
            mensaje.classList.add('registro-mensaje--error');
            return;
        }
        window.location.href = 'registro_compra.php';
    });
}

/* Formatea los valores que se muestran en el resumen de preparación */
function formatearMontoPrepararCompra(valor)
{
    return '$' + (Number(valor) || 0).toLocaleString('es-CL', { maximumFractionDigits: 2 });
}

/* Formatea importes con la moneda elegida en el paso de confirmación */
function formatearMontoConfirmarCompra(valor, codigoForzado = '')
{
    const monedaSeleccionada = document.querySelector('[data-confirmar-moneda]')?.selectedOptions?.[0];
    const codigo = codigoForzado || monedaSeleccionada?.dataset.codigo || obtenerPreparacionCompraRegistro().codigo_moneda || 'CLP';
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: codigo }).format(Number(valor) || 0);
}

/* Agrega el código de moneda al importe para identificar el total sin ambigüedad */
function formatearMontoConCodigoConfirmarCompra(valor, codigo)
{
    const decimales = codigo === 'CLP' ? 0 : 2;
    const importe = new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    }).format(Number(valor) || 0);
    return `${codigo}$${importe}`;
}

/* Actualiza el total estimado con precios unitarios y costo de envío */
function actualizarTotalPrepararCompra()
{
    const productos = obtenerProductosSolicitadosRegistro();
    const preparacion = obtenerPreparacionCompraRegistro();
    const subtotal = productos.reduce((total, producto) => {
        const precio = Number(preparacion.precios_unitarios?.[producto.id]) || 0;
        return total + (Number(producto.cantidad) || 0) * precio;
    }, 0);
    const total = subtotal + (Number(preparacion.costo_envio) || 0);
    const salida = document.querySelector('[data-preparar-total]');
    if (salida) salida.textContent = formatearMontoPrepararCompra(total);
}

/* Dibuja el resumen del paso final con los productos y costos ya preparados */
function renderizarResumenConfirmarCompra()
{
    const tabla = document.querySelector('[data-confirmar-productos]');
    if (!tabla) return;

    const productos = obtenerProductosSolicitadosRegistro();
    const preparacion = obtenerPreparacionCompraRegistro();
    const monedaSeleccionada = document.querySelector('[data-confirmar-moneda]')?.selectedOptions?.[0];
    const codigoMoneda = monedaSeleccionada?.dataset.codigo || preparacion.codigo_moneda || 'CLP';
    const tipoCambio = Number(preparacion.tipo_cambio_usd_clp) || 0;
    const convertirAClp = codigoMoneda === 'USD' && tipoCambio > 0;
    const monedaResumen = convertirAClp ? 'CLP' : codigoMoneda;
    const subtotal = productos.reduce((total, producto) => {
        const cantidad = Math.max(1, Number(producto.cantidad) || 1);
        const precio = Number(preparacion.precios_unitarios?.[producto.id]) || 0;
        return total + cantidad * precio;
    }, 0);
    const unidades = productos.reduce((total, producto) => total + Math.max(1, Number(producto.cantidad) || 1), 0);
    const costoEnvio = Math.max(0, Number(preparacion.costo_envio) || 0);

    const campoProveedor = document.querySelector('[data-confirmar-proveedor]');
    const campoTransportista = document.querySelector('[data-confirmar-transportista]');
    if (campoProveedor) campoProveedor.value = preparacion.nombre_proveedor || '';
    if (campoTransportista) campoTransportista.value = preparacion.nombre_transportista || '';

    const totalProductos = document.querySelector('[data-resumen-confirmar-productos]');
    const totalUnidades = document.querySelector('[data-resumen-confirmar-unidades]');
    const resumenProveedor = document.querySelector('[data-resumen-proveedor]');
    const resumenTransportista = document.querySelector('[data-resumen-transportista]');
    const resumenSubtotal = document.querySelector('[data-resumen-subtotal]');
    const resumenEnvio = document.querySelector('[data-resumen-costo-envio]');
    const resumenTotal = document.querySelector('[data-resumen-total]');
    const fechaEstimada = document.querySelector('[data-confirmar-fecha-estimada]')?.value || '';
    const resumenEntrega = document.querySelector('[data-resumen-entrega]');
    if (totalProductos) totalProductos.textContent = productos.length.toLocaleString('es-CL');
    if (totalUnidades) totalUnidades.textContent = unidades.toLocaleString('es-CL');
    if (resumenProveedor) resumenProveedor.textContent = preparacion.nombre_proveedor || '--';
    if (resumenTransportista) resumenTransportista.textContent = preparacion.nombre_transportista || '--';
    if (resumenSubtotal) resumenSubtotal.textContent = formatearMontoConCodigoConfirmarCompra(
        convertirAClp ? subtotal * tipoCambio : subtotal,
        monedaResumen
    );
    if (resumenEnvio) resumenEnvio.textContent = formatearMontoConCodigoConfirmarCompra(
        convertirAClp ? costoEnvio * tipoCambio : costoEnvio,
        monedaResumen
    );
    if (resumenTotal) {
        resumenTotal.textContent = formatearMontoConCodigoConfirmarCompra(
            convertirAClp ? (subtotal + costoEnvio) * tipoCambio : subtotal + costoEnvio,
            monedaResumen
        );
    }
    if (resumenEntrega) {
        const [anio, mes, dia] = fechaEstimada.split('-');
        resumenEntrega.textContent = anio && mes && dia ? `${dia}-${mes}-${anio}` : 'Por confirmar';
    }

    const notaTipoCambio = document.querySelector('[data-resumen-tipo-cambio]');
    if (notaTipoCambio) {
        notaTipoCambio.hidden = codigoMoneda !== 'USD';
        if (codigoMoneda === 'USD') {
            if (tipoCambio > 0) {
                const fecha = String(preparacion.fecha_tipo_cambio || '').split('-').reverse().join('/');
                const tasaFormateada = formatearMontoConfirmarCompra(tipoCambio, 'CLP');
                const avisoActualizacion = estadoTipoCambioDolarCompra === 'error'
                    ? ' No se pudo actualizar; se mantiene la tasa guardada.'
                    : '';
                notaTipoCambio.textContent = `Resumen convertido a CLP con dólar observado: USD 1 = ${tasaFormateada}${fecha ? ` (${fecha})` : ''}.${avisoActualizacion}`;
                if (String(preparacion.origen_tipo_cambio || '').toLowerCase().includes('indicadores.cl')) {
                    const enlaceFuente = document.createElement('a');
                    enlaceFuente.href = 'https://indicadores.cl/api/';
                    enlaceFuente.target = '_blank';
                    enlaceFuente.rel = 'noopener noreferrer';
                    enlaceFuente.textContent = 'indicadores.cl';
                    notaTipoCambio.append(document.createTextNode(' Fuente: '), enlaceFuente);
                }
            } else if (estadoTipoCambioDolarCompra === 'cargando') {
                notaTipoCambio.textContent = 'Consultando el dólar observado para convertir el resumen a CLP...';
            } else if (estadoTipoCambioDolarCompra === 'error') {
                notaTipoCambio.textContent = 'No se pudo obtener el dólar observado. Los importes del resumen se mantienen en USD.';
            } else {
                notaTipoCambio.textContent = 'La conversión a CLP se calculará al consultar el dólar observado.';
            }
        }
    }

    if (!productos.length) {
        tabla.innerHTML = '<tr><td colspan="7">No hay productos en la solicitud.</td></tr>';
        return;
    }

    tabla.innerHTML = productos.map((producto, indice) => {
        const cantidad = Math.max(1, Number(producto.cantidad) || 1);
        const precio = Number(preparacion.precios_unitarios?.[producto.id]) || 0;
        const imagen = producto.imagen || obtenerRutaImagenSinRegistro('producto');
        return `<tr>
            <td>${indice + 1}</td>
            <td><span class="preparar-imagen"><img src="${escaparRegistroCompra(imagen)}" alt="Imagen de ${escaparRegistroCompra(producto.nombre)}" data-imagen-respaldo></span></td>
            <td><strong>${escaparRegistroCompra(producto.nombre)}</strong><small class="preparar-producto-codigo">${escaparRegistroCompra(producto.codigo_producto || '')}</small></td>
            <td>${cantidad.toLocaleString('es-CL')}</td>
            <td>${escaparRegistroCompra(producto.unidad_medida || 'Unidades')}</td>
            <td>${formatearMontoConfirmarCompra(precio)}</td>
            <td>${formatearMontoConfirmarCompra(cantidad * precio)}</td>
        </tr>`;
    }).join('');
}

/* Carga desde la API usuarios, monedas y el siguiente código libre de compra */
async function cargarCatalogosConfirmacionCompra()
{
    const responsable = document.querySelector('[data-confirmar-responsable]');
    const moneda = document.querySelector('[data-confirmar-moneda]');
    const codigoCompra = document.querySelector('[data-confirmar-codigo-compra]');
    const mensaje = document.querySelector('[data-confirmar-mensaje]');

    try {
        const parametros = new URLSearchParams({ modulo: 'registro_compra', accion: 'obtener_datos_confirmacion_compra' });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, { headers: { Accept: 'application/json' } });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar los datos de confirmación.');

        const datos = resultado.datos || {};
        const preparacion = obtenerPreparacionCompraRegistro();
        const responsables = Array.isArray(datos.responsables) ? datos.responsables : [];
        const monedas = Array.isArray(datos.monedas) ? datos.monedas : [];

        if (responsable) {
            const opciones = responsables.map((usuario) => {
                const nombre = `${usuario.nombre || ''} ${usuario.apellido || ''}`.trim();
                const etiqueta = usuario.rol ? `${nombre} (${usuario.rol})` : nombre;
                const seleccionado = Number(usuario.id_usuario) === Number(preparacion.id_responsable_compra);
                return `<option value="${Number(usuario.id_usuario)}" ${seleccionado ? 'selected' : ''}>${escaparRegistroCompra(etiqueta)}</option>`;
            }).join('');
            responsable.innerHTML = `<option value="">${responsables.length ? 'Seleccionar responsable' : 'No hay responsables activos'}</option>${opciones}`;
        }

        if (moneda) {
            const opciones = monedas.map((item) => {
                const seleccionado = Number(item.id_moneda) === Number(preparacion.id_moneda);
                return `<option value="${Number(item.id_moneda)}" data-codigo="${escaparRegistroCompra(item.codigo)}" ${seleccionado ? 'selected' : ''}>${escaparRegistroCompra(item.codigo)} - ${escaparRegistroCompra(item.nombre)}</option>`;
            }).join('');
            moneda.innerHTML = `<option value="">${monedas.length ? 'Seleccionar moneda' : 'No hay monedas CLP/USD registradas'}</option>${opciones}`;
        }

        if (codigoCompra) codigoCompra.value = datos.codigo_compra || '';
        renderizarResumenConfirmarCompra();
    } catch (error) {
        if (responsable) responsable.innerHTML = '<option value="">No se pudieron cargar los responsables</option>';
        if (moneda) moneda.innerHTML = '<option value="">No se pudieron cargar las monedas</option>';
        if (mensaje) {
            mensaje.textContent = error.message;
            mensaje.classList.add('registro-mensaje--error');
        }
    }
}

const CLAVE_CACHE_TIPO_CAMBIO_COMPRA = 'asinco_tipo_cambio_usd_clp';

/* Lee una cotización local solo si su fecha es reciente y el valor es válido */
function leerCacheTipoCambioCompra(edadMaximaMs)
{
    try {
        const guardada = JSON.parse(localStorage.getItem(CLAVE_CACHE_TIPO_CAMBIO_COMPRA) || 'null');
        const valor = Number(guardada?.valor);
        const fecha = String(guardada?.fecha || '');
        const guardadoEn = Number(guardada?.guardado_en);
        if (!Number.isFinite(valor) || valor <= 0 || !/^\d{4}-\d{2}-\d{2}$/.test(fecha) || !Number.isFinite(guardadoEn)) {
            return null;
        }

        const fechaTasa = new Date(`${fecha}T00:00:00`);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const diasDesdeTasa = Math.floor((hoy.getTime() - fechaTasa.getTime()) / 86400000);
        const edadCache = Date.now() - guardadoEn;
        if (diasDesdeTasa < 0 || diasDesdeTasa > 7 || edadCache < 0 || edadCache > edadMaximaMs) {
            return null;
        }

        return {
            valor,
            fecha,
            origen: String(guardada.origen || 'mindicador.cl'),
            guardado_en: guardadoEn
        };
    } catch (error) {
        return null;
    }
}

/* Guarda la última tasa válida para mantener el resumen ante caídas temporales */
function guardarCacheTipoCambioCompra(datos)
{
    try {
        localStorage.setItem(CLAVE_CACHE_TIPO_CAMBIO_COMPRA, JSON.stringify({
            valor: Number(datos.valor),
            fecha: String(datos.fecha),
            origen: String(datos.origen || 'mindicador.cl'),
            guardado_en: Date.now()
        }));
    } catch (error) {
        // El cálculo sigue disponible aunque el navegador bloquee el almacenamiento local
    }
}

/* Obtiene la cotización del servidor y recurre a la última tasa local ante una caída */
async function consultarTipoCambioDolarCompra()
{
    const cacheReciente = leerCacheTipoCambioCompra(60 * 60 * 1000);
    if (cacheReciente) {
        return { ...cacheReciente, cache_reciente: true };
    }

    let errorConsultaDirecta = null;
    let temporizadorDirecto = null;

    try {
        const controlador = new AbortController();
        temporizadorDirecto = setTimeout(() => controlador.abort(), 8000);
        const respuesta = await fetch('https://mindicador.cl/api/dolar', {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: controlador.signal
        });
        clearTimeout(temporizadorDirecto);
        if (!respuesta.ok) throw new Error('mindicador.cl no respondió correctamente.');

        const datos = await respuesta.json();
        const observacion = Array.isArray(datos.serie)
            ? datos.serie.find((item) => Number(item.valor) > 0 && item.fecha)
            : null;
        if (!observacion) throw new Error('mindicador.cl no devolvió una cotización válida.');

        return {
            valor: Number(observacion.valor),
            fecha: String(observacion.fecha).slice(0, 10),
            origen: 'mindicador.cl'
        };
    } catch (error) {
        if (temporizadorDirecto) clearTimeout(temporizadorDirecto);
        errorConsultaDirecta = error;
    }

    let temporizadorApi = null;
    try {
        const parametros = new URLSearchParams({ modulo: 'registro_compra', accion: 'obtener_tipo_cambio_dolar' });
        const controlador = new AbortController();
        temporizadorApi = setTimeout(() => controlador.abort(), 14000);
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, {
            headers: { Accept: 'application/json' },
            signal: controlador.signal
        });
        clearTimeout(temporizadorApi);
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo consultar el dólar observado.');
        return resultado.datos;
    } catch (error) {
        if (temporizadorApi) clearTimeout(temporizadorApi);
        const cacheDisponible = leerCacheTipoCambioCompra(7 * 24 * 60 * 60 * 1000);
        if (cacheDisponible) {
            return {
                ...cacheDisponible,
                origen: `${cacheDisponible.origen} (caché local)`,
                desde_cache: true
            };
        }
        throw errorConsultaDirecta || error;
    }
}

/* Consulta y conserva la cotización USD/CLP mientras se prepara el resumen */
async function cargarTipoCambioDolarCompra()
{
    const preparacionActual = obtenerPreparacionCompraRegistro();
    if (preparacionActual.codigo_moneda !== 'USD') return;

    estadoTipoCambioDolarCompra = 'cargando';
    renderizarResumenConfirmarCompra();

    try {
        const datos = await consultarTipoCambioDolarCompra();
        const tipoCambio = Number(datos.valor);
        const preparacion = obtenerPreparacionCompraRegistro();
        if (preparacion.codigo_moneda !== 'USD') return;
        if (!Number.isFinite(tipoCambio) || tipoCambio <= 0 || !datos.fecha) throw new Error('La cotización recibida no es válida.');

        preparacion.tipo_cambio_usd_clp = tipoCambio;
        preparacion.fecha_tipo_cambio = datos.fecha;
        preparacion.origen_tipo_cambio = datos.origen || 'mindicador.cl';
        guardarPreparacionCompraRegistro(preparacion);
        if (!datos.cache_reciente && !datos.desde_cache) guardarCacheTipoCambioCompra(datos);
        estadoTipoCambioDolarCompra = datos.desde_cache ? 'error' : 'listo';
    } catch (error) {
        if (obtenerPreparacionCompraRegistro().codigo_moneda === 'USD') estadoTipoCambioDolarCompra = 'error';
    }

    renderizarResumenConfirmarCompra();
}

/* Conserva las selecciones de responsable y moneda en el borrador de la compra */
function inicializarSeleccionesConfirmarCompra()
{
    const responsable = document.querySelector('[data-confirmar-responsable]');
    const moneda = document.querySelector('[data-confirmar-moneda]');
    const fechaEstimada = document.querySelector('[data-confirmar-fecha-estimada]');
    const preparacionInicial = obtenerPreparacionCompraRegistro();

    if (fechaEstimada) {
        fechaEstimada.value = preparacionInicial.fecha_estimada_llegada || '';
        fechaEstimada.addEventListener('change', () => {
            const preparacion = obtenerPreparacionCompraRegistro();
            preparacion.fecha_estimada_llegada = fechaEstimada.value;
            guardarPreparacionCompraRegistro(preparacion);
            renderizarResumenConfirmarCompra();
        });
    }

    responsable?.addEventListener('change', () => {
        const preparacion = obtenerPreparacionCompraRegistro();
        preparacion.id_responsable_compra = Number(responsable.value) || null;
        guardarPreparacionCompraRegistro(preparacion);
    });

    moneda?.addEventListener('change', () => {
        const preparacion = obtenerPreparacionCompraRegistro();
        const seleccion = moneda.selectedOptions[0];
        preparacion.id_moneda = Number(moneda.value) || null;
        preparacion.codigo_moneda = seleccion?.dataset.codigo || '';
        guardarPreparacionCompraRegistro(preparacion);
        estadoTipoCambioDolarCompra = 'sin_consulta';
        renderizarResumenConfirmarCompra();
        if (preparacion.codigo_moneda === 'USD') cargarTipoCambioDolarCompra();
    });
}

/* Envía al servidor todos los datos confirmados del borrador de compra */
async function enviarRegistroCompra()
{
    const mensaje = document.querySelector('[data-confirmar-mensaje]');
    const boton = document.querySelector('[data-registrar-compra]');
    const productos = obtenerProductosSolicitadosRegistro();
    const preparacion = obtenerPreparacionCompraRegistro();
    const fechaCompra = document.querySelector('[data-confirmar-fecha-compra]')?.value || '';
    const responsable = document.querySelector('[data-confirmar-responsable]');
    const moneda = document.querySelector('[data-confirmar-moneda]');

    if (!productos.length) {
        if (mensaje) {
            mensaje.textContent = 'Agrega al menos un producto antes de registrar la compra.';
            mensaje.classList.add('registro-mensaje--error');
        }
        return;
    }
    if (!fechaCompra || !preparacion.id_proveedor || !preparacion.id_transportista || !responsable?.value || !moneda?.value) {
        if (mensaje) {
            mensaje.textContent = 'Completa los datos obligatorios y selecciona proveedor, transportista, responsable y moneda.';
            mensaje.classList.add('registro-mensaje--error');
        }
        return;
    }

    const precios = preparacion.precios_unitarios || {};
    const productosCompra = [];
    for (const producto of productos) {
        const precio = precios[producto.id];
        if (precio === undefined || precio === null || precio === '' || !Number.isFinite(Number(precio)) || Number(precio) <= 0) {
            if (mensaje) {
                mensaje.textContent = `Ingresa un precio unitario válido para ${producto.nombre}.`;
                mensaje.classList.add('registro-mensaje--error');
            }
            return;
        }
        productosCompra.push({
            id_producto: Number(producto.id_producto) || 0,
            nombre: producto.nombre,
            codigo_producto: producto.codigo_producto,
            categoria: producto.categoria,
            unidad_medida: producto.unidad_medida,
            cantidad: Number(producto.cantidad),
            descripcion: producto.descripcion || '',
            imagen: producto.imagen || '',
            nombre_archivo: producto.nombre_archivo || '',
            precio_unitario: Number(precio)
        });
    }

    const datos = {
        id_proveedor: Number(preparacion.id_proveedor),
        id_transportista: Number(preparacion.id_transportista),
        id_responsable_compra: Number(responsable.value),
        id_moneda: Number(moneda.value),
        codigo_moneda: moneda.selectedOptions?.[0]?.dataset.codigo || preparacion.codigo_moneda || '',
        fecha_compra: fechaCompra,
        fecha_estimada_llegada: document.querySelector('[data-confirmar-fecha-estimada]')?.value || '',
        numero_pedido: document.querySelector('[data-confirmar-pedido]')?.value.trim() || '',
        observaciones: document.querySelector('[data-confirmar-observaciones]')?.value.trim() || '',
        costo_envio: Number(preparacion.costo_envio) || 0,
        tipo_cambio_usd_clp: Number(preparacion.tipo_cambio_usd_clp) || null,
        fecha_tipo_cambio: preparacion.fecha_tipo_cambio || '',
        origen_tipo_cambio: preparacion.origen_tipo_cambio || '',
        productos: productosCompra
    };

    if (mensaje) {
        mensaje.textContent = 'Registrando la compra y sus productos...';
        mensaje.classList.remove('registro-mensaje--error');
    }
    if (boton) {
        boton.disabled = true;
        boton.querySelector('span').textContent = 'Registrando...';
    }

    try {
        const formulario = new FormData();
        formulario.append('datos', JSON.stringify(datos));
        const parametros = new URLSearchParams({ modulo: 'registro_compra', accion: 'registrar_compra' });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: formulario
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) {
            throw new Error(resultado.mensaje || 'No fue posible registrar la compra.');
        }

        sessionStorage.removeItem(CLAVE_PRODUCTOS_SOLICITADOS);
        sessionStorage.removeItem(CLAVE_PREPARACION_COMPRA);
        window.location.href = `detalle_compra.php?id_compra=${encodeURIComponent(resultado.datos.id_compra)}`;
    } catch (error) {
        if (mensaje) {
            mensaje.textContent = error.message || 'No fue posible registrar la compra.';
            mensaje.classList.add('registro-mensaje--error');
        }
        if (boton) {
            boton.disabled = false;
            boton.querySelector('span').textContent = 'Registrar la compra';
        }
    }
}

/* Carga en la confirmación los datos previos y catálogos necesarios */
async function inicializarPasoTresRegistroCompra()
{
    if (!document.querySelector('[data-confirmar-productos]')) return;
    document.querySelector('[data-registrar-compra]')?.addEventListener('click', enviarRegistroCompra);
    inicializarSeleccionesConfirmarCompra();
    renderizarResumenConfirmarCompra();
    await cargarCatalogosConfirmacionCompra();
    const moneda = document.querySelector('[data-confirmar-moneda]');
    if (moneda?.selectedOptions?.[0]?.dataset.codigo === 'USD') await cargarTipoCambioDolarCompra();
}

/* Dibuja en el paso dos los productos conservados en el paso uno */
function renderizarProductosPrepararCompra()
{
    const tabla = document.querySelector('[data-preparar-productos]');
    if (!tabla) return;

    const productos = obtenerProductosSolicitadosRegistro();
    const preparacion = obtenerPreparacionCompraRegistro();
    const textoCantidad = document.querySelector('[data-preparar-productos-texto]');
    if (textoCantidad) {
        textoCantidad.textContent = `${productos.length.toLocaleString('es-CL')} ${productos.length === 1 ? 'producto' : 'productos'}`;
    }

    if (!productos.length) {
        tabla.innerHTML = '<tr><td colspan="6">No hay productos en la solicitud.</td></tr>';
        actualizarTotalPrepararCompra();
        return;
    }

    tabla.innerHTML = productos.map((producto, indice) => {
        const imagen = producto.imagen || obtenerRutaImagenSinRegistro('producto');
        const precio = preparacion.precios_unitarios?.[producto.id];
        const precioValido = precio !== undefined && precio !== null && precio !== '' && Number.isFinite(Number(precio));
        return `<tr data-preparar-producto-row="${escaparRegistroCompra(producto.id)}">
            <td>${indice + 1}</td>
            <td><span class="preparar-imagen"><img src="${escaparRegistroCompra(imagen)}" alt="Imagen de ${escaparRegistroCompra(producto.nombre)}" data-imagen-respaldo></span></td>
            <td><strong>${escaparRegistroCompra(producto.nombre)}</strong><small class="preparar-producto-codigo">${escaparRegistroCompra(producto.codigo_producto || '')}</small></td>
            <td>${Math.max(1, Number(producto.cantidad) || 1)}</td>
            <td>${escaparRegistroCompra(producto.unidad_medida || 'Unidades')}</td>
            <td><input type="number" min="0.01" step="0.01" value="${precioValido ? Number(precio) : ''}" placeholder="0.00" aria-label="Precio unitario de ${escaparRegistroCompra(producto.nombre)}" data-preparar-precio="${escaparRegistroCompra(producto.id)}"></td>
        </tr>`;
    }).join('');
    actualizarTotalPrepararCompra();
}

/* Guarda los precios ingresados para cada producto solicitado */
function actualizarPrecioPrepararCompra(evento)
{
    const campo = evento.target.closest('[data-preparar-precio]');
    if (!campo) return;

    const preparacion = obtenerPreparacionCompraRegistro();
    preparacion.precios_unitarios = preparacion.precios_unitarios || {};
    if (campo.value === '') {
        delete preparacion.precios_unitarios[campo.dataset.prepararPrecio];
    } else {
        const precio = Number(campo.value);
        if (!Number.isFinite(precio) || precio < 0) return;
        preparacion.precios_unitarios[campo.dataset.prepararPrecio] = precio;
    }
    guardarPreparacionCompraRegistro(preparacion);
    actualizarTotalPrepararCompra();

    const mensaje = document.querySelector('[data-preparar-mensaje]');
    if (mensaje?.textContent.includes('precio unitario') && !obtenerCampoPrecioPendientePrepararCompra()) {
        mensaje.textContent = '';
        mensaje.classList.remove('registro-mensaje--error');
    }
}

/* Devuelve el primer precio unitario vacío, inválido o igual a cero */
function obtenerCampoPrecioPendientePrepararCompra()
{
    return [...document.querySelectorAll('[data-preparar-precio]')].find((campo) => {
        const precio = Number(campo.value);
        return campo.value.trim() === '' || !Number.isFinite(precio) || precio <= 0;
    }) || null;
}

/* Cambia el panel lateral entre recomendaciones de proveedores y transportistas */
function cambiarPanelSugerenciasCompra(tipo)
{
    const panelProveedores = document.querySelector('[data-panel-proveedores]');
    const panelTransportistas = document.querySelector('[data-panel-despachos]');
    const botonProveedor = document.querySelector('[data-selector-proveedor]');
    const botonTransportista = document.querySelector('[data-selector-despacho]');
    if (!panelProveedores || !panelTransportistas) return;

    const mostrarProveedores = tipo === 'proveedor';
    panelProveedores.hidden = !mostrarProveedores;
    panelTransportistas.hidden = mostrarProveedores;
    botonProveedor?.setAttribute('aria-expanded', String(mostrarProveedores));
    botonTransportista?.setAttribute('aria-expanded', String(!mostrarProveedores));
}

/* Actualiza la selección del proveedor general de esta solicitud */
function seleccionarProveedorPrepararCompra(proveedor)
{
    const preparacion = obtenerPreparacionCompraRegistro();
    preparacion.id_proveedor = Number(proveedor.id_proveedor);
    preparacion.nombre_proveedor = proveedor.nombre;
    guardarPreparacionCompraRegistro(preparacion);

    const texto = document.querySelector('[data-selector-proveedor-texto]');
    const boton = document.querySelector('[data-selector-proveedor]');
    if (texto) texto.textContent = proveedor.nombre;
    boton?.classList.add('preparar-selector-solo-lectura--seleccionado');
    renderizarListaProveedoresSugeridosCompra();
}

/* Guarda el transportista obligatorio seleccionado para esta solicitud */
function seleccionarTransportistaPrepararCompra(transportista)
{
    const idTransportista = Number(transportista?.id_transportista);
    if (!Number.isInteger(idTransportista) || idTransportista <= 0 || !transportista?.nombre) return;

    const preparacion = obtenerPreparacionCompraRegistro();
    preparacion.id_transportista = idTransportista;
    preparacion.nombre_transportista = transportista.nombre;
    guardarPreparacionCompraRegistro(preparacion);

    const texto = document.querySelector('[data-selector-despacho-texto]');
    const boton = document.querySelector('[data-selector-despacho]');
    const mensaje = document.querySelector('[data-preparar-mensaje]');
    if (texto) texto.textContent = transportista.nombre;
    boton?.classList.add('preparar-selector-solo-lectura--seleccionado');
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.classList.remove('registro-mensaje--error');
    }
    renderizarListaTransportistasSugeridosCompra();
}

/* Presenta recomendaciones de proveedores ordenadas por evaluación y actividad */
function renderizarListaProveedoresSugeridosCompra(mensajeVacio = 'No hay proveedores registrados todavía.')
{
    const lista = document.querySelector('[data-proveedores-sugeridos-lista]');
    if (!lista) return;
    const seleccionId = Number(obtenerPreparacionCompraRegistro().id_proveedor) || 0;
    if (!proveedoresSugeridosCompra.length) {
        lista.innerHTML = `<p class="sugerencias-vacio">${escaparRegistroCompra(mensajeVacio)}</p>`;
        return;
    }

    lista.innerHTML = proveedoresSugeridosCompra.map((proveedor) => {
        const id = Number(proveedor.id_proveedor);
        const seleccionado = id === seleccionId;
        const evaluacion = proveedor.calificacion === null || proveedor.calificacion === undefined
            ? 'Sin evaluaciones'
            : `${Number(proveedor.calificacion).toLocaleString('es-CL', { maximumFractionDigits: 1 })} / 5 estrellas`;
        return `<button type="button" class="sugerencia-entidad ${seleccionado ? 'sugerencia-entidad--seleccionada' : ''}" data-seleccionar-proveedor="${id}" aria-pressed="${seleccionado}">
            <span class="sugerencia-entidad__selector" aria-hidden="true"></span>
            <span class="sugerencia-entidad__avatar"><i class="bi bi-building" aria-hidden="true"></i></span>
            <span class="sugerencia-entidad__texto"><strong>${escaparRegistroCompra(proveedor.nombre)}</strong><small>${escaparRegistroCompra(proveedor.razon_social || proveedor.nombre)}</small><small>${evaluacion} · ${Number(proveedor.compras) || 0} compras</small></span>
        </button>`;
    }).join('');
}

/* Presenta recomendaciones de transportistas ordenadas por evaluación y actividad */
function renderizarListaTransportistasSugeridosCompra(mensajeVacio = 'No hay transportistas registrados todavía.')
{
    const lista = document.querySelector('[data-transportistas-sugeridos-lista]');
    if (!lista) return;
    const seleccionId = Number(obtenerPreparacionCompraRegistro().id_transportista) || 0;
    if (!transportistasSugeridosCompra.length) {
        lista.innerHTML = `<p class="sugerencias-vacio">${escaparRegistroCompra(mensajeVacio)}</p>`;
        return;
    }

    lista.innerHTML = transportistasSugeridosCompra.map((transportista) => {
        const id = Number(transportista.id_transportista);
        const seleccionado = id === seleccionId;
        const evaluacion = transportista.calificacion === null || transportista.calificacion === undefined
            ? 'Sin evaluaciones'
            : `${Number(transportista.calificacion).toLocaleString('es-CL', { maximumFractionDigits: 1 })} / 5 estrellas`;
        return `<button type="button" class="sugerencia-entidad ${seleccionado ? 'sugerencia-entidad--seleccionada' : ''}" data-seleccionar-transportista="${id}" aria-pressed="${seleccionado}">
            <span class="sugerencia-entidad__selector" aria-hidden="true"></span>
            <span class="sugerencia-entidad__avatar sugerencia-entidad__avatar--transportista"><i class="bi bi-truck" aria-hidden="true"></i></span>
            <span class="sugerencia-entidad__texto"><strong>${escaparRegistroCompra(transportista.nombre)}</strong><small>${evaluacion} · ${Number(transportista.compras) || 0} compras despachadas</small></span>
        </button>`;
    }).join('');
}

/* Consulta las mejores opciones existentes para el panel lateral */
async function cargarSugerenciasCompra(tipo)
{
    const esProveedor = tipo === 'proveedor';
    const modulo = esProveedor ? 'proveedores' : 'transportistas';
    const atributoLista = esProveedor ? '[data-proveedores-sugeridos-lista]' : '[data-transportistas-sugeridos-lista]';
    const selectorBusqueda = esProveedor ? '[data-buscar-proveedores]' : '[data-buscar-transportistas]';
    const lista = document.querySelector(atributoLista);
    const estado = esProveedor ? estadoBusquedaProveedoresCompra : estadoBusquedaTransportistasCompra;
    if (lista) lista.innerHTML = '<p class="sugerencias-vacio">Cargando recomendaciones...</p>';

    try {
        const parametros = new URLSearchParams({ modulo, accion: 'listar_sugerencias_compra' });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, { headers: { Accept: 'application/json' } });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudieron cargar las recomendaciones.');

        if (esProveedor) {
            proveedoresRecomendadosCompra = Array.isArray(resultado.datos) ? resultado.datos.slice(0, 5) : [];
            if (!estado.termino || estado.termino.length < 2 || document.querySelector(selectorBusqueda)?.value.trim().length < 2) {
                proveedoresSugeridosCompra = proveedoresRecomendadosCompra;
                renderizarListaProveedoresSugeridosCompra();
            }
        } else {
            transportistasRecomendadosCompra = Array.isArray(resultado.datos) ? resultado.datos.slice(0, 5) : [];
            if (!estado.termino || estado.termino.length < 2 || document.querySelector(selectorBusqueda)?.value.trim().length < 2) {
                transportistasSugeridosCompra = transportistasRecomendadosCompra;
                renderizarListaTransportistasSugeridosCompra();
            }
        }
    } catch (error) {
        if (lista) lista.innerHTML = `<p class="sugerencias-vacio sugerencias-vacio--error">${escaparRegistroCompra(error.message)}</p>`;
    }
}

/* Actualiza el estado y los botones de páginas del buscador del panel lateral */
function actualizarPaginacionBusquedaCompra(tipo, paginacion = {})
{
    const esProveedor = tipo === 'proveedor';
    const estado = esProveedor ? estadoBusquedaProveedoresCompra : estadoBusquedaTransportistasCompra;
    const nav = document.querySelector(esProveedor ? '[data-paginacion-proveedores]' : '[data-paginacion-transportistas]');
    const info = document.querySelector(esProveedor ? '[data-pagina-info-proveedores]' : '[data-pagina-info-transportistas]');
    const anterior = document.querySelector(esProveedor ? '[data-pagina-anterior-proveedores]' : '[data-pagina-anterior-transportistas]');
    const siguiente = document.querySelector(esProveedor ? '[data-pagina-siguiente-proveedores]' : '[data-pagina-siguiente-transportistas]');

    estado.pagina = Math.max(1, Number(paginacion.pagina_actual) || 1);
    estado.totalPaginas = Math.max(1, Number(paginacion.total_paginas) || 1);
    if (!nav || !info || !anterior || !siguiente) return;
    nav.hidden = estado.totalPaginas <= 1;
    info.textContent = `Página ${estado.pagina} de ${estado.totalPaginas} · ${Number(paginacion.total) || 0} resultados`;
    anterior.disabled = estado.pagina <= 1;
    siguiente.disabled = estado.pagina >= estado.totalPaginas;
}

/* Busca en todo el catálogo y muestra cinco resultados por página */
async function buscarEnCatalogoCompra(tipo, pagina = 1)
{
    const esProveedor = tipo === 'proveedor';
    const modulo = esProveedor ? 'proveedores' : 'transportistas';
    const estado = esProveedor ? estadoBusquedaProveedoresCompra : estadoBusquedaTransportistasCompra;
    const input = document.querySelector(esProveedor ? '[data-buscar-proveedores]' : '[data-buscar-transportistas]');
    const lista = document.querySelector(esProveedor ? '[data-proveedores-sugeridos-lista]' : '[data-transportistas-sugeridos-lista]');
    const textoEstado = document.querySelector(esProveedor ? '[data-estado-proveedores]' : '[data-estado-transportistas]');
    const botonLimpiar = document.querySelector(esProveedor ? '[data-limpiar-busqueda-proveedores]' : '[data-limpiar-busqueda-transportistas]');
    const termino = input?.value.trim() || '';
    if (!lista || termino.length < 2) return;

    if (estado.controlador) estado.controlador.abort();
    estado.controlador = new AbortController();
    estado.termino = termino;
    estado.pagina = pagina;
    if (textoEstado) textoEstado.textContent = 'Buscando en todo el catálogo...';
    if (botonLimpiar) botonLimpiar.hidden = false;
    lista.innerHTML = '<p class="sugerencias-vacio">Buscando coincidencias...</p>';
    actualizarPaginacionBusquedaCompra(tipo);

    try {
        const parametros = new URLSearchParams({
            modulo,
            accion: 'buscar_catalogo_compra',
            busqueda: termino,
            pagina: String(pagina),
        });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, {
            signal: estado.controlador.signal,
            headers: { Accept: 'application/json' },
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo completar la búsqueda.');
        if (input.value.trim() !== termino) return;

        const datos = resultado.datos || {};
        const resultados = Array.isArray(datos.resultados) ? datos.resultados.slice(0, 5) : [];
        if (esProveedor) {
            proveedoresSugeridosCompra = resultados;
            renderizarListaProveedoresSugeridosCompra('No se encontraron proveedores que coincidan con la búsqueda.');
        } else {
            transportistasSugeridosCompra = resultados;
            renderizarListaTransportistasSugeridosCompra('No se encontraron transportistas que coincidan con la búsqueda.');
        }

        const total = Number(datos.paginacion?.total) || 0;
        if (textoEstado) {
            textoEstado.textContent = total
                ? `Se encontraron ${total.toLocaleString('es-CL')} coincidencias en el catálogo.`
                : 'No hay coincidencias. Puedes registrar un nuevo elemento.';
        }
        actualizarPaginacionBusquedaCompra(tipo, datos.paginacion || {});
    } catch (error) {
        if (error.name === 'AbortError') return;
        if (textoEstado) textoEstado.textContent = error.message;
        lista.innerHTML = `<p class="sugerencias-vacio sugerencias-vacio--error">${escaparRegistroCompra(error.message)}</p>`;
        actualizarPaginacionBusquedaCompra(tipo);
    }
}

/* Devuelve las recomendaciones principales al limpiar la búsqueda */
function restablecerBusquedaCompra(tipo)
{
    const esProveedor = tipo === 'proveedor';
    const estado = esProveedor ? estadoBusquedaProveedoresCompra : estadoBusquedaTransportistasCompra;
    const input = document.querySelector(esProveedor ? '[data-buscar-proveedores]' : '[data-buscar-transportistas]');
    const botonLimpiar = document.querySelector(esProveedor ? '[data-limpiar-busqueda-proveedores]' : '[data-limpiar-busqueda-transportistas]');
    const textoEstado = document.querySelector(esProveedor ? '[data-estado-proveedores]' : '[data-estado-transportistas]');
    const nav = document.querySelector(esProveedor ? '[data-paginacion-proveedores]' : '[data-paginacion-transportistas]');
    if (estado.temporizador) clearTimeout(estado.temporizador);
    estado.controlador?.abort();
    estado.controlador = null;
    estado.termino = '';
    estado.pagina = 1;
    estado.totalPaginas = 1;
    if (input) input.value = '';
    if (botonLimpiar) botonLimpiar.hidden = true;
    if (nav) nav.hidden = true;
    if (textoEstado) textoEstado.textContent = esProveedor
        ? 'Hasta 5 recomendaciones. Busca para ver otros proveedores.'
        : 'Hasta 5 recomendaciones. Busca para ver otros transportistas.';

    if (esProveedor) {
        proveedoresSugeridosCompra = proveedoresRecomendadosCompra.slice(0, 5);
        renderizarListaProveedoresSugeridosCompra();
    } else {
        transportistasSugeridosCompra = transportistasRecomendadosCompra.slice(0, 5);
        renderizarListaTransportistasSugeridosCompra();
    }
}

/* Conecta los campos de búsqueda y navegación a la consulta completa del catálogo */
function inicializarBusquedaCatalogoCompra(tipo)
{
    const esProveedor = tipo === 'proveedor';
    const input = document.querySelector(esProveedor ? '[data-buscar-proveedores]' : '[data-buscar-transportistas]');
    const botonLimpiar = document.querySelector(esProveedor ? '[data-limpiar-busqueda-proveedores]' : '[data-limpiar-busqueda-transportistas]');
    const anterior = document.querySelector(esProveedor ? '[data-pagina-anterior-proveedores]' : '[data-pagina-anterior-transportistas]');
    const siguiente = document.querySelector(esProveedor ? '[data-pagina-siguiente-proveedores]' : '[data-pagina-siguiente-transportistas]');
    const estado = esProveedor ? estadoBusquedaProveedoresCompra : estadoBusquedaTransportistasCompra;
    if (!input) return;

    input.addEventListener('input', () => {
        const termino = input.value.trim();
        estado.termino = termino;
        estado.pagina = 1;
        if (botonLimpiar) botonLimpiar.hidden = !termino;

        if (estado.temporizador) clearTimeout(estado.temporizador);
        estado.controlador?.abort();

        if (termino.length < 2) {
            if (esProveedor) proveedoresSugeridosCompra = proveedoresRecomendadosCompra.slice(0, 5);
            else transportistasSugeridosCompra = transportistasRecomendadosCompra.slice(0, 5);
            if (esProveedor) renderizarListaProveedoresSugeridosCompra();
            else renderizarListaTransportistasSugeridosCompra();
            const textoEstado = document.querySelector(esProveedor ? '[data-estado-proveedores]' : '[data-estado-transportistas]');
            if (textoEstado) textoEstado.textContent = termino
                ? 'Escribe al menos dos caracteres para buscar en todo el catálogo.'
                : (esProveedor
                    ? 'Hasta 5 recomendaciones. Busca para ver otros proveedores.'
                    : 'Hasta 5 recomendaciones. Busca para ver otros transportistas.');
            const nav = document.querySelector(esProveedor ? '[data-paginacion-proveedores]' : '[data-paginacion-transportistas]');
            if (nav) nav.hidden = true;
            return;
        }

        const textoEstado = document.querySelector(esProveedor ? '[data-estado-proveedores]' : '[data-estado-transportistas]');
        if (textoEstado) textoEstado.textContent = 'Buscando en todo el catálogo...';
        estado.temporizador = setTimeout(() => buscarEnCatalogoCompra(tipo, 1), 300);
    });

    botonLimpiar?.addEventListener('click', () => restablecerBusquedaCompra(tipo));
    anterior?.addEventListener('click', () => {
        if (estado.pagina > 1) buscarEnCatalogoCompra(tipo, estado.pagina - 1);
    });
    siguiente?.addEventListener('click', () => {
        if (estado.pagina < estado.totalPaginas) buscarEnCatalogoCompra(tipo, estado.pagina + 1);
    });
}

/* Abre el formulario modal de proveedor o transportista */
function abrirModalEntidadCompra(tipo)
{
    const modal = document.querySelector(tipo === 'proveedor' ? '[data-modal-proveedor]' : '[data-modal-despacho]');
    const formulario = modal?.querySelector('form');
    const mensaje = modal?.querySelector('[data-mensaje-entidad-nueva]');
    if (!modal || !formulario) return;

    formulario.reset();
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.classList.remove('registro-mensaje--error');
    }
    modal.hidden = false;
    document.body.classList.add('registro-modal-abierto');
    formulario.querySelector('input')?.focus();
}

/* Cierra el formulario modal de entidad */
function cerrarModalEntidadCompra(modal)
{
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('registro-modal-abierto');
}

/* Envía el alta del proveedor o transportista y lo selecciona para la compra */
async function guardarNuevaEntidadCompra(evento, tipo)
{
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const esProveedor = tipo === 'proveedor';
    const mensaje = formulario.querySelector('[data-mensaje-entidad-nueva]');
    const boton = formulario.querySelector('button[type="submit"]');
    const modal = formulario.closest('.registro-modal');
    const modulo = esProveedor ? 'proveedores' : 'transportistas';
    const accion = esProveedor ? 'crear_proveedor_compra' : 'crear_transportista_compra';
    if (mensaje) {
        mensaje.textContent = '';
        mensaje.classList.remove('registro-mensaje--error');
    }
    if (boton) boton.disabled = true;

    try {
        const parametros = new URLSearchParams({ modulo, accion });
        const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, {
            method: 'POST',
            body: new FormData(formulario),
            headers: { Accept: 'application/json' },
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.exito) throw new Error(resultado.mensaje || 'No se pudo guardar el registro.');

        const entidad = resultado.datos || {};
        if (esProveedor) {
            const nuevoProveedor = {
                id_proveedor: Number(entidad.id_proveedor),
                nombre: entidad.nombre,
                razon_social: entidad.razon_social,
                compras: 0,
                calificacion: null,
            };
            proveedoresRecomendadosCompra = [nuevoProveedor, ...proveedoresRecomendadosCompra.filter((item) => Number(item.id_proveedor) !== nuevoProveedor.id_proveedor)].slice(0, 5);
            restablecerBusquedaCompra('proveedor');
            seleccionarProveedorPrepararCompra(nuevoProveedor);
        } else {
            const nuevoTransportista = {
                id_transportista: Number(entidad.id_transportista),
                nombre: entidad.nombre,
                compras: 0,
                calificacion: null,
            };
            transportistasRecomendadosCompra = [nuevoTransportista, ...transportistasRecomendadosCompra.filter((item) => Number(item.id_transportista) !== nuevoTransportista.id_transportista)].slice(0, 5);
            restablecerBusquedaCompra('transportista');
            seleccionarTransportistaPrepararCompra(nuevoTransportista);
        }
        formulario.reset();
        cerrarModalEntidadCompra(modal);
    } catch (error) {
        if (mensaje) {
            mensaje.textContent = error.message;
            mensaje.classList.add('registro-mensaje--error');
        }
    } finally {
        if (boton) boton.disabled = false;
    }
}

/* Conecta listas, paneles laterales, campos y formularios del segundo paso */
function inicializarPasoDosRegistroCompra()
{
    const panel = document.querySelector('[data-panel-proveedores]');
    if (!panel) return;

    const preparacion = obtenerPreparacionCompraRegistro();
    const botonProveedor = document.querySelector('[data-selector-proveedor]');
    const botonTransportista = document.querySelector('[data-selector-despacho]');
    if (preparacion.nombre_proveedor) {
        document.querySelector('[data-selector-proveedor-texto]').textContent = preparacion.nombre_proveedor;
        botonProveedor.classList.add('preparar-selector-solo-lectura--seleccionado');
    }
    if (Number(preparacion.id_transportista) > 0 && preparacion.nombre_transportista) {
        document.querySelector('[data-selector-despacho-texto]').textContent = preparacion.nombre_transportista;
        botonTransportista.classList.add('preparar-selector-solo-lectura--seleccionado');
    }

    if (document.querySelector('[data-costo-envio]')) {
        document.querySelector('[data-costo-envio]').value = Number(preparacion.costo_envio) || 0;
    }
    renderizarProductosPrepararCompra();

    document.querySelector('[data-preparar-productos]')?.addEventListener('input', actualizarPrecioPrepararCompra);
    document.querySelector('[data-preparar-productos]')?.addEventListener('change', actualizarPrecioPrepararCompra);
    document.querySelector('[data-costo-envio]')?.addEventListener('input', (evento) => {
        const datos = obtenerPreparacionCompraRegistro();
        datos.costo_envio = Math.max(0, Number(evento.target.value) || 0);
        guardarPreparacionCompraRegistro(datos);
        actualizarTotalPrepararCompra();
    });

    document.querySelector('[data-preparar-continuar]')?.addEventListener('click', (evento) => {
        const campoPrecioPendiente = obtenerCampoPrecioPendientePrepararCompra();
        if (campoPrecioPendiente) {
            evento.preventDefault();
            const mensaje = document.querySelector('[data-preparar-mensaje]');
            if (mensaje) {
                mensaje.textContent = 'Debes ingresar un precio unitario mayor que cero para cada producto antes de continuar.';
                mensaje.classList.add('registro-mensaje--error');
            }
            campoPrecioPendiente.focus();
            return;
        }

        const seleccion = obtenerPreparacionCompraRegistro();
        const idProveedor = Number(seleccion.id_proveedor);
        const idTransportista = Number(seleccion.id_transportista);
        const proveedorValido = Number.isInteger(idProveedor) && idProveedor > 0 && seleccion.nombre_proveedor?.trim();
        const transportistaValido = Number.isInteger(idTransportista) && idTransportista > 0 && seleccion.nombre_transportista?.trim();
        if (proveedorValido && transportistaValido) return;

        evento.preventDefault();
        const mensaje = document.querySelector('[data-preparar-mensaje]');
        const tipoEntidad = proveedorValido ? 'transportista' : 'proveedor';
        if (mensaje) {
            mensaje.textContent = tipoEntidad === 'proveedor'
                ? 'Selecciona un proveedor general antes de continuar.'
                : 'Selecciona un transportista antes de continuar.';
            mensaje.classList.add('registro-mensaje--error');
        }
        cambiarPanelSugerenciasCompra(tipoEntidad);
        document.querySelector(tipoEntidad === 'proveedor' ? '[data-selector-proveedor]' : '[data-selector-despacho]')?.focus();
    });

    botonProveedor?.addEventListener('click', () => cambiarPanelSugerenciasCompra('proveedor'));
    botonTransportista?.addEventListener('click', () => cambiarPanelSugerenciasCompra('transportista'));
    document.querySelector('[data-proveedores-sugeridos-lista]')?.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-seleccionar-proveedor]');
        const proveedor = proveedoresSugeridosCompra.find((item) => Number(item.id_proveedor) === Number(boton?.dataset.seleccionarProveedor));
        if (proveedor) seleccionarProveedorPrepararCompra(proveedor);
    });
    document.querySelector('[data-transportistas-sugeridos-lista]')?.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-seleccionar-transportista]');
        const transportista = transportistasSugeridosCompra.find((item) => Number(item.id_transportista) === Number(boton?.dataset.seleccionarTransportista));
        if (transportista) seleccionarTransportistaPrepararCompra(transportista);
    });

    document.querySelector('[data-abrir-modal-proveedor]')?.addEventListener('click', () => abrirModalEntidadCompra('proveedor'));
    document.querySelector('[data-abrir-modal-despacho]')?.addEventListener('click', () => abrirModalEntidadCompra('transportista'));
    document.querySelector('[data-formulario-proveedor]')?.addEventListener('submit', (evento) => guardarNuevaEntidadCompra(evento, 'proveedor'));
    document.querySelector('[data-formulario-despacho]')?.addEventListener('submit', (evento) => guardarNuevaEntidadCompra(evento, 'transportista'));

    document.querySelectorAll('[data-cerrar-modal-proveedor]').forEach((boton) => boton.addEventListener('click', () => cerrarModalEntidadCompra(document.querySelector('[data-modal-proveedor]'))));
    document.querySelectorAll('[data-cerrar-modal-despacho]').forEach((boton) => boton.addEventListener('click', () => cerrarModalEntidadCompra(document.querySelector('[data-modal-despacho]'))));
    document.querySelectorAll('[data-modal-proveedor], [data-modal-despacho]').forEach((modal) => {
        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) cerrarModalEntidadCompra(modal);
        });
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            const modalAbierto = document.querySelector('.registro-modal:not([hidden])');
            if (modalAbierto) cerrarModalEntidadCompra(modalAbierto);
        }
    });

    inicializarBusquedaCatalogoCompra('proveedor');
    inicializarBusquedaCatalogoCompra('transportista');
    cargarSugerenciasCompra('proveedor');
    cargarSugerenciasCompra('transportista');
}

/* Inicializa el paso de productos y el formulario que conserva la imagen seleccionada */
document.addEventListener('DOMContentLoaded', function () {
    inicializarPasoUnoRegistroCompra();
    inicializarFormularioProductoRegistroCompra();
    inicializarPasoDosRegistroCompra();
    inicializarPasoTresRegistroCompra();
});
