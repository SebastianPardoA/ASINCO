/**
 * index.js
 *
 * Controla los comportamientos propios del login
 */

/* Oculta la notificación de cierre después de cinco segundos */
function ocultarNotificacionLogin()
{
    const notificacion = document.querySelector('[data-login-notificacion]');

    if (!notificacion) {
        return;
    }

    /* Evita que el mensaje vuelva a aparecer al refrescar la misma URL */
    const urlLimpia = `${window.location.pathname}${window.location.hash}`;
    window.history.replaceState({}, document.title, urlLimpia);

    window.setTimeout(function () {
        notificacion.classList.add('login-notificacion--oculta');
        window.setTimeout(function () {
            notificacion.remove();
        }, 250);
    }, 5000);
}

/* Alterna entre mostrar y ocultar la contraseña escrita en el formulario */
function inicializarVisibilidadContrasena()
{
    const boton = document.querySelector('[data-login-toggle-password]');
    const campo = document.getElementById('contrasena');

    if (!boton || !campo) {
        return;
    }

    boton.addEventListener('click', function () {
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        boton.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}

/* Inicializa las funciones del login cuando el documento está disponible */
document.addEventListener('DOMContentLoaded', function () {
    ocultarNotificacionLogin();
    inicializarVisibilidadContrasena();
});
