<?php

foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
	if ( 'Aspect Trading local development test' !== $zone_data['zone_name'] ) {
		continue;
	}
	$zone = new WC_Shipping_Zone( $zone_data['zone_id'] );
	echo 'zone_id=' . $zone->get_id() . "\n";
	echo 'locations=' . wp_json_encode( array_map( static function ( $location ) { return array( 'code' => $location->code, 'type' => $location->type ); }, $zone->get_zone_locations() ) ) . "\n";
	foreach ( $zone->get_shipping_methods( false ) as $method ) {
		echo 'method_id=' . $method->id . "\n";
		echo 'instance_id=' . $method->get_instance_id() . "\n";
		echo 'option_key=' . $method->get_option_key() . "\n";
		echo 'instance_settings=' . wp_json_encode( $method->instance_settings ) . "\n";
	}
}
