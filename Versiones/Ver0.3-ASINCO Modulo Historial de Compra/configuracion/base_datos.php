<?php
/**
 * base_datos.php
 *
 * Centraliza la conexión PDO con la base de datos asinco
 * Utiliza la configuración local de XAMPP y entrega errores mediante excepciones
 */

/**
 * Crea una conexión PDO con la base de datos del proyecto
 */
function obtenerConexionBaseDatos()
{
    $servidor = 'localhost';
    $baseDatos = 'asinco';
    $usuario = 'root';
    $contrasena = '';

    $dsn = "mysql:host={$servidor};dbname={$baseDatos};charset=utf8mb4";

    return new PDO($dsn, $usuario, $contrasena, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
