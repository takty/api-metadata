<?php
/**
 * Cache
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2024-02-06
 */

declare(strict_types=1);

require_once __DIR__ . '/atomic-file-op.php';

/**
 * Cache
 *
 * @api
 */
abstract class Cache {
	/**
	 * The directory where cache files are stored.
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * The expiration time for cache files (in seconds).
	 *
	 * @var int
	 */
	private $exp_time;

	/**
	 * The last API error message.
	 *
	 * @var string|null
	 */
	private $last_error = null;

	/**
	 * Constructor.
	 *
	 * @param string $cache_dir The directory where cache files are stored.
	 * @param int    $exp_time  (Optional) The expiration time for cache files (in seconds).
	 */
	public function __construct( string $cache_dir, int $exp_time = 24 * 60 * 60 /* 24 hours */ ) {
		$this->cache_dir = $cache_dir;
		$this->exp_time  = $exp_time;

		if ( ! is_dir( $this->cache_dir ) ) {
			mkdir( $this->cache_dir, 0755, true );  // phpcs:ignore
		}
	}

	/**
	 * Get data from cache or generate new data if not cached.
	 *
	 * @param array<string, mixed> $params Parameters for the function.
	 *
	 * @return mixed Resulting data from the API or cache.
	 */
	public function get_data( array $params ) {
		$this->clean();

		$key  = $this->generate_key_( $params );
		$data = $this->read_( $key, $params );

		if ( null !== $data ) {
			return $data;
		}
		$data = $this->request_data_( $params );
		if ( null !== $data ) {
			$this->write_( $key, $params, $data );
		}
		return $data;
	}

	/**
	 * Clean up old cache entries.
	 */
	public function clean(): void {
		$now = time();

		foreach ( scandir( $this->cache_dir ) as $file ) {
			$path = $this->cache_dir . '/' . $file;

			if ( is_file( $path ) && ( $now - filemtime( $path ) ) > $this->exp_time ) {
				unlink( $path );  // phpcs:ignore
			}
		}
	}

	/**
	 * Get the last API error message.
	 *
	 * @return string|null The last API error message, or null if there was no error.
	 */
	public function get_last_error() {
		return $this->last_error;
	}


	// -------------------------------------------------------------------------


	/**
	 * Read data from cache.
	 *
	 * @param string               $key    Cache key (file path).
	 * @param array<string, mixed> $params Expected parameters for validation.
	 *
	 * @return mixed|null Cached data or null if not found or parameters don't match.
	 */
	private function read_( string $key, array $params ) {
		$path = $this->generate_path_( $key );

		if ( file_exists( $path ) ) {
			$cont = operate_file_atomically(
				$path,
				'r',
				function ( $fh ) use ( $path ) {
					return fread( $fh, filesize( $path ) );  // phpcs:ignore
				}
			);
			if ( null !== $cont ) {
				$data = json_decode( $cont, true );
				if ( null !== $data && isset( $data['params'] ) && $data['params'] === $params ) {
					return $data['data'];
				}
			}
		}
		return null;
	}

	/**
	 * Write data to cache.
	 *
	 * @param string               $key    Cache key (file path).
	 * @param array<string, mixed> $params Parameters to be stored along with the cached data.
	 * @param mixed                $data   Data to be cached.
	 * @return bool Whether or not it was successful.
	 */
	private function write_( string $key, array $params, $data ): bool {
		$path = $this->generate_path_( $key );
		$cont = json_encode(  // phpcs:ignore
			array(
				'data'   => $data,
				'params' => $params,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		$res = operate_file_atomically(
			$path,
			'w',
			function ( $fh ) use ( $cont ) {
				$ret = fwrite( $fh, $cont );  // phpcs:ignore
				return false === $ret ? null : $ret;
			}
		);
		return null !== $res;
	}

	/**
	 * Generate a cache key for the given parameters.
	 *
	 * @param array<string, mixed> $params Parameters for the function.
	 *
	 * @return string Cache key.
	 */
	private function generate_key_( array $params ): string {
		return md5( json_encode( $params ) );  // phpcs:ignore
	}

	/**
	 * Generate a file path for the cache file.
	 *
	 * @param string $key Cache key.
	 *
	 * @return string File path.
	 */
	private function generate_path_( string $key ): string {
		return $this->cache_dir . '/' . $key . '.txt';
	}

	/**
	 * Generate new data based on parameters.
	 *
	 * @param array<string, mixed> $params Parameters for generating new data.
	 *
	 * @return mixed|null New data from the API on success, null on failure.
	 */
	abstract protected function request_data_( array $params );
}
