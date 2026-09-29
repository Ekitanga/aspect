<?php
session_start();

if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) {
	http_response_code( 404 );
	exit;
}

header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );

if ( empty( $_SESSION['aspect_db_setup_token'] ) ) {
	$_SESSION['aspect_db_setup_token'] = bin2hex( random_bytes( 32 ) );
}

$message = '';
$success = false;

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	$token = (string) ( $_POST['token'] ?? '' );
	$app_password = (string) ( $_POST['app_password'] ?? '' );

	if ( ! hash_equals( $_SESSION['aspect_db_setup_token'], $token ) ) {
		$message = 'This setup form expired. Reload the page and try again.';
	} elseif ( '' === $app_password ) {
		$message = 'Enter the local application password to continue.';
	} else {
		$database = null;

		try {
			mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
			$database = new mysqli( '127.0.0.1', 'root', '', '', 3308 );
			$database->set_charset( 'utf8mb4' );
			$database->query( 'CREATE DATABASE IF NOT EXISTS aspect_trading CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );

			$escaped_password = "'" . $database->real_escape_string( $app_password ) . "'";
			$database->query( "ALTER USER 'root'@'localhost' IDENTIFIED BY $escaped_password" );
			$database->query( "CREATE USER IF NOT EXISTS 'aspect'@'localhost' IDENTIFIED BY $escaped_password" );
			$database->query( "ALTER USER 'aspect'@'localhost' IDENTIFIED BY $escaped_password" );
			$database->query( "CREATE USER IF NOT EXISTS 'aspect'@'127.0.0.1' IDENTIFIED BY $escaped_password" );
			$database->query( "ALTER USER 'aspect'@'127.0.0.1' IDENTIFIED BY $escaped_password" );
			$database->query( "GRANT ALL PRIVILEGES ON aspect_trading.* TO 'aspect'@'localhost'" );
			$database->query( "GRANT ALL PRIVILEGES ON aspect_trading.* TO 'aspect'@'127.0.0.1'" );

			$app_check = new mysqli( '127.0.0.1', 'aspect', $app_password, 'aspect_trading', 3308 );
			$app_check->close();
			$database->close();
			$success = true;
			$message = 'The Aspect Trading database and restricted application account are ready. Remove this temporary setup file, then retry the WordPress database form.';
			unset( $_SESSION['aspect_db_setup_token'] );
		} catch ( Throwable $error ) {
			if ( $database instanceof mysqli ) {
				$database->close();
			}
			$message = 'Database setup failed. Check that the isolated MariaDB service is running and that its root password is correct.';
		}

		unset( $app_password, $escaped_password );
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Aspect Trading Local Database Setup</title>
	<style>
		body{margin:0;background:#f6faf8;color:#203235;font:16px/1.5 "Segoe UI",sans-serif}
		main{max-width:32rem;margin:8vh auto;padding:2rem;background:#fff;border:1px solid #d7e2df;border-radius:6px}
		h1{margin-top:0;color:#153d40;font-size:1.5rem}
		label{display:block;margin:1rem 0 .35rem;font-weight:600}
		input{box-sizing:border-box;width:100%;min-height:2.75rem;padding:.6rem;border:1px solid #98aaa6;border-radius:4px;font:inherit}
		button{margin-top:1.25rem;min-height:2.75rem;padding:.6rem 1rem;border:0;border-radius:4px;background:#153d40;color:white;font:inherit;font-weight:600;cursor:pointer}
		.notice{padding:.75rem;background:#edf5f2;border-left:4px solid #137d6a}
		@media(max-width:36rem){main{margin:1rem;padding:1.25rem}}
	</style>
</head>
<body>
<main>
	<h1>Aspect Trading database setup</h1>
	<p>This loopback-only form secures the fresh local MariaDB instance, creates the <code>aspect_trading</code> database, and grants the <code>aspect</code> user access only to that database.</p>
	<?php if ( $message ) : ?>
		<p class="notice" role="status"><?php echo htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' ); ?></p>
	<?php endif; ?>
	<?php if ( ! $success ) : ?>
		<form method="post" autocomplete="off">
			<input type="hidden" name="token" value="<?php echo htmlspecialchars( $_SESSION['aspect_db_setup_token'], ENT_QUOTES, 'UTF-8' ); ?>">
			<label for="app_password">Aspect local password</label>
			<input id="app_password" name="app_password" type="password" required autocomplete="new-password">
			<button type="submit">Create local database access</button>
		</form>
	<?php endif; ?>
</main>
</body>
</html>
