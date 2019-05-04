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
        email_send($out, $bf['email']);

    // Check and send request
    $bf = \config\get('request');
    if (!empty($bf['request']))
        request_send($out, $bf['request']);

    // Return to user
    success();
}

function success(): void
{
    $rps = new \Phalcon\Http\Response();
    $rps->setStatusCode(200, 'OK');
    $rps->setHeader('Content-Type', 'application/json');
    $rps->setContent('{"success":true}');
    $rps->send();
}
function request_send(string $msg, array $datas): void
{
    $msg   = substr(json_encode($msg), 1, -1);
    $title = \config\get('title')['title'];

    foreach ($datas as $rqst) {
        $url = parse_url($rqst['url']);
        $url['type'] = empty($rqst['datas']) ? 'GET' : 'POST';

        if ('https' === $url['scheme']) {
            $url['pfix'] = 'tls://';
            $url['port'] = empty($url['port']) ? 443 : $url['port'];
        } else {
            $url['pfix'] = 'tcp://';
            $url['port'] = empty($url['port']) ? 80 : $url['port'];
        }
        // Build datas
        $rqst['datas'] = str_replace('MAILSWG_TITLE', APP_NAME.' | Message from '.$title.' service', $rqst['datas']);
        $rqst['datas'] = str_replace('MAILSWG_BODY', $msg, $rqst['datas']);

        // Build header
        $header  = $url['type'].' '.$url['path'].$url['query'].' HTTP/1.1'.PHP_EOL;
        $header .= 'Host: '.$url['host'].PHP_EOL;
        $header .= implode(PHP_EOL, $rqst['header']).PHP_EOL;

        if ('POST' === $url['type'])
            $header .= 'Content-length: '.strlen($rqst['datas']).PHP_EOL;

        if (isset($url['user']) AND isset($url['pass']))
            $header .= 'Authorization: Basic '.base64_encode($url['user'].':'.$url['pass']);

        $header .= 'Connection: close'.PHP_EOL.PHP_EOL;

        // Send request
        if (!($sock = fsockopen($url['pfix'].$url['host'], $url['port'])))
            \error\send(500, '['.$title.'] Can\'t send to : '.$serv['server']);

        fputs($sock, $header.$rqst['datas'].PHP_EOL);

        if (false === strpos(fgets($sock, 128), '200 OK'))
            \error\send(500, '['.$title.'] Can\'t send to : '.$serv['server']);

        fclose($sock); 
    }
}

function email_send(string $msg, array $datas): void
{
    $errno = 0;
    $errtr = '';

    $serv  = $datas['smtp'];
    $title = \config\get('title')['title'];
    $sock  = fsockopen($serv['server'], $serv['port'], $errno, $errtr, 12);

    if (!$sock)
        \error\send(500, '['.$title.'] Can\'t connecting to : '.$serv['server'].' ('.$errno.') ('.$errtr.')');

    sock_check($sock, '220');

    fwrite($sock, 'EHLO '.$serv['server'].PHP_EOL);
    sock_check($sock, '250');

    if (25 !== $serv['port'] OR 465 !== $serv['port']) {
        fwrite($sock, 'STARTTLS'.PHP_EOL);
        sock_check($sock, '220');

        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        fwrite($sock, 'EHLO '.$serv['server'].PHP_EOL);
        sock_check($sock, '250');
    }

    fwrite($sock, 'AUTH LOGIN'.PHP_EOL);
    sock_check($sock, '334');

    fwrite($sock, base64_encode($serv['user']).PHP_EOL);
    sock_check($sock, '334');

    fwrite($sock, base64_encode($serv['password']).PHP_EOL);
    sock_check($sock, '235');

    fwrite($sock, 'MAIL FROM: <mailbot@'.$serv['server'].'>'.PHP_EOL);
    sock_check($sock, '250');

    foreach ($datas['list'] as $mail) {
        fwrite($sock, 'RCPT TO: <'.$mail.'>'.PHP_EOL);
        sock_check($sock, '250');
    }

    fwrite($sock, 'DATA'.PHP_EOL);
    sock_check($sock, '354');

    fwrite($sock, ''
        .'Subject: '.APP_NAME.' | Message from '.$title.' service'.PHP_EOL
        .'From: (BOT)'.$title.' <mailbot@'.$serv['server'].'>'.PHP_EOL
        .'To: <'.implode('>, <', $datas['list']).'>'.PHP_EOL
        .'X-Mailer: '.APP_NAME.PHP_EOL
        .'Content-Type: text/plain; charset=UTF-8'.PHP_EOL
        .PHP_EOL.PHP_EOL
        .$msg.PHP_EOL
    );

    fwrite($sock, '.'.PHP_EOL);
    sock_check($sock, '250');

    fwrite($sock, 'QUIT'.PHP_EOL);

    if (!fclose($sock))
        \error\log('Sock close for '.$title, false);
}

function sock_check($sock, string $expect): bool {
    do {
        $response = fgets($sock, 1024);

        if (false === $response) {
            \error\log('SMTP faillure no response code', false);
            return false;
        }
    } while (substr((string) $response, 3, 1) != ' ');

    if (substr((string) $response, 0, 3) != $expect) {
        \error\log('SMTP faillure code '.$response, false);
        return false;
    }

    stream_set_timeout($sock, 300);
    set_time_limit(310);

    return true;
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
    $out = '# Message via le service '.$buff.PHP_EOL.PHP_EOL;

    foreach ($in as $key => &$value) {
        $out .= '## '.$key.PHP_EOL;
        $out .= $value.PHP_EOL.PHP_EOL;
    }

    $out .= '*Genrated by '.APP_NAME.'*';
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

function log(string $str, bool $ext=true): void
{
    error_log('[error]'.$str, 0);

    if ($ext)
        exit(1);
}
}

