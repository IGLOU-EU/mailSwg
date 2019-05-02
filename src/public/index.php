<?php
/* ----------
* ! NoCopyright, NoCopyleft for a free world !
* ! PasDeCopyright, PasDeCopyleft pour un monde libre !
* ----------
* Copyright (C) [2019] [Kara.Adrien]   <adrien@iglou.eu>
* ----------
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
* ----------
* http://www.apache.org/licenses/LICENSE-2.0
* ----------
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
* ----------
* Beta 0.1.0 - 26 mars 2019
* ----------
* MailSwg
** ---------- */

define('APP_NAME', 'MailSwg');

// Define the absolute path
define('APP_ROOT', dirname(__DIR__) );
define('APP_CORE', APP_ROOT.'/core' );
define('APP_DATA', APP_ROOT.'/datas');

require APP_CORE.'/main.php';

main\start();

exit;
