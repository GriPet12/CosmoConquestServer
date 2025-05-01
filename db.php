<?php
class DatabaseConnection {
	private static $instance = null;
	private $pdo;

    private function __construct()
    {
        $host = 'localhost';
        $dbname = 'cosmo_conquest';
        $user = 'postgres';
        $password = 'password';
        $port = 5432; // типовий порт для PostgreSQL

		try {
			$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
			
			$options = [
				 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				 PDO::ATTR_EMULATE_PREPARES => false,
			];

			$this->pdo = new PDO($dsn, $user, $password, $options);

			// Встановлення кодування 
			$this->pdo->exec("SET NAMES 'utf8'");

	  } catch (PDOException $e) {
			error_log('Database Connection Error: ' . $e->getMessage());
			throw new Exception('Database connection failed: ' . $e->getMessage());
	  }
 }

 public static function getInstance() {
	  if (self::$instance === null) {
			self::$instance = new self();
	  }
	  return self::$instance;
 }

 public function getConnection() {
	  return $this->pdo;
 }

 // Закриваємо можливість клонування
 private function __clone() {}
 public function __wakeup() {} // Змінено на public
}