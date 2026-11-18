/**
 * dashboard.js
 *
 * Carga los datos del dashboard y representa tablas, rankings y gráficos
 */

const dashboardRaiz = document.querySelector('[data-dashboard]');
let dashboardSecuenciaCarga = 0;

/* Formatea los numeros segun la convención local chilena */
function dashboardFormatoNumero(valor) {
    return new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(Number(valor) || 0);
}

/* Formatea montos expresados en pesos chilenos */
function dashboardFormatoCLP(valor) {
    return `CLP ${dashboardFormatoNumero(valor)}`;
}

/* Convierte fechas SQL al formato habitual del sistema */
function dashboardFormatoFecha(fecha) {
    if (!fecha) return '—';
    const fechaLocal = new Date(`${fecha}T00:00:00`);
    return Number.isNaN(fechaLocal.getTime()) ? fecha : fechaLocal.toLocaleDateString('es-CL');
}

/* Traduce nombres internos de estados a etiquetas legibles */
function dashboardNombreEstado(estado) {
    const normalizado = String(estado || 'Sin estado').trim().toLocaleLowerCase('es-CL').replaceAll('_', ' ');
    const nombresPrincipales = {
        recibida: 'Recibida',
        recibido: 'Recibida',
        registrada: 'Registrada',
        confirmada: 'Confirmada',
        confirmado: 'Confirmada',
        'en preparacion': 'En preparación',
        'en preparación': 'En preparación',
        atrasada: 'Atrasada',
    };
    return nombresPrincipales[normalizado] || normalizado.replace(/\b\p{L}/gu, letra => letra.toLocaleUpperCase('es-CL'));
}

/* Devuelve un color hexadecimal válido o un color de respaldo seguro */
function dashboardColorValido(color, respaldo = '#008596') {
    return /^#[0-9a-f]{6}$/i.test(String(color || '')) ? color : respaldo;
}

/* Inicializa las opciones del selector mensual del gráfico de estados */
function dashboardPrepararSelectorMes() {
    const selector = dashboardRaiz.querySelector('[data-dashboard-mes]');
    const ahora = new Date();
    const nombreMes = new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric' });
    selector.replaceChildren();
    for (let retroceso = 0; retroceso < 12; retroceso += 1) {
        const fecha = new Date(ahora.getFullYear(), ahora.getMonth() - retroceso, 1);
        const valor = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
        const opcion = document.createElement('option');
        opcion.value = valor;
        opcion.textContent = nombreMes.format(fecha);
        selector.append(opcion);
    }
}

/* Solicita todos los resúmenes del dashboard para los filtros activos */
async function dashboardCargarDatos() {
    const secuencia = ++dashboardSecuenciaCarga;
    const meses = dashboardRaiz.querySelector('[data-dashboard-periodo-grafico]').value || '6';
    const mesEstados = dashboardRaiz.querySelector('[data-dashboard-mes]').value;
    const parametros = new URLSearchParams({ modulo: 'dashboard', accion: 'obtener_datos_dashboard', meses, mes_estados: mesEstados });
    const respuesta = await fetch(`../api/api.php?${parametros.toString()}`, { headers: { Accept: 'application/json' } });
    const resultado = await respuesta.json();
    if (secuencia !== dashboardSecuenciaCarga) return;
    if (!respuesta.ok || !resultado.exito || !resultado.datos) {
        throw new Error(resultado.mensaje || 'No se pudieron cargar los datos del dashboard.');
    }
    dashboardRenderizar(resultado.datos);
}

