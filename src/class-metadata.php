<?php
/**
 * Metadata of Website
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2024-02-06
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/class-cache.php';
require_once __DIR__ . '/lib/remote-contents.php';

/**
 * Metadata of Website
 *
 * @api
 */
class Metadata extends Cache {
	/**
	 * Get metadata from cache or retrieve new data if not cached.
	 *
	 * @param string $url URL.
	 *
	 * @return mixed Resulting data from the URL or cache.
	 */
	public function get( string $url ) {
		return $this->get_data( array( 'url' => $url ) );
	}


	// // -------------------------------------------------------------------------


	/**
	 * Generate new data based on parameters.
	 *
	 * @param array<string, mixed> $params Parameters for generating new data.
	 *
	 * @return mixed|null New data from the API on success, null on failure.
	 */
	protected function request_data_( array $params ) {
		$res = get_remote_contents( $params['url'] );

		if ( null === $res ) {
			$this->last_error = error_get_last()['message'] ?? 'API request failed';
			return null;
		}
		$ie = libxml_use_internal_errors( true );

		$doc = new DOMDocument();
		$doc->loadHTML( mb_convert_encoding( $res, 'HTML-ENTITIES', 'UTF-8' ) );

		libxml_use_internal_errors( $ie );

		$ret = array();

		$ts = $doc->getElementsByTagName( 'title' );
		if ( 0 < $ts->length ) {
			$ret['title'] = $ts->item( 0 )->textContent;
		}
		$ms = $doc->getElementsByTagName( 'meta' );
		foreach ( $ms as $m ) {  // phpcs:ignore
			$key = $m->getAttribute( 'name' );
			$key = strtolower( $key );
			if ( 'description' === $key ) {
				$ret[ $key ] = $m->getAttribute( 'content' );
			}
		}

		$ls = $doc->getElementsByTagName( 'link' );
		foreach ( $ls as $l ) {  // phpcs:ignore
			$key = $l->getAttribute( 'rel' );
			if ( empty( $key ) ) {
				continue;
			}
			$key = strtolower( $key );
			if ( 'apple-touch-icon' === $key ) {
				$ret[ $key ] = $l->getAttribute( 'href' );
			}
			if ( 'icon' === $key ) {
				$ret[ $key ] = $l->getAttribute( 'href' );
			}
		}

		foreach ( $ms as $m ) {  // phpcs:ignore
			$key = $m->getAttribute( 'name' );
			if ( empty( $key ) ) {
				$key = $m->getAttribute( 'property' );
			}
			if ( empty( $key ) ) {
				continue;
			}
			$key = strtolower( $key );
			if ( 'og:' === substr( $key, 0, strlen( 'og:' ) ) ) {
				$ret[ $key ] = $m->getAttribute( 'content' );
			}
		}
		return $ret;
	}
}
