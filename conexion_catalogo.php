<?php
/**
 * Conexión PDO a la base de datos del catálogo web (productos_catalogo).
 *
 * Uso:
 *   require_once __DIR__ . '/conexion_catalogo.php';
 *   $conexionCatalogo = new ConexionCatalogo();
 *   $pdo = $conexionCatalogo->conectar();
 *   if (!$pdo) { ... manejar error ... }
 */

require_once __DIR__ . '/config.php';

class ConexionCatalogo
{
    /**
     * Abre la conexión y devuelve un objeto PDO, o null si falla.
     */
    public function conectar()
    {
        try {
            $dsn = 'mysql:host=' . HOST_CATALOGO .
                ';port=' . PORT_CATALOGO .
                ';dbname=' . DATABASE_CATALOGO .
                ';charset=utf8mb4';

            $pdo = new PDO($dsn, USER_CATALOGO, PASSWORD_CATALOGO, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            error_log('Error de conexión a productos_catalogo: ' . $e->getMessage());
            return null;
        }
    }
}
