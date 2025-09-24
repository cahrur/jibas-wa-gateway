<?
/**
 * Mengirim antrian WhatsApp menggunakan Wapisender.
 * Disarankan dijalankan via scheduler (cron/Task Scheduler) setiap 1 menit.
 */
?>
<?
require_once(__DIR__ . '/../include/config.php');
require_once(__DIR__ . '/../include/db_functions.php');
require_once(__DIR__ . '/../include/wapi_client.php');

const WA_DISPATCH_LIMIT = 50;

try
{
    WA_OpenDb();

    $sql = "SELECT id, sms_history_id, destination, message, sender_id, attempts\n            FROM " . $WA_QUEUE_TABLE . "\n           WHERE (\n                  (status = 0 AND (next_retry_at IS NULL OR next_retry_at <= NOW()))\n               OR (status = 2 AND attempts < " . (int)WAPI_MAX_RETRY . " AND (next_retry_at IS NULL OR next_retry_at <= NOW()))\n                )\n           ORDER BY id ASC\n           LIMIT " . WA_DISPATCH_LIMIT;

    $result = WA_Query($sql);
    $rows = WA_FetchAll($result);

    if (count($rows) === 0)
    {
        echo "Tidak ada antrian WhatsApp." . PHP_EOL;
        WA_CloseDb();
        exit;
    }

    foreach ($rows as $row)
    {
        $id = (int)$row['id'];
        $destination = $row['destination'];
        $message = $row['message'];
        $attempts = (int)$row['attempts'];

        try
        {
            $response = WAPI_SendText($destination, $message);
            $waMessageId = isset($response['data']['id']) ? $response['data']['id'] : '';
            $status = isset($response['data']['status']) ? $response['data']['status'] : '';

            $escResponse = WA_Escape(json_encode($response));
            $escWaId = WA_Escape($waMessageId);
            $escStatus = WA_Escape($status);

            $updateSql = "UPDATE " . $WA_QUEUE_TABLE . "\n                           SET status = 1,\n                               attempts = attempts + 1,\n                               sent_at = NOW(),\n                               wa_message_id = '$escWaId',\n                               last_response = '$escResponse',\n                               last_error = NULL,\n                               next_retry_at = NULL,\n                               updated_at = NOW(),\n                               wa_message_status = '$escStatus'\n                         WHERE id = $id";
            WA_Query($updateSql);

            echo 'Berhasil kirim ke ' . $destination . PHP_EOL;
        }
        catch (Exception $ex)
        {
            $attempts += 1;
            $escError = WA_Escape($ex->getMessage());
            $statusCode = ($attempts >= WAPI_MAX_RETRY) ? 3 : 2;
            $retryClause = ($statusCode === 2) ? ", next_retry_at = DATE_ADD(NOW(), INTERVAL " . (int)WAPI_RETRY_DELAY . " SECOND)" : ", next_retry_at = NULL";

            $updateSql = "UPDATE " . $WA_QUEUE_TABLE . "\n                           SET status = $statusCode,\n                               attempts = $attempts,\n                               last_error = '$escError',\n                               updated_at = NOW()" .
                         $retryClause .
                         " WHERE id = $id";
            WA_Query($updateSql);

            WA_LogError('wa_dispatch: ' . $ex->getMessage());
            echo 'Gagal kirim ke ' . $destination . ': ' . $ex->getMessage() . PHP_EOL;
        }
    }

    WA_CloseDb();
}
catch (Exception $e)
{
    if (function_exists('WA_RollbackTrans'))
        WA_RollbackTrans();

    WA_LogError('wa_dispatch fatal: ' . $e->getMessage());
    echo 'Kesalahan fatal: ' . $e->getMessage() . PHP_EOL;
}
?>
