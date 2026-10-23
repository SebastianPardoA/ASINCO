/**
 * global.js
 *
 * Contiene funciones JavaScript reutilizables en todo el sistema ASINCO
 */



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



// Ejecuta las funciones globales cuando la página ya está disponible
document.addEventListener('DOMContentLoaded', function () {
    inicializarMenuLateral();
    inicializarCierreSesion();
    inicializarModalDetalle();
});