/* Actualiza los indicadores, la tabla, los gráficos y los rankings con la respuesta de API */
function dashboardRenderizar(datos) {
    const resumen = datos.resumen || {};
    dashboardRaiz.querySelector('[data-dashboard-productos]').textContent = dashboardFormatoNumero(resumen.productos_registrados);
    dashboardRaiz.querySelector('[data-dashboard-gasto]').textContent = dashboardFormatoCLP(resumen.gasto_ultimo_mes_clp);
    dashboardRaiz.querySelector('[data-dashboard-llegada]').textContent = dashboardFormatoNumero(resumen.compras_en_llegada);
    dashboardRaiz.querySelector('[data-dashboard-pendientes]').textContent = dashboardFormatoNumero(resumen.compras_pendientes);
    dashboardRaiz.querySelector('[data-dashboard-monto-pendiente]').textContent = `Valor pendiente: ${dashboardFormatoCLP(resumen.monto_pendiente_clp)}`;
    dashboardRaiz.querySelector('[data-dashboard-periodo]').textContent = `${dashboardEtiquetaMes(resumen.mes_gasto)} · CLP`;

    dashboardRenderizarCompras(datos.compras_recientes || []);
    dashboardRenderizarGraficoLinea(datos.gastos_mensuales || []);
    dashboardRenderizarEstados(datos.compras_por_estado || []);
    dashboardRenderizarRanking('[data-dashboard-mejores-proveedores]', datos.mejores_proveedores || []);
    dashboardRenderizarRanking('[data-dashboard-peores-proveedores]', datos.peores_proveedores || []);
    dashboardRenderizarRanking('[data-dashboard-mejores-transportistas]', datos.mejores_transportistas || []);
    dashboardRenderizarRanking('[data-dashboard-peores-transportistas]', datos.peores_transportistas || []);
    dashboardRenderizarAlertas(datos.alertas || {});

    const sinTasaMensual = Number(resumen.gasto_sin_tasa || 0);
    const sinTasaGrafico = (datos.gastos_mensuales || []).some(mes => Number(mes.compras_sin_tasa || 0) > 0);
    const sinTasaPendiente = Number(resumen.pendientes_sin_tasa || 0) > 0;
    const notaTasa = dashboardRaiz.querySelector('[data-dashboard-nota-tasa]');
    notaTasa.hidden = !(sinTasaMensual || sinTasaGrafico || sinTasaPendiente);
    notaTasa.textContent = 'Algunas compras en USD no tienen tasa histórica guardada y se excluyeron de los montos en CLP.';
}

/* Genera una etiqueta de mes localizada sin depender del idioma del servidor */
function dashboardEtiquetaMes(valor) {
    if (!/^\d{4}-\d{2}$/.test(String(valor || ''))) return 'Mes anterior';
    const [anio, mes] = valor.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CL', { month: 'long', year: 'numeric' }).format(new Date(anio, mes - 1, 1));
}

/* Inserta las cinco compras mas recientes y sus enlaces al detalle */
function dashboardRenderizarCompras(compras) {
    const cuerpo = dashboardRaiz.querySelector('[data-dashboard-recientes]');
    cuerpo.replaceChildren();
    if (compras.length === 0) {
        dashboardAgregarFilaVacia(cuerpo, 6, 'No hay compras registradas.');
        return;
    }

    compras.forEach(compra => {
        const fila = document.createElement('tr');
        const enlace = document.createElement('a');
        enlace.href = `detalle_compra.php?id_compra=${encodeURIComponent(compra.id_compra)}`;
        enlace.textContent = compra.codigo_compra;
        fila.append(dashboardCelda(enlace));
        fila.append(dashboardCelda(compra.proveedor));
        fila.append(dashboardCelda(dashboardFormatoNumero(compra.productos)));
        fila.append(dashboardCelda(compra.total_clp === null ? 'Sin tasa CLP' : dashboardFormatoCLP(compra.total_clp)));

        const estado = document.createElement('span');
        estado.className = 'estado';
        estado.textContent = dashboardNombreEstado(compra.estado);
        estado.style.setProperty('--estado-color', dashboardColorValido(compra.estado_color));
        fila.append(dashboardCelda(estado));
        fila.append(dashboardCelda(dashboardFormatoFecha(compra.fecha_compra)));
        cuerpo.append(fila);
    });
}

