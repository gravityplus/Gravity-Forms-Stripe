<?php

if ( ! function_exists( 'rgget' ) ) {
	/**
	 * @param      $name
	 * @param null $array
	 *
	 * @return string
	 */
	function rgget ( $name, $array = null ) {
		if ( ! isset( $array ) )
			$array = $_GET;

		if ( isset( $array[$name] ) )
			return $array[$name];

		return "";
	}
}


if ( ! function_exists( 'rgpost' ) ) {
	/**
	 * @param      $name
	 * @param bool $do_stripslashes
	 *
	 * @return mixed|string
	 */
	function rgpost ( $name, $do_stripslashes = true ) {
		if ( isset( $_POST[$name] ) )
			return $do_stripslashes ? stripslashes_deep( $_POST[$name] ) : $_POST[$name];

		return '';
	}
}

if ( ! function_exists( 'rgar' ) ) {
	/**
	 * @param $array
	 * @param $name
	 *
	 * @return string
	 */
	function rgar ( $array, $name ) {
		if ( isset( $array[$name] ) )
			return $array[$name];

		return '';
	}
}

if ( ! function_exists( 'rgars' ) ) {
	/**
	 * @param $array
	 * @param $name
	 *
	 * @return string
	 */
	function rgars ( $array, $name ) {
		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = rgar( $val, $current_name );
		}

		return $val;
	}
}

if ( ! function_exists( 'rgempty' ) ) {
	/**
	 * @param      $name
	 * @param null $array
	 *
	 * @return bool
	 */
	function rgempty ( $name, $array = null ) {
		if ( ! $array )
			$array = $_POST;

		$val = rgget( $name, $array );

		return empty( $val );
	}
}


if ( ! function_exists( 'rgblank' ) ) {
	/**
	 * @param $text
	 *
	 * @return bool
	 */
	function rgblank ( $text ) {
		return empty( $text ) && strval( $text ) != '0';
	}
}