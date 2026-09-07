<?php
/**
 * Database Configuration & Connection Class
 * Customer Management System
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'customer_management');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static ?PDO $instance = null;

    /**
     * Get Singleton PDO Database Connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die("<div style='padding:20px; color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; font-family:sans-serif;'>
                    <h3>Database Connection Error</h3>
                    <p>Unable to connect to MySQL database <strong>" . DB_NAME . "</strong> on " . DB_HOST . ".</p>
                    <p><em>Error Details:</em> " . htmlspecialchars($e->getMessage()) . "</p>
                    <p>Please make sure XAMPP MySQL is running and database configuration in <code>config/database.php</code> is correct.</p>
                </div>");
            }
        }
        return self::$instance;
    }
}
