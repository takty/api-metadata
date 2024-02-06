<?php
/**
 * Key-Value Store
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2023-12-01
 */

declare(strict_types=1);

/**
 * Key-Value Store
 *
 * @api
 */
class KeyValueStore {

	/**
	 * The path to the file storing the data.
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * The expiration time for stored values in seconds.
	 *
	 * @var int
	 */
	private $exp_time;

	/**
	 * The file handle for accessing the data file.
	 *
	 * @var resource
	 */
	private $file_handle;

	/**
	 * The timestamp of the last modification of the data file.
	 *
	 * @var int
	 */
	private $last_time;

	/**
	 * The associative array to store key-value pairs and their expiration times.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * PersistentKeyValueStore constructor.
	 *
	 * @param string $file_path The path to the file storing the data.
	 * @param int    $exp_time  The expiration time for stored values in seconds.
	 */
	public function __construct( string $file_path, int $exp_time ) {
		$this->file_path = $file_path;
		$this->exp_time  = $exp_time;

		$this->open_();
		$this->last_time = file_exists( $this->file_path ) ? filemtime( $this->file_path ) : 0;
		$this->load_();
	}

	/**
	 * Set a value for a given key in the key-value store.
	 *
	 * @param string $key The key to set the value for.
	 * @param mixed  $val The value to set.
	 */
	public function set( string $key, $val ) {
		$this->lock_();
		$this->load_();
		$this->data[ $key ] = array(
			'value'    => json_encode( $val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),  // phpcs:ignore
			'exp_time' => time() + $this->exp_time,
		);
		$this->save_();
		$this->unlock_();
	}

	/**
	 * Get the value for a given key from the key-value store.
	 *
	 * @param string $key The key to get the value for.
	 *
	 * @return mixed|null The value for the given key, or null if the key is not found or has expired.
	 */
	public function get( string $key ) {
		$this->lock_();
		$this->load_();
		$val = isset( $this->data[ $key ] ) && $this->data[ $key ]['exp_time'] > time()
			? $this->data[ $key ]['value']
			: null;
		$this->unlock_();
		return null === $val ? null : json_decode( $val, true );
	}

	/**
	 * Load data from the file if it has been modified since the last load.
	 */
	private function load_() {
		if ( file_exists( $this->file_path ) ) {
			$time = filemtime( $this->file_path );

			if ( $this->last_time < $time ) {
				$this->last_time = $time;
				$this->data      = array();

				fseek( $this->file_handle, 0 );
				$cont = stream_get_contents( $this->file_handle );

				if ( null !== $cont ) {
					$data = json_decode( $cont, true );
					if ( null !== $data ) {
						$this->data = $data;
					}
				}
			}
		} else {
			$this->last_time = 0;
			$this->data      = array();
		}
	}

	/**
	 * Save the data to the file.
	 *
	 * @return int|null The number of bytes written, or null on failure.
	 */
	private function save_() {
		$cont = json_encode( $this->data );  // phpcs:ignore

		fseek( $this->file_handle, 0 );
		ftruncate( $this->file_handle, 0 );
		$ret = fwrite( $this->file_handle, $cont );  // phpcs:ignore

		$this->last_time = filemtime( $this->file_path );
		return false === $ret ? null : $ret;
	}

	/**
	 * Open the file for reading and writing, creating it if it does not exist.
	 *
	 * @throws Exception If unable to open or create the file.
	 */
	private function open_() {
		if ( ! file_exists( $this->file_path ) ) {
			$dir = dirname( $this->file_path );
			if ( ! is_dir( $dir ) ) {
				if ( ! mkdir( $dir, 0755, true ) ) {  // phpcs:ignore
					throw new Exception( "Unable to create the directory: {$dir}" );  // phpcs:ignore
				}
			}
		}
		$this->file_handle = fopen( $this->file_path, 'c+' );  // phpcs:ignore
		if ( ! $this->file_handle ) {
			throw new Exception( "Unable to open or create the file: {$this->file_path}" );  // phpcs:ignore
		}
	}

	/**
	 * Acquire an exclusive lock on the file.
	 */
	private function lock_() {
		flock( $this->file_handle, LOCK_EX );
	}

	/**
	 * Release the lock on the file.
	 */
	private function unlock_() {
		flock( $this->file_handle, LOCK_UN );
	}

	/**
	 * Close the file handle when the object is destroyed.
	 */
	public function __destruct() {
		fclose( $this->file_handle );  // phpcs:ignore
	}
}
