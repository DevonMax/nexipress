<?php
return [

	// System Error
	'not_found_title'   => "File mancante",
	'not_found_message' => "File non trovato: %s. Controlla il percorso e il nome del file.",
	'nexi_fn_title'   => "Parametri insufficienti",
	'nexi_fn_message' => "L'alias %s punta alla funzione '%s'. Hai passato: '%s' parametro/i, ma ne richiede: '%s'.",

	// Router/Controller Error
	'route_incomplete_title'   => "Parametri mancanti",
	'route_incomplete_message' => "\nLa route '%s' è incompleta, non rispetta lo schema richiesto oppure è inesistente.\nControllate il percorso e i parametri passati.\n Potreste avere impostato la Route obbligatoria e avere passato meno parametri di quelli richiesti.",
	'route_extra_param_title'   => "Parametri Extra",
	'route_extra_param_message' => "\nLa route '%s' ha dei parametri extra e non rispetta lo schema richiesto oppure è inesistente.\nControllate il percorso e i parametri passati.",

	// File Mappa Routing - routers.map.php
	'routemap_array_wrong_title'   => "Array non valido",
	'routemap_array_wrong_message'   => "routes.map.php deve restituire un array associativo valido.",
	'routemap_alias_wrong_title'   => "Alias non valido",
	'routemap_alias_wrong_message'   => "Alias '%s' non conforme. Deve iniziare con @ e contenere solo lettere, numeri o underscore.",
	'routemap_route_wrong_title'   => "Missing configuration",
	'routemap_route_wrong_message'   => "Controlla in routes.map.php che la rotta '%s' sia corretta.",

	'controller_not_found_title'   => "File controller non valido",
	'controller_not_found_message' => "Il file di destinazione '%s' non è valido. Deve terminare con '.controller.php' per essere riconosciuto come controller valido.",

	// Type Error
	'type_invalid_title'   => "Tipo non valido",
	'type_invalid_message' => "Il tipo '%s' non è riconosciuto per il parametro :%s",

	'type_error_title' => "Tipo errato",
	'type_error_message' => "Il parametro '%s' non corrisponde al Tipo di dato dichiarato nella rotta. Valore ricevuto: '%s'. Controlla la rotta nel file 'core/routes.map.php e verifica i dati scritti nell'URL: '%s'.",

	'type_error_int'          => "Il parametro '%s' richiede un intero. Valore ricevuto: '%s'",
	'type_error_dbl'          => "Il parametro '%s' richiede un numero decimale (double). Valore ricevuto: '%s'",
	'type_error_string'       => "Il parametro '%s' richiede solo una stringa valida. Valore passato: '%s'",
	'type_error_string_lower' => "Il parametro '%s' richiede solo lettere minuscole. Valore passato: '%s'",
	'type_error_string_upper' => "Il parametro '%s' richiede solo lettere maiuscole. Valore passato: '%s'",
	'type_error_bool'         => "Il parametro '%s' richiede true o false. Valore ricevuto: '%s'",
	'type_error_slug'         => "Il parametro '%s' richiede uno slug valido. Valore passato: '%s'",

	// Error custom description
	'type_error_custom_message' => "Il parametro '%s' non corrisponde al tipo dichiarato nella rotta. Valore ricevuto: '%s'. Probabilmente hai definito un tipo custom che non accetta questo valore.",

	// Error Middleware
	'mdware_title'   => "Errore caricamento del Middleware",
	'mdware_message' => "Si è verificato un errore nel caricamento del middleware: %s",

	// Language user Error
	'language_file_invalid_title'          => "File della lingua non trovato",
	'language_file_invalid_message'          => "\n1. Il file di lingua '%s.php' non è un file valido. Controlla in /application/{SITE_ID}/locale se il file esiste.\n2. Se la lingua selezionata non dovrebbe essere attiva, prova a controllare in /core/config.php che siano settate correttamente le lingue in: lang_locales",

	'language_unsupported_title'   => "Lingua non riconosciuta",
	'language_unsupported_message' => "\nLa lingua '%s' non è tra quelle supportate dal sistema.\nControlla Config -> lang_locales",

];