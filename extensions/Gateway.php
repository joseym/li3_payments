<?php

namespace li3_payments\extensions;

use lithium\util\String;
use lithium\util\Inflector;

class Gateway extends \lithium\core\Adaptable {

	protected static $_adapter;

	/**
	 * Path where to look for tracking adapters.
	 *
	 * @var string
	 */
	protected static $_adapters = 'adapter.gateways';

	/**
	 * To be re-defined in sub-classes.
	 *
	 * @var object `Collection` of configurations, indexed by name.
	 */
	protected static $_configurations = array();

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'session' => 'lithium\\storage\\Session'
	);

	public static function add($name, array $config = array()) {

		static::$_configurations[$name] = $config;

		return static::$_configurations;

	}

	/**
	 * Obtain the tracker
	 */
	public static function get($name = null, array $options = array()) {
		if (!$name) {
			return array_keys(static::$_configurations);
		}
		if (!isset(static::$_configurations[$name])) {
			return null;
		}

		return static::adapter($name);
	}

	

}