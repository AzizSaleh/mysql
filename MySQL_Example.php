<?php
/**
 * Example usage of the object - This page should work
 * on a mysql_* discontinued php version - You will
 * need to update connection/database/table info
 *
 * @author    Aziz S. Hussain <azizsaleh@gmail.com>
 * @copyright GPL license 
 * @license   http://www.gnu.org/copyleft/gpl.html 
 * @link      http://www.AzizSaleh.com
 */

// Include the definitions
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Definitions.php');

// Include the object
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL.php');

// Include the mysql_* functions
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Functions.php');

$link = mysql_connect('localhost', 'root', '');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}

// make foo the current db
$db_selected = mysql_select_db('unit_sql_v_1', $link);
if (!$db_selected) {
    die ('Can\'t use foo : ' . mysql_error());
}

$sql = "SELECT * FROM unit_sql_table_1 WHERE field_id >= 1";

$result = mysql_query($sql);

if (!$result) {
    echo "Could not successfully run query ($sql) from DB: " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "No rows found, nothing to print so am exiting";
    exit;
}

echo '<pre>';
while ($row = mysql_fetch_assoc($result)) {
    print_r($row);
}
echo '</pre>';

mysql_free_result($result);
mysql_close($link);