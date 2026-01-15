<?php
return [

	// System Error
	'not_found_title'   => "Missing file",
	'not_found_message' => "File not found: %s. Check the path and file name.",
	'nexi_fn_title'   => "Parametri insufficienti",
	'nexi_fn_message' => "L'alias %s punta alla funzione '%s'. Hai passato: '%s' parametro/i, ma ne richiede: '%s'.",

	// Route/Controller Error
	'route_incomplete_title'   => "Missing parameters",
	'route_incomplete_message' => "\nThe route '%s' is incomplete, does not respect the required schema or is non-existent. Control the path and the passed parameters. You may have set the mandatory Route and passed fewer parameters than required.",
	'route_extra_param_title'   => "Extra Parameters",
	'route_extra_param_message' => "\nLa route '%s' ha dei parametri extra e non rispetta lo schema richiesto oppure è inesistente.\nControllate il percorso e i parametri passati.",

	// File Mappa Routing - routers.map.php
	'routemap_array_wrong_title'   => "Array not valid",
	'routemap_array_wrong_message'   => "routes.map.php must return a valid associative array.",
	'routemap_alias_wrong_title'   => "Alias not valid",
	'routemap_alias_wrong_message'   => "Alias '%s' not compliant. Must start with @ and contain only letters, numbers or underscore.",
	'routemap_route_wrong_title'   => "Alias not valid",
	'routemap_route_wrong_message'   => "Check in routes.map.php that the route '%s' is correct.",

	'controller_not_found_title'   => "Invalid controller file",
	'controller_not_found_message' => "The target file '%s' is not valid. It must end with '.controller.php' to be recognized as a valid controller.",

	// Type Error
	'type_invalid_title'   => "Invalid type",
	'type_invalid_message' => "Type '%s' is not recognized for parameter :%s",

	'type_error_title' => "Type error",
	'type_error_desc' => "The parameter '%s' does not match the data type declared in the route. Received value: '%s'. Check the route in the file 'core/routes.map.php and check the data written to the URL: '%s'.",

	'type_error_int'          => "Parameter '%s' requires an integer. Received: '%s'",
	'type_error_dbl'          => "Parameter '%s' requires a decimal number (double). Received: '%s'",
	'type_error_string'       => "Parameter '%s' must contain only a valid string. Passed Value: '%s'",
	'type_error_string_lower' => "Parameter '%s' must contain only lowercase letters. Passed Value: '%s'",
	'type_error_string_upper' => "Parameter '%s' must contain only uppercase letters. Passed Value: '%s'",
	'type_error_bool'         => "Parameter '%s' must be true or false. Received: '%s'",
	'type_error_slug'         => "Parameter '%s' requires a valid slug. Value: '%s'",

	// Error custom description
	'type_error_custom_desc' => "The parameter '%s' does not match the type declared in the route. Value received: '%s'. You likely defined a custom type that does not accept this value.",

	// Error Middleware
	'mdware_title'   => "Errore caricamento del Middleware",
	'mdware_message' => "Il tipo '%s' non è riconosciuto per il parametro",

	// Language user Error
	'language_file_invalid_title'          => "Invalid language file",
	'language_file_invalid_message'          => "\nFile declared but not found1. Language file ‘%s.php’ is not a valid file. Checks in /application/{SITE_ID}/locale if the file exists.\n2. If the selected language should not be active, try checking in /core/config.php that the languages in: lang_locales are set correctly",

	'language_unsupported_title'   => "Invalid language",
	'language_unsupported_desc' => "nThe language ‘%s’ is not among those supported by the system.\nCheck Config -> lang_locales",

];