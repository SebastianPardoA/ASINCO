/**
 * registro_compra.js
 *
 * Contiene las interacciones visuales propias del registro de compra
 */

/* Muestra el panel lateral solicitado y oculta el panel alternativo */
function mostrarPanelSugerencias(panelVisible, panelOculto)
{
    panelVisible.hidden = false;
    panelOculto.hidden = true;
}

/* Actualiza el texto y el estado visual de un control superior de solo lectura */
function actualizarControlPrepararCompra(control, textoControl, valorSeleccionado)
{
    control.dataset.valorSeleccionado = valorSeleccionado;
    textoControl.textContent = valorSeleccionado || 'Seleccionar';
    control.classList.toggle('preparar-selector-solo-lectura--seleccionado', Boolean(valorSeleccionado));
}

/* Marca una sola tarjeta como activa o limpia la selección completa */
function actualizarTarjetasSugeridas(tarjetas, propiedad, valorSeleccionado, claseActiva)
{
    tarjetas.forEach(function (tarjeta) {
        const estaActiva = tarjeta.dataset[propiedad] === valorSeleccionado && Boolean(valorSeleccionado);
        tarjeta.classList.toggle(claseActiva, estaActiva);
        tarjeta.setAttribute('aria-pressed', estaActiva ? 'true' : 'false');
    });
}

/* Configura la selección y deselección de una tarjeta de proveedor */
function configurarTarjetaProveedor(tarjeta, selectorProveedor, textoProveedor, panelProveedores)
{
    tarjeta.addEventListener('click', function () {
        const proveedorActual = selectorProveedor.dataset.valorSeleccionado || '';
        const proveedorSeleccionado = proveedorActual === tarjeta.dataset.proveedor ? '' : tarjeta.dataset.proveedor;
        const tarjetasProveedor = Array.from(panelProveedores.querySelectorAll('[data-proveedor]'));

        actualizarControlPrepararCompra(selectorProveedor, textoProveedor, proveedorSeleccionado);
        actualizarTarjetasSugeridas(tarjetasProveedor, 'proveedor', proveedorSeleccionado, 'proveedor-card--activo');
    });

    tarjeta.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter' || evento.key === ' ') {
            evento.preventDefault();
            tarjeta.click();
        }
    });
}

/* Construye una tarjeta temporal para el proveedor registrado en el modal */
function crearTarjetaProveedor(nombreProveedor)
{
    const tarjeta = document.createElement('article');

    tarjeta.className = 'proveedor-card proveedor-card--seleccionable';
    tarjeta.dataset.proveedor = nombreProveedor;
    tarjeta.setAttribute('role', 'button');
    tarjeta.setAttribute('tabindex', '0');
    tarjeta.setAttribute('aria-pressed', 'false');
    tarjeta.innerHTML = '<span class="proveedor-card__selector"></span>'
        + '<div class="proveedor-card__contenido">'
        + '<div class="proveedor-card__encabezado"><span class="proveedor-card__avatar"></span><h3></h3></div>'
        + '<p>Proveedor agregado temporalmente</p><strong>Sin calificaciones</strong>'
        + '<div class="proveedor-card__etiquetas"><span>Nuevo proveedor</span></div>'
        + '</div><small>Nuevo</small>';
    tarjeta.querySelector('h3').textContent = nombreProveedor;

    return tarjeta;
}

/* Abre el formulario modal y posiciona el foco en su primer campo */
function abrirModalRegistro(modal, campoInicial)
{
    modal.hidden = false;
    document.body.classList.add('registro-modal-abierto');
    campoInicial.focus();
}

/* Cierra el formulario modal y devuelve el foco a su botón de apertura */
function cerrarModalRegistro(modal, botonApertura)
{
    modal.hidden = true;
    document.body.classList.remove('registro-modal-abierto');
    botonApertura.focus();
}

/* Configura la selección y deselección de una tarjeta de despacho */
function configurarTarjetaDespacho(tarjeta, selectorDespacho, textoDespacho, costoEnvio, panelDespachos)
{
    tarjeta.addEventListener('click', function () {
        const metodoActual = selectorDespacho.dataset.valorSeleccionado || '';
        const metodoSeleccionado = metodoActual === tarjeta.dataset.metodoDespacho ? '' : tarjeta.dataset.metodoDespacho;
        const tarjetasDespacho = Array.from(panelDespachos.querySelectorAll('[data-metodo-despacho]'));

        actualizarControlPrepararCompra(selectorDespacho, textoDespacho, metodoSeleccionado);
        actualizarTarjetasSugeridas(tarjetasDespacho, 'metodoDespacho', metodoSeleccionado, 'metodo-despacho-card--activo');
        costoEnvio.value = metodoSeleccionado ? tarjeta.dataset.costoDespacho : '';
    });
}

