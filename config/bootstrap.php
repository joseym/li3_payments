<?php

use lithium\core\Environment;
use lithium\util\Validator;
use lithium\core\Libraries;

Libraries::add('authorizeNet', array('path' => LITHIUM_LIBRARY_PATH . '/anet_php_sdk'));

require_once Libraries::get('authorizeNet', 'path') . '/AuthorizeNet.php';

