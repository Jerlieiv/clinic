<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'master_clinic');

// Site Configuration
define('SITE_NAME', 'MasterClinic');
define('SITE_URL', 'http://127.0.0.1:8080/master-clinic');
define('ADMIN_EMAIL', 'admin@pharmacare.com');
define('VERSION', '1.0.0');


// Timezone Configuration
date_default_timezone_set('Africa/Blantyre');


// Database Connection Function
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

?>