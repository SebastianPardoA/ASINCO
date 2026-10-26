# ASINCO – Asistente de Inventario y Compras
Proyecto de Título desarrollado para optar al título de Ingeniero en Informática en DuocUC.

## Descripción

**ASINCO (Asistente de Inventario y Compras)** es una plataforma web orientada a centralizar y gestionar la información relacionada con el inventario y los procesos de compra de una organización.

<img width="2550" height="1268" alt="02-Inventario" src="https://github.com/user-attachments/assets/0a89d401-96bc-4783-abef-d2d210fb063e" />

La plataforma permite administrar productos, unidades de inventario, compras, proveedores, transportistas y usuarios, manteniendo además antecedentes históricos como precios, evaluaciones, comentarios y documentos asociados a las adquisiciones.

Su objetivo es mejorar la trazabilidad de los productos y permitir que la información obtenida de compras anteriores pueda ser reutilizada como apoyo para futuras adquisiciones, facilitando la comparación de proveedores y transportistas.

El sistema está dirigido principalmente a organizaciones que necesiten mantener un control digital de su inventario y administrar de forma centralizada la información relacionada con sus procesos de compra.

---

## Tecnologías utilizadas

El proyecto está desarrollado principalmente utilizando las siguientes tecnologías:

- **PHP:** lógica del sistema y comunicación con la base de datos.
- **HTML:** estructura de las interfaces.
- **JavaScript:** comportamiento e interacción de las vistas.
- **Bootstrap:** diseño y organización visual de la plataforma.
- **MariaDB:** almacenamiento y gestión de los datos.
- **Visual Studio Code:** entorno utilizado para el desarrollo del proyecto.
- **GitHub:** control de versiones, respaldo y seguimiento de los cambios realizados.

---

## Ejecución local

Para ejecutar ASINCO de manera local se requiere contar con un servidor web compatible con **PHP** y una instancia de **MariaDB**.

### Pasos generales

1. Ubicar el proyecto dentro del directorio utilizado por el servidor web local.

2. Crear una base de datos en MariaDB.

3. Importar la estructura de base de datos correspondiente al proyecto.

4. Configurar en el proyecto los datos de conexión a la base de datos:
   - Host
   - Nombre de la base de datos
   - Usuario
   - Contraseña

5. Iniciar el servidor web y MariaDB.

6. Acceder al proyecto desde el navegador utilizando la dirección configurada en el entorno local.

---

## Integrantes del equipo

### Benjamín Jeria

Participa en el análisis, planificación, diseño, desarrollo e integración de los diferentes componentes de ASINCO, incluyendo el trabajo relacionado con la estructura de datos y los módulos funcionales de la plataforma.

### Sebastián Pardo

Participa en el análisis, planificación, diseño, desarrollo e integración de ASINCO, incluyendo el diseño de interfaces y el desarrollo de los diferentes módulos funcionales de la plataforma.

Ambos integrantes participan además en las actividades de pruebas, validación, documentación y cierre del proyecto.

---

## Metodología de trabajo

Para el desarrollo de ASINCO se utiliza una metodología tradicional en cascada, organizando el proyecto mediante etapas secuenciales.

Las principales etapas consideradas son:

1. Levantamiento y análisis de requerimientos.
2. Diseño de la solución.
3. Desarrollo e implementación.
4. Pruebas y validación.
5. Documentación y cierre.

Esta metodología permite utilizar los productos generados en cada etapa como base para las actividades posteriores, manteniendo una relación entre los requerimientos, el diseño y la implementación de la plataforma.

---

## Arquitectura de la solución

ASINCO está desarrollado como una **aplicación web conectada a una base de datos MariaDB**.



La plataforma está organizada mediante diferentes módulos funcionales, entre ellos:

- Usuarios.
- Inventario.
- Registro de compras.
- Historial de compras.
- Proveedores.
- Transportistas.
- Dashboard.
- Roles y permisos.

Estos módulos utilizan una base de datos común que permite relacionar la información de productos, unidades de inventario, compras, proveedores, transportistas, usuarios, documentos, evaluaciones y comentarios.
