<?php
/**
 * Adapted from SplClassLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 * http://groups.google.com/group/php-standards/web/final-proposal
 *
 *     // Example which loads classes for the Doctrine Common package in the
 *     // Doctrine\Common namespace.
 *     $classLoader = new SplClassLoader('Doctrine\Common', '/path/to/doctrine');
 *     $classLoader->register();
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */

class GFP_Stripe_Loader {

	private $_file_extension = '.php';
	private $_namespace;
	private $_include_path;
	private $_namespace_separator = '\\';

	/**
	 * Creates a new <tt>SplClassLoader</tt> that loads classes of the
	 * specified namespace.
	 *
	 * @param string $ns The namespace to use.
	 */
	public function __construct ( $ns = null, $include_path = null ) {
		$this->_namespace    = $ns;
		$this->_include_path = $include_path;
	}

	/**
	 * Sets the namespace separator used by classes in the namespace of this class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function set_namespace_separator ( $sep ) {
		$this->_namespace_separator = $sep;
	}

	/**
	 * Gets the namespace separator used by classes in the namespace of this class loader.
	 *
	 * @return void
	 */
	public function get_namespace_separator () {
		return $this->_namespace_separator;
	}

	/**
	 * Sets the base include path for all class files in the namespace of this class loader.
	 *
	 * @param string $include_path
	 */
	public function loader_set_include_path ( $include_path ) {
		$this->_include_path = $include_path;
	}

	/**
	 * Gets the base include path for all class files in the namespace of this class loader.
	 *
	 * @return string $includePath
	 */
	public function loader_get_include_path () {
		return $this->_include_path;
	}

	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 *
	 * @param string $fileExtension
	 */
	public function set_file_extension ( $fileExtension ) {
		$this->_file_extension = $fileExtension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $fileExtension
	 */
	public function get_file_extension () {
		return $this->_file_extension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register () {
		spl_autoload_register( array( $this, 'loadClass' ) );
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister () {
		spl_autoload_unregister( array( $this, 'loadClass' ) );
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $class_name The name of the class to load.
	 *
	 * @return void
	 */
	public function loadClass ( $class_name ) {
		if ( 'GFP_Stripe' == mb_substr( $class_name, 0, 10 ) ) {
			$file_name = '';
			$namespace = '';
			$file_name .= str_replace( '_', DIRECTORY_SEPARATOR, $class_name );
			$parameters = explode( DIRECTORY_SEPARATOR, $file_name );
			foreach ( $parameters as &$parameter ) {
				$parameter = strtolower( $parameter );
			}
			unset( $parameter );

			if ( isset( $parameters[2] ) ) {
				$start_of_filename    = strrpos( $file_name, DIRECTORY_SEPARATOR ) + 1;
				$file_name_to_replace = substr( $file_name, $start_of_filename );
				$actual_file_name     = 'class-' . $parameters[0] . '-' . $parameters[1] . '-' . $parameters[2];
				$file_name            = str_replace( $file_name_to_replace, $actual_file_name, $file_name ) . $this->_file_extension;
			}
			else {
				$actual_file_name = 'class-' . $parameters[0] . '-' . $parameters[1];
				$file_name        = $file_name . DIRECTORY_SEPARATOR . $actual_file_name . $this->_file_extension;
			}

			$file_name = ( ( $this->_include_path !== null ) ? $this->_include_path . DIRECTORY_SEPARATOR : '' ) . $file_name;

			require( $file_name );
		}
		else {
			return;
		}
	}
}