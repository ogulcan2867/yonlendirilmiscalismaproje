<?php
	define('DEBUG_MODE', true);
	if(DEBUG_MODE) {
		error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
	}

	//ini_set('display_errors', !IS_ENV_PRODUCTION);
	//ini_set('error_log', 'log/phperror.txt');

	// set time zone to use date/time functions without warnings
	date_default_timezone_set('Turkey');

	// compensate for magic quotes if necessary
	if (get_magic_quotes_gpc()) {
		function _stripslashes_rcurs($variable, $top = true) {
			$clean_data = array();
			foreach ($variable as $key => $value) {
				$key = ($top) ? $key : stripslashes($key);
				$clean_data[$key] = (is_array($value)) ? stripslashes_rcurs($value, false) : stripslashes($value);
			}
			return $clean_data;
		}

		$_GET = _stripslashes_rcurs($_GET);
		$_POST = _stripslashes_rcurs($_POST);
	}
?>