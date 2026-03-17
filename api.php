<?php
header('Content-Type: application/json; charset=utf-8');

$q = $_GET['q'] ?? '';
$tipo = $_GET['tipo'] ?? 'tracking';

if (empty($q)) {
    echo json_encode([]);
    exit;
}

// URL completa hacia tu controlador en el servidor remoto
$url_remota = "https://cpsystemsnic.com/controller/yaya/paquete/c_get_rastreo_paquetes.php?q=" . urlencode($q) . "&tipo=" . urlencode($tipo);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_remota);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

// 1. Ejecutamos UNA sola vez
$response = curl_exec($ch);

// 2. Verificamos si hubo error de conexión
if (curl_errno($ch)) {
    echo json_encode(["error" => "Error de conexión: " . curl_error($ch)]);
} else {
    // 3. Si todo salió bien, imprimimos la respuesta
    echo $response;
}

// 4. Limpiamos el objeto (PHP 8 Style)
$ch = null;