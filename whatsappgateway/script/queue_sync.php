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

    $sql = "SELECT ID, DestinationNumber, Text, SenderID, idsmsgeninfo, InsertIntoDB\n            FROM jbssms.outboxhistory\n           WHERE ID > " . (int)$lastProcessed . "\n           ORDER BY ID ASC\n           LIMIT " . WA_SYNC_FETCH_LIMIT;
    $result = WA_Query($sql);

    $rows = WA_FetchAll($result);
    if (count($rows) === 0)
    {
        echo "Tidak ada data baru." . PHP_EOL;
        WA_CloseDb();
        exit;
    }

    WA_BeginTrans();

    $maxId = (int)$lastProcessed;
    foreach ($rows as $row)
    {
        $smsId = (int)$row['ID'];
        $destination = trim($row['DestinationNumber']);
        $message = isset($row['Text']) ? trim($row['Text']) : '';
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

        if (strlen($message) === 0)
            continue;

        $escDest = WA_Escape($destination);
        $escMessage = WA_Escape($message);
        $escSender = WA_Escape($sender);

        $insertSql = "INSERT INTO " . $WA_QUEUE_TABLE . " (sms_history_id, destination, message, sender_id, idsmsgeninfo, status, attempts, created_at)\n                       VALUES ($smsId, '$escDest', '$escMessage', '$escSender', " . ($idsmsgeninfo === null ? 'NULL' : $idsmsgeninfo) . ", 0, 0, NOW())\n                       ON DUPLICATE KEY UPDATE\n                           destination = VALUES(destination),\n                           message = VALUES(message),\n                           sender_id = VALUES(sender_id),\n                           idsmsgeninfo = VALUES(idsmsgeninfo),\n                           status = CASE WHEN status = 1 THEN status ELSE VALUES(status) END,\n                           updated_at = NOW()";
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
