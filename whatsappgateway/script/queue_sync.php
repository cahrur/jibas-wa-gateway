<?
/**
 * Sinkronisasi data SMS (jbssms.outboxhistory) ke antrian WhatsApp.
 * Jalankan via CLI / scheduler, misal setiap 1 menit.
 */
?>
<?
require_once(__DIR__ . '/../include/config.php');
require_once(__DIR__ . '/../include/db_functions.php');

const WA_SYNC_FETCH_LIMIT = 500;

function NormalizeLineBreaks($message)
{
    return str_replace(array("\r\n", "\r"), "\n", $message);
}

function PrepareWhatsappMessage($message)
{
    $msg = NormalizeLineBreaks($message);
    return str_replace("\n", "\r\n", $msg);
}

try
{
    WA_OpenDb();

    $markerSql = "SELECT last_outboxhistory_id FROM " . $WA_SYNC_MARKER_TABLE . " WHERE id = 1";
    $lastProcessed = WA_FetchSingle($markerSql);

    if ($lastProcessed === null)
    {
        WA_Query("INSERT INTO " . $WA_SYNC_MARKER_TABLE . " (id, last_outboxhistory_id, updated_at) VALUES (1, 0, NOW()) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)");
        $lastProcessed = 0;
    }

    $sql = "SELECT oh.ID, oh.DestinationNumber, oh.Text, oh.SenderID, oh.idsmsgeninfo, oh.InsertIntoDB, si.info AS sms_info\n            FROM jbssms.outboxhistory oh\n            LEFT JOIN jbssms.smsgeninfo si ON si.replid = oh.idsmsgeninfo\n           WHERE oh.ID > " . (int)$lastProcessed . "\n           ORDER BY oh.ID ASC\n           LIMIT " . WA_SYNC_FETCH_LIMIT;
    $result = WA_Query($sql);

    $rows = WA_FetchAll($result);
    if (count($rows) === 0)
    {
        echo "Tidak ada data baru." . PHP_EOL;
        WA_CloseDb();
        exit;
    }

    WA_BeginTrans();

    $lastPengumumanSchedule = 0;
    $maxId = (int)$lastProcessed;
    foreach ($rows as $row)
    {
        $smsId = (int)$row['ID'];
        $destination = trim($row['DestinationNumber']);
        $rawMessage = isset($row['Text']) ? $row['Text'] : '';
        $sender = isset($row['SenderID']) ? trim($row['SenderID']) : '';
        $idsmsgeninfo = isset($row['idsmsgeninfo']) ? (int)$row['idsmsgeninfo'] : null;

        if ($smsId > $maxId)
            $maxId = $smsId;

        if (strlen($destination) < 5)
            continue;

        // Normalize nomor tujuan: ganti leading 0 dengan 62 jika perlu
        if (substr($destination, 0, 1) === '0')
            $destination = '62' . substr($destination, 1);

        $destination = preg_replace('/[^0-9A-Za-z@._-]/', '', $destination);

        if ($rawMessage === '')
            continue;

        $smsInfo = isset($row['sms_info']) ? trim($row['sms_info']) : '';
        $waMessage = PrepareWhatsappMessage($rawMessage);

        $escDest = WA_Escape($destination);
        $escMessage = WA_Escape($waMessage);
        $escSender = WA_Escape($sender);

        $nextRetryValue = 'NULL';

        if ($smsInfo !== '' && stripos($smsInfo, 'pengumuman') !== false)
        {
            $nowTimestamp = time();
            if ($lastPengumumanSchedule < $nowTimestamp)
                $lastPengumumanSchedule = $nowTimestamp;

            $delaySeconds = mt_rand(10, 20);
            $scheduledTimestamp = $lastPengumumanSchedule + $delaySeconds;
            $lastPengumumanSchedule = $scheduledTimestamp;
            $nextRetryValue = "'" . date('Y-m-d H:i:s', $scheduledTimestamp) . "'";
        }

        $insertSql = "INSERT INTO " . $WA_QUEUE_TABLE . " (sms_history_id, destination, message, sender_id, idsmsgeninfo, status, attempts, created_at, next_retry_at)\n                       VALUES ($smsId, '$escDest', '$escMessage', '$escSender', " . ($idsmsgeninfo === null ? 'NULL' : $idsmsgeninfo) . ", 0, 0, NOW(), %NEXT_RETRY%)\n                       ON DUPLICATE KEY UPDATE\n                           destination = VALUES(destination),\n                           message = VALUES(message),\n                           sender_id = VALUES(sender_id),\n                           idsmsgeninfo = VALUES(idsmsgeninfo),\n                           status = CASE WHEN status = 1 THEN status ELSE VALUES(status) END,\n                           next_retry_at = CASE WHEN status = 1 THEN next_retry_at ELSE COALESCE(next_retry_at, VALUES(next_retry_at)) END,\n                           updated_at = NOW()";
        $insertSql = str_replace('%NEXT_RETRY%', $nextRetryValue, $insertSql);
        WA_Query($insertSql);
    }

    WA_Query("UPDATE " . $WA_SYNC_MARKER_TABLE . " SET last_outboxhistory_id = $maxId, updated_at = NOW() WHERE id = 1");

    WA_CommitTrans();
    WA_CloseDb();

    echo "Sinkronisasi selesai. Diproses " . count($rows) . " data." . PHP_EOL;
}
catch (Exception $e)
{
    if (function_exists('WA_RollbackTrans'))
        WA_RollbackTrans();

    WA_LogError('queue_sync: ' . $e->getMessage());
    echo 'Gagal sinkronisasi: ' . $e->getMessage() . PHP_EOL;
}
?>
