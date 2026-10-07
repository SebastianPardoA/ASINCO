/**
 * global.js
 *
 * Contiene funciones JavaScript reutilizables en todo el sistema ASINCO
 */

// Rutas de respaldo para entidades que no tienen una imagen registrada
const RUTAS_IMAGENES_SIN_REGISTRO = {
    producto: '../recursos/img/sin_imagen_producto.jpg',
    proveedor: '../recursos/img/sin_imagen_proveedores.jpg',
    transportista: '../recursos/img/sin_imagen_transportista.jpg',
};

// Mantiene compatibilidad temporal con scripts antiguos guardados en caché
const RUTA_IMAGEN_SIN_REGISTRO = RUTAS_IMAGENES_SIN_REGISTRO.producto;

// Devuelve la imagen predeterminada según el tipo de entidad
function obtenerRutaImagenSinRegistro(tipo)
{
    return RUTAS_IMAGENES_SIN_REGISTRO[tipo] || RUTAS_IMAGENES_SIN_REGISTRO.producto;
}

// Sustituye una imagen ausente o inválida por las iniciales de la entidad
function manejarErrorImagenRespaldo(evento)
{
    const imagen = evento.target;

    if (!imagen.matches('[data-imagen-respaldo]')) {
        return;
    }

    const contenedor = imagen.parentElement;
    const iniciales = imagen.dataset.iniciales || '?';

    if (contenedor.matches('.modal-detalle__imagen-contenedor')) {
        imagen.hidden = true;
        imagen.removeAttribute('src');
        const fallback = contenedor.querySelector('[data-detalle-imagen-fallback]');
        if (fallback) {
            fallback.textContent = iniciales;
            fallback.hidden = false;
        }
        return;
    }

    imagen.remove();
    contenedor.classList.add('entidad-miniatura--fallback');
    contenedor.textContent = iniciales;
}

// Escucha errores de imagen, incluyendo imágenes agregadas dinámicamente por las vistas
document.addEventListener('error', manejarErrorImagenRespaldo, true);

// Inicializa el botón que contrae y despliega el menú lateral
function inicializarMenuLateral()
{
    const botonMenu = document.querySelector('[data-menu-lateral-boton]');

    if (!botonMenu) {
        return;
    }

    const menuContraido = localStorage.getItem('asinco_menu_lateral_contraido') === 'true';

    aplicarEstadoMenuLateral(menuContraido, botonMenu);

    botonMenu.addEventListener('click', function () {
        const estaContraido = document.body.classList.toggle('menu-lateral-contraido');

        localStorage.setItem('asinco_menu_lateral_contraido', estaContraido ? 'true' : 'false');
        actualizarBotonMenuLateral(botonMenu, estaContraido);
    });
}

// Aplica el estado inicial contraído o desplegado del menú lateral
function aplicarEstadoMenuLateral(estaContraido, botonMenu)
{
    document.body.classList.toggle('menu-lateral-contraido', estaContraido);
    actualizarBotonMenuLateral(botonMenu, estaContraido);
}

// Actualiza el ícono y atributos accesibles del botón del menú lateral
function actualizarBotonMenuLateral(botonMenu, estaContraido)
{
    botonMenu.setAttribute('aria-expanded', estaContraido ? 'false' : 'true');
    botonMenu.setAttribute('aria-label', estaContraido ? 'Desplegar menú lateral' : 'Contraer menú lateral');
}

// Inicializa el modal de confirmación para cerrar la sesión actual
function inicializarCierreSesion()
{
    const botonAbrir = document.querySelector('[data-cerrar-sesion-boton]');
    const botonCancelar = document.querySelector('[data-cerrar-sesion-cancelar]');
    const modal = document.querySelector('[data-cerrar-sesion-modal]');

    if (!botonAbrir || !botonCancelar || !modal) {
        return;
    }

    botonAbrir.addEventListener('click', function () {
        modal.hidden = false;
    });

    botonCancelar.addEventListener('click', function () {
        modal.hidden = true;
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            modal.hidden = true;
        }
    });
}

// Inicializa el comportamiento comun de los modales de detalle
function inicializarModalDetalle()
{
    const modal = document.querySelector('[data-detalle-modal]');
    const botonesCerrar = document.querySelectorAll('[data-detalle-cerrar], [data-detalle-volver]');

    if (!modal || !botonesCerrar.length) {
        return;
    }

    botonesCerrar.forEach((boton) => {
        boton.addEventListener('click', function () {
            modal.hidden = true;
        });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) {
            modal.hidden = true;
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
        }
    });
}

// Ejecuta las funciones globales cuando la página ya está disponible
document.addEventListener('DOMContentLoaded', function () {
    inicializarMenuLateral();
    inicializarCierreSesion();
    inicializarModalDetalle();
});
