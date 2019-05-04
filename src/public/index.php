<?php
# Copyright 2019 Iglou.eu
# Copyright 2019 Adrien Kara
# V. Beta 0.1.0 - 26 mars 2019
# license that can be found in the LICENSE file.

define('APP_NAME', 'MailSwg');

// Define the absolute path
define('APP_ROOT', dirname(__DIR__) );
define('APP_CORE', APP_ROOT.'/core' );
define('APP_DATA', APP_ROOT.'/datas');

require APP_CORE.'/main.php';

main\start();

exit;
