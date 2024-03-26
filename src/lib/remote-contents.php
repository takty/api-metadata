<?php
/**
 * Alternative of file_get_contents
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2024-02-08
 */

declare(strict_types=1);

/**
 * Retrieves the content of a remote file using cURL.
 *
 * @param string $url      The URL of the remote file to fetch.
 * @param int    $timeout  (Optional) The maximum time in seconds to wait for the request to complete.
 *                         Default is 3 seconds.
 *
 * @return string|null     The content of the remote file, or null if the request fails.
 */
function get_remote_contents( string $url, int $timeout = 3 ) {
	$opts = array(
		CURLOPT_TIMEOUT        => $timeout,  // cspell:disable-line.
		CURLOPT_RETURNTRANSFER => true,  // cspell:disable-line.
	);
	// phpcs:disabled
	$ch = curl_init( $url );
	curl_setopt_array( $ch, $opts );

	$data = curl_exec( $ch );
	$info = curl_getinfo( $ch );
	$err  = curl_errno( $ch );
	$msg  = curl_error( $ch );
	curl_close($ch);
	// phpcs:enabled

	if ( CURLE_OK !== $err || 200 !== $info['http_code'] ) {  // cspell:disable-line.
		trigger_error( 'cURL error: ' . $msg, E_USER_ERROR );  // phpcs:ignore
		return null;
	}
	return $data;
}