/* Crea una celda de tabla que admite texto o un elemento DOM */
function dashboardCelda(contenido) {
    const celda = document.createElement('td');
    if (contenido instanceof Node) celda.append(contenido);
    else celda.textContent = String(contenido ?? '—');
    return celda;
}

/* Muestra una fila vacía para tablas sin registros */
function dashboardAgregarFilaVacia(cuerpo, columnas, mensaje) {
    const fila = document.createElement('tr');
    const celda = document.createElement('td');
    celda.colSpan = columnas;
    celda.className = 'dashboard-vacio';
    celda.textContent = mensaje;
    fila.append(celda);
    cuerpo.append(fila);
}

/* Dibuja los gastos de cada mes en un gráfico que se adapta al espacio disponible */
function dashboardRenderizarGraficoLinea(meses) {
    const contenedor = dashboardRaiz.querySelector('[data-dashboard-grafico-linea]');
    contenedor.replaceChildren();
    if (meses.length === 0) {
        contenedor.textContent = 'No hay datos de gastos para este período.';
        return;
    }

    const ancho = 760;
    const alto = 250;
    const margen = { arriba: 22, derecha: 20, abajo: 45, izquierda: 64 };
    const areaAncho = ancho - margen.izquierda - margen.derecha;
    const areaAlto = alto - margen.arriba - margen.abajo;
    const valores = meses.map(mes => Number(mes.total_clp) || 0);
    const maximo = Math.max(...valores);
    const escala = maximo || 1;
    const svg = dashboardCrearSvg('svg', { viewBox: `0 0 ${ancho} ${alto}`, role: 'presentation', preserveAspectRatio: 'none' });

    for (let linea = 0; linea < 4; linea += 1) {
        const y = margen.arriba + (areaAlto / 3) * linea;
        svg.append(dashboardCrearSvg('line', { x1: margen.izquierda, y1: y, x2: ancho - margen.derecha, y2: y, class: 'grafico-linea__guia' }));
        const referencia = Math.round(maximo * (1 - linea / 3));
        const texto = dashboardCrearSvg('text', { x: margen.izquierda - 8, y: y + 4, 'text-anchor': 'end', class: 'grafico-linea__eje' });
        texto.textContent = dashboardFormatoCompacto(referencia);
        svg.append(texto);
    }

    const coordenadas = valores.map((valor, indice) => {
        const x = meses.length === 1 ? margen.izquierda + areaAncho / 2 : margen.izquierda + (areaAncho / (meses.length - 1)) * indice;
        const y = margen.arriba + areaAlto - ((valor / escala) * areaAlto);
        return { x, y, valor, mes: meses[indice].mes };
    });
    const linea = dashboardCrearSvg('polyline', {
        points: coordenadas.map(punto => `${punto.x},${punto.y}`).join(' '),
        class: 'grafico-linea__serie',
    });
    svg.append(linea);

    coordenadas.forEach(punto => {
        svg.append(dashboardCrearSvg('circle', { cx: punto.x, cy: punto.y, r: 5, class: 'grafico-linea__punto' }));
        const etiqueta = dashboardCrearSvg('text', { x: punto.x, y: Math.max(13, punto.y - 11), 'text-anchor': 'middle', class: 'grafico-linea__valor' });
        etiqueta.textContent = dashboardFormatoCompacto(punto.valor);
        svg.append(etiqueta);
        const mes = dashboardCrearSvg('text', { x: punto.x, y: alto - 14, 'text-anchor': 'middle', class: 'grafico-linea__mes' });
        mes.textContent = dashboardEtiquetaMesCorta(punto.mes);
        svg.append(mes);
    });

    contenedor.append(svg);
}

/* Formatea etiquetas compactas para los ejes y puntos del gráfico */
function dashboardFormatoCompacto(valor) {
    return new Intl.NumberFormat('es-CL', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(valor) || 0);
}

