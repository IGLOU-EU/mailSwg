<?php
declare(strict_types = 1);

namespace main {

use Phalcon\Http\Request;

function start(): void
{

    $datas   = '';
    $buffer  = '';
    $config  = Array();
    $request = new Request();

    // Check if is POST, is a valid ID and ok
    if (true !== $request->isPost())
        \error\send(405);

    if (true !== $request->has('id') OR
        40   !== strlen($request->getQuery('id')) OR
        true !== ctype_alnum($request->getQuery('id')))
        \error\send(400);
    else
        $buffer = APP_DATA.'/'.$request->getQuery('id').'.json';

    if (!\file\exist($buffer) OR !\file\is_readable($buffer, true))
        \error\send(500);

    $config = \file\get_decode($buffer);

    // Check POST request and format to str
    if (true === $config['honeypot'] AND !(
        empty($_POST['name']) OR
        empty($_POST['email']) OR
        empty($_POST['message']) OR
        empty($_POST['honeypot'])
       )) {
        \error\send(418);
    }

    $buffer = $request->getPost(null, ['trim', 'string']);

    if (!empty($config['acceptable_form']))
        $buffer = acceptable_form($buffer, $config['acceptable_form']);

    $datas = format_body($buffer, $config['title']);

    // Check and send email
    if (!empty($config['email']['list']))
        email_send($datas, $config);

    // Check and send request
    if (!empty($config['request']))
        request_send($datas, $config);

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

function request_send(array &$msg, array &$datas): void
{
    $title = substr(json_encode($msg['title']), 1, -1);
    $body  = substr(json_encode($msg['body']), 1, -1);

    foreach ($datas['request'] as $rqst) {
        if (empty($rqst['url']))
            continue;

        $url = parse_url($rqst['url']);
        $url['type']  = empty($rqst['datas']) ? 'GET' : 'POST';
        $url['query'] = isset($url['query']) ? $url['query'] : '';

        if ('https' === $url['scheme']) {
            $url['pfix'] = 'tls://';
            $url['port'] = empty($url['port']) ? 443 : $url['port'];
        } else {
            $url['pfix'] = 'tcp://';
            $url['port'] = empty($url['port']) ? 80 : $url['port'];
        }
        // Build datas
        $rqst['datas'] = str_replace('MAILSWG_TITLE', $title, $rqst['datas']);
        $rqst['datas'] = str_replace('MAILSWG_BODY', $body, $rqst['datas']);

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
            \error\send(500, '['.$datas['title'].'] Can\'t send to : '.$serv['server']);

        fputs($sock, $header.$rqst['datas'].PHP_EOL);

        if (false === strpos(fgets($sock, 128), '200 OK'))
            \error\send(500, '['.$datas['title'].'] Can\'t send to : '.$serv['server']);

        fclose($sock);
    }
}

function email_send(array &$msg, array &$datas): void
{
    $errno  = 0;
    $errtr  = '';
    $serv   = &$datas['email']['smtp'];
    $emails = &$datas['email']['list'];

    if (!($sock  = fsockopen($serv['server'], $serv['port'], $errno, $errtr, 12)))
        \error\send(500, '['.$datas['title'].'] Can\'t connecting to : '.$serv['server'].' ('.$errno.') ('.$errtr.')');

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

    foreach ($emails as $mail) {
        fwrite($sock, 'RCPT TO: <'.$mail.'>'.PHP_EOL);
        sock_check($sock, '250');
    }

    fwrite($sock, 'DATA'.PHP_EOL);
    sock_check($sock, '354');

    fwrite($sock, ''
        .'Subject: =?ISO-8859-15?Q?'.imap_8bit($msg['title']).'?='.PHP_EOL
        .'From: =?ISO-8859-15?Q?'.imap_8bit('[BOT] '.$datas['title']).'?= <mailbot@'.$serv['dns'].'>'.PHP_EOL
        .'To: <'.implode('>, <', $emails).'>'.PHP_EOL
        .'X-Mailer: '.APP_NAME.' '.APP_VERSION.PHP_EOL
        .'Message-ID: <'.sha1(date(DATE_RFC2822).$serv['dns']).'@'.$serv['server'].'>'.PHP_EOL
        .'Date:'.date(DATE_RFC2822).PHP_EOL
        .'MIME-Version: 1.0'.PHP_EOL
        .'Content-Type: text/plain; charset=utf-8'.PHP_EOL
        .'Content-Transfer-Encoding: 8bit'.PHP_EOL
        .'Content-Language: fr'
        .PHP_EOL.PHP_EOL
        .$msg['body'].PHP_EOL
    );

    fwrite($sock, '.'.PHP_EOL);
    sock_check($sock, '250');

    fwrite($sock, 'QUIT'.PHP_EOL);

    if (!fclose($sock))
        \error\log('Sock close for '.$datas['title'], false);
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

function acceptable_form(array &$in, array &$acceptable_form): array
{
    $out = array();

    foreach ($acceptable_form as $value)
        $out[$value] = $in[$value];

    return $out;
}

function format_body(array &$in, string &$app_title): array
{
    $title = $out = '# Message via le service '.$app_title;
    $out  .= PHP_EOL.PHP_EOL;

    foreach ($in as $key => &$value) {
        if (empty($value) || 'submit' === $key)
            continue;

        $out .= '## '.strtoupper($key).' :'.PHP_EOL;
        $out .= $value.PHP_EOL.PHP_EOL;
    }

    $out .= '---'.PHP_EOL.'_Generated with '.APP_NAME.'_';

    if (!empty(APP_FOOTER))
        $out .= PHP_EOL.'_'.APP_FOOTER.'_';

    return array('title' => $title, 'body' => $out);
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
