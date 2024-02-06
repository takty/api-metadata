<?php
/**
 * Atomic File Operations
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2023-11-27
 */

declare(strict_types=1);

/**
 * Perform a file operation atomically.
 *
 * @param string   $path The path to the file.
 * @param string   $mode The type of access.
 * @param callable $op   The callback function to perform the file operation.
 *
 * @return mixed|null Returns the result of the operation on success, or null on failure.
 */
function operate_file_atomically( $path, $mode, $op ) {
	$h = fopen( $path, $mode );  // phpcs:ignore

	if ( $h && flock( $h, LOCK_EX ) ) {
		$res = call_user_func( $op, $h );

		flock( $h, LOCK_UN );
		fclose( $h );  // phpcs:ignore

		return $res;
	} else {
		return null;
	}
}

/**
 * Perform a directory operation atomically.
 *
 * @param string   $path The path to the directory.
 * @param callable $op   The callback function to perform the directory operation.
 *
 * @return mixed|null Returns the result of the operation on success, or null on failure.
 */
function operate_directory_atomically( $path, $op ) {
	$h = opendir( $path );

	if ( $h && flock( $h, LOCK_EX ) ) {
		$res = call_user_func( $op, $path );

		flock( $h, LOCK_UN );
		closedir( $h );

		return $res;
	} else {
		return null;
	}
}
