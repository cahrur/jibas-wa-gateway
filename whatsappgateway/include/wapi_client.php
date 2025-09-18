<?
/**[N]**
 * Klien HTTP sederhana untuk terhubung ke Wapisender
 */
?>
<?
require_once('wapi.config.php');
require_once('db_functions.php');

function WAPI_Request($endpoint, $payload, $method = 'POST')
{
    $url = rtrim(WAPI_BASE_URL, '/') . '/' . ltrim($endpoint, '/');

    if (!extension_loaded('curl'))
        throw new Exception('Ekstensi PHP cURL belum aktif. Aktifkan sebelum menggunakan WhatsApp Gateway.');

    $ch = curl_init();

    if (strtoupper($method) === 'GET')
    {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    else
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    curl_close($ch);

    if ($errno !== 0)
        throw new Exception('Gagal melakukan request ke Wapisender: ' . $error);

    if ($statusCode >= 500)
        throw new Exception('Server Wapisender mengembalikan status ' . $statusCode . ': ' . $body);

    $json = json_decode($body, true);
    if (!is_array($json))
        throw new Exception('Respons Wapisender tidak valid: ' . $body);

    return $json;
}

function WAPI_SendText($destination, $message)
{
    $payload = array(
        'api_key' => WAPI_API_KEY,
        'device_key' => WAPI_DEVICE_KEY,
        'destination' => $destination,
        'message' => $message
    );

    $response = WAPI_Request('message/text', $payload, 'POST');

    if (!isset($response['status']) || $response['status'] !== 'ok')
    {
        $reason = isset($response['message']) ? $response['message'] : json_encode($response);
        throw new Exception('Pengiriman WhatsApp gagal: ' . $reason);
    }

    return $response;
}

?>
