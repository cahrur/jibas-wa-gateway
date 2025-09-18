<?
/**[N]**
 * JIBAS WhatsApp Gateway
 * Konfigurasi modul integrasi WhatsApp
 */
?>
<?

if (file_exists('../include/global.patch.manager.php'))
{
    require_once('../include/global.patch.manager.php');
    ApplyGlobalPatch('..');
}
elseif (file_exists('../../include/global.patch.manager.php'))
{
    require_once('../../include/global.patch.manager.php');
    ApplyGlobalPatch('../..');
}
elseif (file_exists('../../../include/global.patch.manager.php'))
{
    require_once('../../../include/global.patch.manager.php');
    ApplyGlobalPatch('../../..');
}

require_once('module.patch.manager.php');
ApplyModulePatch();

if (file_exists('../include/mainconfig.php'))
{
    require_once('../include/mainconfig.php');
}
elseif (file_exists('../../include/mainconfig.php'))
{
    require_once('../../include/mainconfig.php');
}
elseif (file_exists('../../../include/mainconfig.php'))
{
    require_once('../../../include/mainconfig.php');
}

require_once('wapi.config.php');

$WA_DB_NAME = 'jbswa';
$WA_QUEUE_TABLE = 'jbswa.wa_queue';
$WA_SYNC_MARKER_TABLE = 'jbswa.wa_sync_marker';

$G_ENABLE_QUERY_ERROR_LOG = false;

?>