/* Abrevia el mes y muestra su año en las etiquetas del gráfico */
function dashboardEtiquetaMesCorta(valor) {
    if (!/^\d{4}-\d{2}$/.test(String(valor || ''))) return valor;
    const [anio, mes] = valor.split('-').map(Number);
    return new Intl.DateTimeFormat('es-CL', { month: 'short', year: '2-digit' }).format(new Date(anio, mes - 1, 1));
}

/* Crea un elemento del gráfico y le asigna los atributos indicados */
function dashboardCrearSvg(etiqueta, atributos) {
    const nodo = document.createElementNS('http://www.w3.org/2000/svg', etiqueta);
    Object.entries(atributos).forEach(([nombre, valor]) => nodo.setAttribute(nombre, valor));
    return nodo;
}

/* Dibuja el gráfico circular mensual y su leyenda de estados */
function dashboardRenderizarEstados(estados) {
    const grafico = dashboardRaiz.querySelector('[data-dashboard-grafico-dona]');
    const leyenda = dashboardRaiz.querySelector('[data-dashboard-leyenda-estados]');
    const resumen = dashboardRaiz.querySelector('[data-dashboard-resumen-estados]');
    const vacio = dashboardRaiz.querySelector('[data-dashboard-estados-vacio]');
    grafico.replaceChildren();
    leyenda.replaceChildren();

    const total = estados.reduce((suma, estado) => suma + Number(estado.total || 0), 0);
    if (total === 0) {
        resumen.classList.add('dona-resumen--vacio');
        vacio.hidden = false;
        return;
    }
    resumen.classList.remove('dona-resumen--vacio');
    vacio.hidden = true;

    const svg = dashboardCrearSvg('svg', { viewBox: '0 0 130 130', role: 'presentation' });
    const radio = 45;
    const circunferencia = 2 * Math.PI * radio;
    let recorrido = 0;

    estados.forEach((estado, indice) => {
        const cantidad = Number(estado.total) || 0;
        const segmento = total ? (cantidad / total) * circunferencia : 0;
        const colores = ['#50c878', '#bddfff', '#ffbd42', '#16a6d1', '#a78bfa', '#f27878', '#64748b', '#f97316'];
        const color = dashboardColorValido(estado.color, colores[indice % colores.length]);
        if (segmento > 0) {
            svg.append(dashboardCrearSvg('circle', {
                cx: 65, cy: 65, r: radio, fill: 'none', stroke: color,
                'stroke-width': 20, 'stroke-dasharray': `${segmento} ${circunferencia - segmento}`,
                'stroke-dashoffset': -recorrido, transform: 'rotate(-90 65 65)',
            }));
            recorrido += segmento;
        }

        const elemento = document.createElement('li');
        const muestra = document.createElement('span');
        muestra.className = 'leyenda';
        muestra.style.setProperty('--estado-color', color);
        const detalle = document.createElement('span');
        detalle.textContent = dashboardNombreEstado(estado.nombre);
        const porcentaje = total ? Math.round((cantidad / total) * 100) : 0;
        const resumen = document.createElement('small');
        resumen.textContent = `${porcentaje}% (${dashboardFormatoNumero(cantidad)})`;
        detalle.append(resumen);
        elemento.append(muestra, detalle);
        leyenda.append(elemento);
    });

    const textoCentro = dashboardCrearSvg('text', { x: 65, y: 61, 'text-anchor': 'middle', class: 'dona__total' });
    textoCentro.textContent = dashboardFormatoNumero(total);
    const textoEtiqueta = dashboardCrearSvg('text', { x: 65, y: 78, 'text-anchor': 'middle', class: 'dona__etiqueta' });
    textoEtiqueta.textContent = 'compras';
    svg.append(textoCentro, textoEtiqueta);
    grafico.append(svg);

}

