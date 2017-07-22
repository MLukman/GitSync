<?php
// Directory where GitSync library is located
define('GITSYNC_LIB_DIR', realpath(__DIR__.'/../'));

// Directory where index.php is running from
define('GITSYNC_ROOT_DIR', dirname($_SERVER["SCRIPT_FILENAME"]));

// The route name where the root URL for GitSync can be generated
define('GITSYNC_ROOT_ROUTE', 'context_index');
