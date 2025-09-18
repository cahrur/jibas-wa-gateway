<?
/**[N]**
 * Fungsi utilitas koneksi database untuk WhatsApp Gateway
 */
?>
<?
require_once('config.php');

$WA_DB_CONN = null;
$WA_DB_CLOSED = false;

function WA_OpenDb($dbName = null)
{
    global $db_host, $db_user, $db_pass, $WA_DB_NAME, $WA_DB_CONN;

    $targetDb = $dbName === null ? $WA_DB_NAME : $dbName;

    mysqli_report(MYSQLI_REPORT_OFF);

    $WA_DB_CONN = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$WA_DB_CONN)
        throw new Exception('Tidak dapat terhubung ke server database: ' . mysqli_connect_error());

    $select = @mysqli_select_db($WA_DB_CONN, $targetDb);
    if (!$select)
        throw new Exception('Tidak dapat membuka database ' . $targetDb . ': ' . mysqli_error($WA_DB_CONN));

    @mysqli_query($WA_DB_CONN, "SET lc_time_names = 'id_ID'");
    @mysqli_query($WA_DB_CONN, "SET time_zone = 'Asia/Jakarta'");

    return $WA_DB_CONN;
}

function WA_CloseDb()
{
    global $WA_DB_CONN, $WA_DB_CLOSED;

    if ($WA_DB_CONN === null)
        return;

    if ($WA_DB_CLOSED)
        return;

    @mysqli_close($WA_DB_CONN);
    $WA_DB_CLOSED = true;
}

function WA_Query($sql)
{
    global $WA_DB_CONN;

    $result = @mysqli_query($WA_DB_CONN, $sql);
    if (mysqli_errno($WA_DB_CONN) > 0)
        throw new Exception('Gagal eksekusi query: ' . mysqli_error($WA_DB_CONN) . ' | SQL: ' . $sql);

    return $result;
}

function WA_Escape($value)
{
    global $WA_DB_CONN;
    return mysqli_real_escape_string($WA_DB_CONN, $value);
}

function WA_BeginTrans()
{
    global $WA_DB_CONN;
    @mysqli_query($WA_DB_CONN, 'SET AUTOCOMMIT=0');
    @mysqli_query($WA_DB_CONN, 'BEGIN');
}

function WA_CommitTrans()
{
    global $WA_DB_CONN;
    @mysqli_query($WA_DB_CONN, 'COMMIT');
    @mysqli_query($WA_DB_CONN, 'SET AUTOCOMMIT=1');
}

function WA_RollbackTrans()
{
    global $WA_DB_CONN;
    @mysqli_query($WA_DB_CONN, 'ROLLBACK');
    @mysqli_query($WA_DB_CONN, 'SET AUTOCOMMIT=1');
}

function WA_FetchAll($result)
{
    $rows = array();
    while($row = mysqli_fetch_assoc($result))
        $rows[] = $row;
    return $rows;
}

function WA_FetchSingle($sql)
{
    $res = WA_Query($sql);
    $row = mysqli_fetch_row($res);
    return $row === null ? null : $row[0];
}

function WA_LogError($message)
{
    $logPath = @realpath(@dirname(__FILE__)) . '/../log';
    if (!@file_exists($logPath))
        @mkdir($logPath, 0755, true);

    $logFile = $logPath . '/whatsappgateway-error.log';
    $fp = @fopen($logFile, 'a');
    if ($fp)
    {
        @fwrite($fp, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\r\n");
        @fclose($fp);
    }
}

?>