/* Construye una tarjeta temporal para el método registrado en el modal */
function crearTarjetaDespacho(nombreMetodo, costoMetodo, tiempoEntrega)
{
    const tarjeta = document.createElement('button');

    tarjeta.className = 'proveedor-card metodo-despacho-card';
    tarjeta.type = 'button';
    tarjeta.dataset.metodoDespacho = nombreMetodo;
    tarjeta.dataset.costoDespacho = costoMetodo;
    tarjeta.setAttribute('aria-pressed', 'false');
    tarjeta.innerHTML = '<span class="proveedor-card__selector"></span>'
        + '<span class="proveedor-card__contenido">'
        + '<span class="proveedor-card__encabezado"><span class="proveedor-card__avatar metodo-despacho-card__avatar--azul"></span><span class="metodo-despacho-card__titulo"></span></span>'
        + '<span class="metodo-despacho-card__detalle"></span><strong>Sin calificaciones</strong>'
        + '<span class="proveedor-card__etiquetas"><span>Nuevo metodo</span><span>Con seguimiento</span></span>'
        + '</span><small>Nuevo</small>';
    tarjeta.querySelector('.metodo-despacho-card__titulo').textContent = nombreMetodo;
    tarjeta.querySelector('.metodo-despacho-card__detalle').textContent = 'Costo estimado: ' + costoMetodo + ' · ' + tiempoEntrega;

    return tarjeta;
}