/* Muestra los tres registros mejor o peor calificados sin inyectar HTML del servidor */
function dashboardRenderizarRanking(selector, registros) {
    const lista = dashboardRaiz.querySelector(selector);
    lista.replaceChildren();
    if (registros.length === 0) {
        const vacio = document.createElement('li');
        vacio.className = 'dashboard-vacio';
        vacio.textContent = 'Aún no hay calificaciones.';
        lista.append(vacio);
        return;
    }

    registros.forEach((registro, indice) => {
        const elemento = document.createElement('li');
        const posicion = document.createElement('span');
        posicion.className = 'dashboard-ranking-posicion';
        posicion.textContent = `#${indice + 1}`;
        const nombre = document.createElement('span');
        nombre.className = 'dashboard-ranking-nombre';
        nombre.textContent = registro.nombre;
        const nota = Number(registro.calificacion);
        const estrellasLlenas = Math.max(0, Math.min(5, Math.round(nota)));
        const estrellas = document.createElement('span');
        estrellas.className = 'dashboard-ranking-estrellas';
        estrellas.setAttribute('aria-label', `Calificación ${nota.toFixed(1)} de 5`);
        estrellas.textContent = `${'★'.repeat(estrellasLlenas)}${'☆'.repeat(5 - estrellasLlenas)}`;
        const calificacion = document.createElement('strong');
        calificacion.textContent = nota.toFixed(1);
        elemento.append(posicion, nombre, estrellas, calificacion);
        lista.append(elemento);
    });
}

