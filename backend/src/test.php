<?php

/**
    Войдите в bash панель сервиса `api`.
    Введите команду `docker compose exec api bash` для того чтобы войти в сервис.
    Далее введите команду `php src/test.php` для проверки скрипта.
*/

$url = 'http://localhost:8080/api/tickets/101/source';

$data = [
    'source_id' => 2
];
$jsonData = json_encode($data);

$headers = [
    'Accept: */*',
    'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'Connection: keep-alive',
    'Content-Type: application/json',
    'Referer: http://localhost:5173/',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
    'X-CSRF-Token: 3101199e92836b752dbecef034e8076b3a5ff9e88266b836',
    'sec-ch-ua: "Google Chrome";v="155", "Chromium";v="155", "Not(A:Brand";v="24"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Вернуть ответ в виде строки, а не выводить сразу
curl_setopt($ch, CURLOPT_POST, true);           // Метод POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Тело запроса (JSON)
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // Установка заголовков

curl_setopt($ch, CURLOPT_COOKIE, 'assessment_session=82a399f577aecafc1c1d3da42734e3fe');

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Ошибка cURL: ' . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP код: " . $httpCode . "\n";
    echo "Ответ сервера:\n" . $response;
}

curl_close($ch);