/* Inicializa los paneles de recomendaciones del segundo paso */
function inicializarSugerenciasPrepararCompra()
{
    const selectorProveedor = document.querySelector('[data-selector-proveedor]');
    const selectorDespacho = document.querySelector('[data-selector-despacho]');
    const textoProveedor = document.querySelector('[data-selector-proveedor-texto]');
    const textoDespacho = document.querySelector('[data-selector-despacho-texto]');
    const costoEnvio = document.querySelector('[data-costo-envio]');
    const panelProveedores = document.querySelector('[data-panel-proveedores]');
    const panelDespachos = document.querySelector('[data-panel-despachos]');

    if (!selectorProveedor || !selectorDespacho || !textoProveedor || !textoDespacho || !costoEnvio || !panelProveedores || !panelDespachos) {
        return;
    }

    const tarjetasProveedor = Array.from(panelProveedores.querySelectorAll('[data-proveedor]'));
    const tarjetasDespacho = Array.from(panelDespachos.querySelectorAll('[data-metodo-despacho]'));

    selectorProveedor.addEventListener('click', function () {
        mostrarPanelSugerencias(panelProveedores, panelDespachos);
    });

    selectorDespacho.addEventListener('click', function () {
        mostrarPanelSugerencias(panelDespachos, panelProveedores);
    });

    tarjetasProveedor.forEach(function (tarjeta) {
        configurarTarjetaProveedor(tarjeta, selectorProveedor, textoProveedor, panelProveedores);
    });

    tarjetasDespacho.forEach(function (tarjeta) {
        configurarTarjetaDespacho(tarjeta, selectorDespacho, textoDespacho, costoEnvio, panelDespachos);
    });

    const botonAbrirModal = document.querySelector('[data-abrir-modal-proveedor]');
    const modalProveedor = document.querySelector('[data-modal-proveedor]');
    const formularioProveedor = document.querySelector('[data-formulario-proveedor]');
    const campoNombreProveedor = document.querySelector('[data-nombre-proveedor]');

    if (!botonAbrirModal || !modalProveedor || !formularioProveedor || !campoNombreProveedor) {
        return;
    }

    botonAbrirModal.addEventListener('click', function () {
        mostrarPanelSugerencias(panelProveedores, panelDespachos);
        abrirModalRegistro(modalProveedor, campoNombreProveedor);
    });

    modalProveedor.querySelectorAll('[data-cerrar-modal-proveedor]').forEach(function (botonCerrar) {
        botonCerrar.addEventListener('click', function () {
            cerrarModalRegistro(modalProveedor, botonAbrirModal);
        });
    });

    modalProveedor.addEventListener('click', function (evento) {
        if (evento.target === modalProveedor) {
            cerrarModalRegistro(modalProveedor, botonAbrirModal);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !modalProveedor.hidden) {
            cerrarModalRegistro(modalProveedor, botonAbrirModal);
        }
    });

    formularioProveedor.addEventListener('submit', function (evento) {
        evento.preventDefault();

        const nombreProveedor = campoNombreProveedor.value.trim();
        const listaProveedores = panelProveedores.querySelector('.proveedores-sugeridos__lista');
        let tarjetaProveedor = Array.from(panelProveedores.querySelectorAll('[data-proveedor]')).find(function (tarjeta) {
            return tarjeta.dataset.proveedor.toLowerCase() === nombreProveedor.toLowerCase();
        });

        if (!tarjetaProveedor) {
            tarjetaProveedor = crearTarjetaProveedor(nombreProveedor);
            configurarTarjetaProveedor(tarjetaProveedor, selectorProveedor, textoProveedor, panelProveedores);
            listaProveedores.appendChild(tarjetaProveedor);
        }

        const tarjetasActualizadas = Array.from(panelProveedores.querySelectorAll('[data-proveedor]'));

        actualizarControlPrepararCompra(selectorProveedor, textoProveedor, tarjetaProveedor.dataset.proveedor);
        actualizarTarjetasSugeridas(tarjetasActualizadas, 'proveedor', tarjetaProveedor.dataset.proveedor, 'proveedor-card--activo');
        formularioProveedor.reset();
        cerrarModalRegistro(modalProveedor, botonAbrirModal);
    });

    const botonAbrirModalDespacho = document.querySelector('[data-abrir-modal-despacho]');
    const modalDespacho = document.querySelector('[data-modal-despacho]');
    const formularioDespacho = document.querySelector('[data-formulario-despacho]');
    const campoNombreDespacho = document.querySelector('[data-nombre-despacho]');
    const campoCostoDespacho = document.querySelector('[data-costo-nuevo-despacho]');
    const campoTiempoDespacho = document.querySelector('[data-tiempo-nuevo-despacho]');

    if (!botonAbrirModalDespacho || !modalDespacho || !formularioDespacho || !campoNombreDespacho || !campoCostoDespacho || !campoTiempoDespacho) {
        return;
    }

    botonAbrirModalDespacho.addEventListener('click', function () {
        mostrarPanelSugerencias(panelDespachos, panelProveedores);
        abrirModalRegistro(modalDespacho, campoNombreDespacho);
    });

    modalDespacho.querySelectorAll('[data-cerrar-modal-despacho]').forEach(function (botonCerrar) {
        botonCerrar.addEventListener('click', function () {
            cerrarModalRegistro(modalDespacho, botonAbrirModalDespacho);
        });
    });

    modalDespacho.addEventListener('click', function (evento) {
        if (evento.target === modalDespacho) {
            cerrarModalRegistro(modalDespacho, botonAbrirModalDespacho);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !modalDespacho.hidden) {
            cerrarModalRegistro(modalDespacho, botonAbrirModalDespacho);
        }
    });

    formularioDespacho.addEventListener('submit', function (evento) {
        evento.preventDefault();

        const nombreMetodo = campoNombreDespacho.value.trim();
        const costoNumero = Number(campoCostoDespacho.value);
        const costoMetodo = costoNumero === 0 ? 'GRATIS' : '$' + costoNumero.toLocaleString('es-CL');
        const tiempoEntrega = campoTiempoDespacho.value.trim() || 'Tiempo por confirmar';
        const listaDespachos = panelDespachos.querySelector('.proveedores-sugeridos__lista');
        let tarjetaDespacho = Array.from(panelDespachos.querySelectorAll('[data-metodo-despacho]')).find(function (tarjeta) {
            return tarjeta.dataset.metodoDespacho.toLowerCase() === nombreMetodo.toLowerCase();
        });

        if (!tarjetaDespacho) {
            tarjetaDespacho = crearTarjetaDespacho(nombreMetodo, costoMetodo, tiempoEntrega);
            configurarTarjetaDespacho(tarjetaDespacho, selectorDespacho, textoDespacho, costoEnvio, panelDespachos);
            listaDespachos.appendChild(tarjetaDespacho);
        } else {
            tarjetaDespacho.dataset.costoDespacho = costoMetodo;
        }

        const tarjetasActualizadas = Array.from(panelDespachos.querySelectorAll('[data-metodo-despacho]'));

        actualizarControlPrepararCompra(selectorDespacho, textoDespacho, tarjetaDespacho.dataset.metodoDespacho);
        actualizarTarjetasSugeridas(tarjetasActualizadas, 'metodoDespacho', tarjetaDespacho.dataset.metodoDespacho, 'metodo-despacho-card--activo');
        costoEnvio.value = tarjetaDespacho.dataset.costoDespacho;
        formularioDespacho.reset();
        cerrarModalRegistro(modalDespacho, botonAbrirModalDespacho);
    });
}

// Inicializa las recomendaciones de compra
document.addEventListener('DOMContentLoaded', function () {
    inicializarSugerenciasPrepararCompra();
});
