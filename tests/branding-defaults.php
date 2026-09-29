<?php

function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function elessi_logo() {}
function get_stylesheet_directory_uri() {
	return 'https://aspect.test/theme';
}

require dirname( __DIR__ ) . '/functions.php';

$GLOBALS['nasa_opt'] = array(
	'site_logo'   => '',
	'site_logo_m' => '',
);
aspect_trading_elessi_logo_defaults();

if (
	'https://aspect.test/theme/assets/images/branding/aspect-trading-logo.svg' !== $GLOBALS['nasa_opt']['site_logo'] ||
	'https://aspect.test/theme/assets/images/branding/aspect-trading-logo-mobile.svg' !== $GLOBALS['nasa_opt']['site_logo_m']
) {
	throw new RuntimeException( 'Aspect Trading logo defaults were not applied.' );
}

$GLOBALS['nasa_opt'] = array(
	'site_logo'   => 'https://owner.test/logo.svg',
	'site_logo_m' => 'https://owner.test/mobile.svg',
);
aspect_trading_elessi_logo_defaults();

if (
	'https://owner.test/logo.svg' !== $GLOBALS['nasa_opt']['site_logo'] ||
	'https://owner.test/mobile.svg' !== $GLOBALS['nasa_opt']['site_logo_m']
) {
	throw new RuntimeException( 'Elessi logo settings were overwritten.' );
}

echo "Elessi logo defaults and native-setting precedence pass.\n";