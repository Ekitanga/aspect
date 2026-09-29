<?php

require 'C:/xampp/htdocs/aspect-trading/wp-config.php';

$connection_parts = explode( ':', DB_HOST );
$database_host = $connection_parts[0];
$database_port = isset( $connection_parts[1] ) ? (int) $connection_parts[1] : 3306;
$database = new mysqli( $database_host, DB_USER, DB_PASSWORD, DB_NAME, $database_port );
$database->query( 'SELECT 1' );
$database->close();

echo "Configured WordPress database connection verified.\n";