/* Presenta alertas operativas calculadas por la API con mensajes acordes a sus datos */
function dashboardRenderizarAlertas(alertas) {
    const vencidas = dashboardRaiz.querySelector('[data-dashboard-alerta-vencidas]');
    const vencidasTitulo = dashboardRaiz.querySelector('[data-dashboard-alerta-vencidas-titulo]');
    const atrasadas = Number(alertas.compras_vencidas || 0);
    vencidasTitulo.textContent = atrasadas ? 'Compras vencidas' : 'Sin compras vencidas';
    vencidas.closest('.alerta').classList.toggle('alerta--verde', !atrasadas);
    vencidas.closest('.alerta').classList.toggle('alerta--roja', Boolean(atrasadas));
    vencidas.textContent = atrasadas
        ? `${dashboardFormatoNumero(atrasadas)} compra(s) pasó su fecha estimada de llegada y aún no se ha recibido.`
        : 'No hay compras pendientes cuya fecha estimada de llegada haya vencido.';

    const stock = dashboardRaiz.querySelector('[data-dashboard-alerta-stock]');
    const stockBajo = Number(alertas.productos_stock_bajo || 0);
    dashboardRaiz.querySelector('[data-dashboard-alerta-stock-titulo]').textContent = stockBajo ? 'Productos con stock bajo' : 'Stock disponible';
    stock.closest('.alerta').classList.toggle('alerta--verde', !stockBajo);
    stock.closest('.alerta').classList.toggle('alerta--amarilla', Boolean(stockBajo));
    stock.textContent = stockBajo
        ? `${dashboardFormatoNumero(stockBajo)} producto(s) tienen entre 1 y 5 unidades disponibles.`
        : 'Ningún producto tiene entre 1 y 5 unidades disponibles.';

    const transito = dashboardRaiz.querySelector('[data-dashboard-alerta-transito]');
    const transitoActual = alertas.transito_promedio_actual;
    const transitoAnterior = alertas.transito_promedio_anterior;
    dashboardRaiz.querySelector('[data-dashboard-alerta-transito-titulo]').textContent = 'Tiempo de tránsito promedio';
    if (transitoActual === null || transitoActual === undefined) {
        transito.textContent = 'Aún no hay recepciones registradas este mes para calcular el promedio.';
    } else if (transitoAnterior === null || transitoAnterior === undefined) {
        transito.textContent = `Promedio de ${Number(transitoActual).toLocaleString('es-CL', { maximumFractionDigits: 1 })} días este mes; aún no hay datos del mes anterior para comparar.`;
    } else {
        const diferencia = Number(transitoActual) - Number(transitoAnterior);
        const porcentaje = Number(transitoAnterior) > 0 ? Math.abs((diferencia / Number(transitoAnterior)) * 100) : null;
        const comparacion = diferencia === 0 ? 'igual al mes anterior' : `${diferencia > 0 ? 'subió' : 'bajó'}${porcentaje === null ? '' : ` ${porcentaje.toLocaleString('es-CL', { maximumFractionDigits: 1 })}%`} respecto al mes anterior`;
        transito.textContent = `Promedio de ${Number(transitoActual).toLocaleString('es-CL', { maximumFractionDigits: 1 })} días; ${comparacion}.`;
    }

    const gasto = dashboardRaiz.querySelector('[data-dashboard-alerta-gasto]');
    const gastoTitulo = dashboardRaiz.querySelector('[data-dashboard-alerta-gasto-titulo]');
    const tarjetaGasto = gasto.closest('.alerta');
    const comparacionGasto = alertas.comparacion_gasto;
    const gastoUltimoMes = Number(alertas.gasto_ultimo_mes_clp || 0);
    const gastoPromedio = alertas.gasto_promedio_clp;
    if (gastoPromedio === null || gastoPromedio === undefined) {
        gastoTitulo.textContent = 'Gasto mensual';
        gasto.textContent = 'No hay suficientes compras con tasa CLP para calcular el promedio de los últimos tres meses.';
        tarjetaGasto.classList.remove('alerta--verde', 'alerta--roja');
        tarjetaGasto.classList.add('alerta--azul');
    } else if (comparacionGasto === 'menor') {
        gastoTitulo.textContent = 'Gasto menor al promedio';
        gasto.textContent = `El gasto del mes pasado fue ${dashboardFormatoCLP(Number(gastoPromedio) - gastoUltimoMes)} menor que el promedio de los tres meses anteriores.`;
        tarjetaGasto.classList.remove('alerta--roja', 'alerta--azul');
        tarjetaGasto.classList.add('alerta--verde');
    } else if (comparacionGasto === 'mayor') {
        gastoTitulo.textContent = 'Gasto mayor al promedio';
        gasto.textContent = `El gasto del mes pasado fue ${dashboardFormatoCLP(gastoUltimoMes - Number(gastoPromedio))} mayor que el promedio de los tres meses anteriores.`;
        tarjetaGasto.classList.remove('alerta--verde', 'alerta--azul');
        tarjetaGasto.classList.add('alerta--roja');
    } else {
        gastoTitulo.textContent = 'Gasto mensual';
        gasto.textContent = 'El gasto del mes pasado fue igual al promedio de los tres meses anteriores.';
        tarjetaGasto.classList.remove('alerta--roja', 'alerta--azul');
        tarjetaGasto.classList.add('alerta--verde');
    }
}

/* Presenta un mensaje de error si no se pueden consultar los datos del dashboard */
function dashboardMostrarError(error) {
    const mensaje = dashboardRaiz.querySelector('[data-dashboard-mensaje]');
    mensaje.textContent = error instanceof Error ? error.message : 'No se pudo cargar el dashboard.';
    mensaje.hidden = false;
}

/* Registra los filtros e inicia la primera carga del dashboard */
function dashboardInicializar() {
    if (!dashboardRaiz) return;
    dashboardPrepararSelectorMes();
    dashboardRaiz.querySelector('[data-dashboard-periodo-grafico]').addEventListener('change', () => {
        dashboardCargarDatos().catch(dashboardMostrarError);
    });
    dashboardRaiz.querySelector('[data-dashboard-mes]').addEventListener('change', () => {
        dashboardCargarDatos().catch(dashboardMostrarError);
    });
    dashboardCargarDatos().catch(dashboardMostrarError);
}

dashboardInicializar();
