<?php
declare(strict_types = 1);

namespace main {

use Phalcon\Http\Request;

function start(): void
{
    $request  = new Request();

    $out = '';
    $bf  = '';

    // Check if is POST, is a valid ID and ok
    if (true !== $request->isPost())
        \error\send(405);

    if (true !== $request->has('id') OR
        40   !== strlen($request->getQuery('id')) OR
        true !== ctype_alnum($request->getQuery('id')))
        \error\send(400);
    else
        $bf = APP_DATA.'/'.$request->getQuery('id').'.json';

    if (!\file\exist($bf) OR !\file\is_readable($bf, true))
        \error\send(500);

    \config\set(\file\get_decode($bf));

    // Check POST request and format to str
    if (true === \config\get('honeypot') AND (
        $request->hasPost('name') OR
        $request->hasPost('email') OR
        $request->hasPost('message') OR
        $request->hasPost('honeypot')))
        \error\send(418);

    if (empty($id['acceptable_form']))
        $out = $request->getPost(null, ['trim', 'string']);
    else
        $out = act_form($request->getPost(null, ['trim', 'string']));

    $out = format_body($out);

    // Check and send email
    $bf = \config\get('email');
    if (!empty($bf['email']['list']))
        email_send($bf['email']);

    // Check and send request

    // Return to user
}

function email_send(array $datas): void
{
    $imap = $datas['smtp'];
    $imap = imap_open(
        $imap['server'].$imap['flags'],
        $imap['user'],
        $imap['password'],
        null, 3
    );

    if (imap_close($imap))
        \error\log('Imap close :'.$imap['user'].'@'.$imap['server']);
}

function act_form(array $in): array
{
    $out = array();

    foreach (\config\get('acceptable_form') as $value)
        $out[$value] = $in[$value];

    return $out;
}

function format_body(array $in): string
{
    $buff= \config\get('title')['title'];
    $out = '# Email du service '.$buff.PHP_EOL.PHP_EOL;

    foreach ($in as $key => &$value) {
        $out .= '## '.$key.PHP_EOL;
        $out .= $value.PHP_EOL.PHP_EOL;
    }

    return $out;
}
}

namespace config {
function core(int $action, array $args = array()): array
{
    static $datas = array();

    if (1 === $action) {
        return ($datas);
    } else if (2 === $action) {
        $datas = array_merge($datas, $args);
        return ($args);
    }

    return (array());
}

function get(): array
{
    $datas = core(1);

    if (0 >= func_num_args())
        return $datas;

    $buff  = array();
    $args  = func_get_args();
    
    foreach ($args as &$arg)
        $buff[$arg] = $datas[$arg];

    return ($buff);
}

function set(array $args): array
{
    return (core(2, $args));
}
}

namespace file {
function is_readable(string $item, bool $is_file = false): bool
{
    if (!\is_readable($item) || ($is_file && !is_file($item)) || (!$is_file && is_file($item)))
        return (false);

    return (true);
}

function exist(string $item): bool
{
    if (!file_exists($item))
        return (false);

    return (true);
}

function get_decode(string $file): array
{
    $content = file_get_contents($file);
    $content = json_decode($content, true);

    if (json_last_error())
        \error\send(500, 'Json '.json_last_error_msg().' in '.$file);

    return ($content);
}
}

namespace error {
function send(int $code, string $str = NULL): void
{
    $msg = $why = '';
    $rps = new \Phalcon\Http\Response();

    switch ($code) {
        case 400:
            $msg = 'Bad Request';
            $why = 'ID inconsistent or not properly formed';
            break;
        case 405:
            $msg = 'Method Not Allowed';
            $why = 'Only POST request is allowed';
            break;
        case 418:
            $msg = 'I\'m a teapot';
            $why = 'No bot allowed';
            break;
        case 500:
            $msg = 'Internal Server Error';
            break;
    }

    $rps->setStatusCode($code, $msg);
    $rps->setHeader('Content-Type', 'application/json');
    $rps->setContent('{"error": {"code": '.$code.', "message": "'.$msg.'", "why": "'.$why.'"}}');
    $rps->send();

    if (!empty($str))
        $msg = &$str;

    \error\log($msg);
}

function log(string $str): void
{
    error_log('['.date(DATE_RFC2822).'][error]'.$str, 0);

    exit(1);
}
}

