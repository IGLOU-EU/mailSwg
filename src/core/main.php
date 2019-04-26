<?php
declare(strict_types = 1);

namespace main {

use Phalcon\Http\Request;
use Phalcon\Http\Response;

function start(): void
{
    $request  = new Request();
    $response = new Response();

    $id = '';

    if (true !== $request->isPost())
        \error\send(405);

    if (true !== $request->has('id') OR
        40   !== strlen($request->getQuery('id')) OR
        true !== ctype_alnum($request->getQuery('id')))
        \error\send(400);
    else
        $id = APP_DATA.'/'.$request->getQuery('id').'.json';

    if (!\file\exist($id) OR !\file\is_readable($id, true))
        \error\send(500);

    $id = \file\get_decode($id);

    // si acform
    // ou si pas
    var_dump($request->getRawBody()); // retourn en claire les &

    $response->send();
}

function format_body(): void
{
    
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
            $msg = 'I’m a teapot';
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

