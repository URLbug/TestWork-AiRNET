<?php

/**
    Войдите в bash панель сервиса `api`.
    Введите команду `docker compose exec api bash` для того чтобы войти в сервис.
    Далее введите команду `php src/test.php` для проверки, что пользователь может изменить заявку.
    
    Для проверки архивной заявки добавте флаг:
    --archive 

    Для чтобы войти от имени редактора добавте следущий флаг:
    --role=editor

    Для того чтобы изменить source_id, используйте следующий флаг:
    --source-id=<id источника>
*/

global $_ENDPOINT;
$_ENDPOINT = 'http://localhost:8080';

$id = 101;
if(in_array('--archive', $argv)) {
    $id = 104;
}

$role = 'viewer';
if(in_array('--role=editor', $argv)) {
    $role = 'editor';
}

$source_id = 1;
foreach($argv as $arg) {
    $source = explode('=', $arg);
    if($source[0] == '--source-id') {
        $source_id = (int)$source[1];
        break;
    }
}

$user = login($role);
$csrf = $user['response']->csrf;
$assessment_session = $user['cookies']['assessment_session'];

$url = "$_ENDPOINT/api/tickets/$id/source";

$data = [
    'source_id' => $source_id
];
$jsonData = json_encode($data);

$headers = [
    'Accept: */*',
    'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'Connection: keep-alive',
    'Content-Type: application/json',
    "Referer: $_ENDPOINT",
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    "X-CSRF-Token: $csrf",
    'sec-ch-ua: "Google Chrome";v="155", "Chromium";v="155", "Not(A:Brand";v="24"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Вернуть ответ в виде строки, а не выводить сразу
curl_setopt($ch, CURLOPT_POST, true);           // Метод POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Тело запроса (JSON)
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // Установка заголовков

curl_setopt($ch, CURLOPT_COOKIE, "assessment_session=$assessment_session");

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Ошибка cURL: ' . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP код: " . $httpCode . "\n";
    echo "Ответ сервера:\n" . $response;
}

curl_close($ch);

function login($role) {
    global $_ENDPOINT;
    $url = "$_ENDPOINT/api/demo/login";

    $data = [
        'role' => $role
    ];
    $jsonData = json_encode($data);

    $headers = [
        'Accept: */*',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        'Connection: keep-alive',
        'Content-Type: application/json',
        'Origin: http://localhost:5173',
        'Referer: http://localhost:5173/src/test.php',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
        'sec-ch-ua: "Google Chrome";v="155", "Chromium";v="155", "Not(A:Brand";v="24"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"'
    ];

    // Инициализация cURL
    $ch = curl_init($url);

    // Настройки cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Вернуть ответ строкой
    curl_setopt($ch, CURLOPT_POST, true);           // Метод POST (так как есть тело запроса)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Тело запроса (JSON)
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // Заголовки
    curl_setopt($ch, CURLOPT_HEADER, true);

    // Выполнение запроса
    $response = curl_exec($ch);

    // Отделяем заголовки от тела ответа
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    // Ищем все заголовки Set-Cookie с помощью регулярного выражения
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $responseHeaders, $matches);

    $cookies = [];
    if (!empty($matches[1])) {
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
    }

    // Закрываем сеанс
    curl_close($ch);

    return ['response' => json_decode($responseBody), 'cookies' => $cookies];
}