<?
/**[N]**
 * Konfigurasi kredensial Wapisender
 */
?>
<?

// URL dasar layanan Wapisender
if (!defined('WAPI_BASE_URL'))
    define('WAPI_BASE_URL', 'https://wapisender.id/api/v5');

// Ganti sesuai kredensial yang diberikan oleh Wapisender
if (!defined('WAPI_API_KEY'))
    define('WAPI_API_KEY', '776C70E2-2068-44C1-9873-9DB408E71E38');

if (!defined('WAPI_DEVICE_KEY'))
    define('WAPI_DEVICE_KEY', 'LSDRBJ');

// Alias default pengirim untuk logging internal
if (!defined('WAPI_SENDER_ALIAS'))
    define('WAPI_SENDER_ALIAS', 'JIBAS.WA');

// Batas percobaan pengiriman ulang (retry)
if (!defined('WAPI_MAX_RETRY'))
    define('WAPI_MAX_RETRY', 3);

// Jeda antar percobaan (dalam detik)
if (!defined('WAPI_RETRY_DELAY'))
    define('WAPI_RETRY_DELAY', 120);

?>
