<?php
session_start();

if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) {
	http_response_code( 404 );
	exit;
}

header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );

if ( empty( $_SESSION['aspect_wp_setup_token'] ) ) {
	$_SESSION['aspect_wp_setup_token'] = bin2hex( random_bytes( 32 ) );
}

$message = '';
$success = false;

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	$token = (string) ( $_POST['token'] ?? '' );
	$password = (string) ( $_POST['db_password'] ?? '' );

	if ( ! hash_equals( $_SESSION['aspect_wp_setup_token'], $token ) ) {
		$message = 'This setup form expired. Reload the page and try again.';
	} elseif ( '' === $password ) {
		$message = 'Enter the Aspect database password to continue.';
	} elseif ( file_exists( __DIR__ . '/wp-config.php' ) ) {
		$message = 'A WordPress configuration already exists. No changes were made.';
	} else {
		$connection = null;

		try {
			mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
			$connection = new mysqli( '127.0.0.1', 'aspect', $password, 'aspect_trading', 3308 );
			$connection->set_charset( 'utf8mb4' );
			$connection->query( 'SELECT 1' );
			$connection->close();
			$connection = null;

			$site_root = __DIR__;
			$sample_path = $site_root . '/wp-config-sample.php';
			$config_path = $site_root . '/wp-config.php';
			$config = file_get_contents( $sample_path );
			if ( false === $config ) {
				throw new RuntimeException( 'WordPress sample configuration is unavailable.' );
			}

			$values = array(
				"define( 'DB_NAME', 'database_name_here' );" => "define( 'DB_NAME', 'aspect_trading' );",
				"define( 'DB_USER', 'username_here' );" => "define( 'DB_USER', 'aspect' );",
				"define( 'DB_PASSWORD', 'password_here' );" => "define( 'DB_PASSWORD', '" . addslashes( $password ) . "' );",
				"define( 'DB_HOST', 'localhost' );" => "define( 'DB_HOST', '127.0.0.1:3308' );",
				'$table_prefix = \'wp_\';' => '$table_prefix = \'at_\';',
			);
			$config = str_replace( array_keys( $values ), array_values( $values ), $config );

			$config = preg_replace_callback(
				"/define\\( '(AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT|SECURE_AUTH_SALT|LOGGED_IN_SALT|NONCE_SALT)',\\s*'put your unique phrase here' \\);/",
				static function ( $matches ) {
					return "define( '" . $matches[1] . "', '" . bin2hex( random_bytes( 32 ) ) . "' );";
				},
				$config
			);

			$config = str_replace(
				"define( 'WP_DEBUG', false );",
				"define( 'WP_DEBUG', true );\n" .
				"define( 'WP_DEBUG_LOG', true );\n" .
				"define( 'WP_DEBUG_DISPLAY', false );\n" .
				"@ini_set( 'display_errors', 0 );",
				$config
			);

			if ( false === file_put_contents( $config_path, $config, LOCK_EX ) ) {
				throw new RuntimeException( 'Could not write wp-config.php.' );
			}

			$success = true;
			$message = 'The database login was verified and local WordPress configuration was created. Continue installation in WordPress.';
			unset( $_SESSION['aspect_wp_setup_token'] );
		} catch ( Throwable $error ) {
			if ( $connection instanceof mysqli ) {
				$connection->close();
			}
			$message = 'The database login did not succeed or local configuration could not be written. Check the entered password and try again.';
		}

		unset( $password, $config, $values );
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Aspect Trading WordPress Database Check</title>
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
	<h1>Aspect Trading WordPress database</h1>
	<p>This local-only check tests the dedicated database login. On success it writes <code>wp-config.php</code> for port 3308 and enables private debug logging without displaying errors on pages.</p>
	<?php if ( $message ) : ?>
		<p class="notice" role="status"><?php echo htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' ); ?></p>
	<?php endif; ?>
	<?php if ( ! $success ) : ?>
		<form method="post" autocomplete="off">
			<input type="hidden" name="token" value="<?php echo htmlspecialchars( $_SESSION['aspect_wp_setup_token'], ENT_QUOTES, 'UTF-8' ); ?>">
			<label for="db_password">Aspect database password</label>
			<input id="db_password" name="db_password" type="password" required autocomplete="new-password">
			<button type="submit">Verify and configure WordPress</button>
		</form>
	<?php endif; ?>
</main>
</body>
</html>
