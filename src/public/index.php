<?php
# Copyright 2019 Iglou.eu
# Copyright 2019 Adrien Kara
# V. Beta 0.1.0 - 26 mars 2019
# license that can be found in the LICENSE file.

define('APP_FOOTER', 'Service fournis par numericoop.fr');

define('APP_NAME', 'MailSwg');
define('APP_VERSION', '0.1.0');

// Define the absolute path
define('APP_ROOT', dirname(__DIR__) );
define('APP_CORE', APP_ROOT.'/core' );
define('APP_DATA', APP_ROOT.'/datas');

require APP_CORE.'/main.php';

main\start();

exit;
