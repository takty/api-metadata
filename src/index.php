<?php
/**
 * API for Retrieving Metadata of Websites
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2024-02-06
 */

define( 'CACHE_DIR', __DIR__ . '/_cache' );

define(
	'ALLOWED_ORIGIN',
	array(
		'https://takty.net',
	)
);


// -----------------------------------------------------------------------------


require_once __DIR__ . '/class-metadata.php';

ini_set( 'display_errors', 'On' );  // phpcs:ignore

header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
header( 'Access-Control-Allow-Headers: Content-Type' );

if ( 'OPTIONS' === (string) filter_input( INPUT_SERVER, 'REQUEST_METHOD' ) ) {  // phpcs:ignore
	header( 'HTTP/1.1 200 OK' );
	exit();
}

$orig = (string) filter_input( INPUT_SERVER, 'HTTP_ORIGIN' );

if ( $orig && ! in_array( $orig, ALLOWED_ORIGIN, true ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	die();
}

$url = (string) filter_input( INPUT_GET, 'url' );

if ( ! $url ) {
	header( 'HTTP/1.1 400 Bad Request' );
	die();
}

header( "Access-Control-Allow-Origin: $orig" );
header( 'Content-Type: text/html; charset=UTF-8' );

$md = new Metadata( CACHE_DIR );

$vs = $md->get( $url );
echo_array( $vs );


// -----------------------------------------------------------------------------


/**
 * Outputs data array.
 *
 * @param array $a Data array.
 */
function echo_array( array $a ) {
	echo json_encode( $a, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );  // phpcs:ignore
}
