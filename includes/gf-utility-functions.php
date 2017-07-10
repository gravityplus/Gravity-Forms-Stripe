<?php

if ( ! function_exists( 'rgget' ) ) {
	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array  $array   Array from which the property's value should be retrieved.
	 * @param string $prop    Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	function rgar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
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
