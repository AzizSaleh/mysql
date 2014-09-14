<?php
/**
 * This is a simple unit test for all the MySQL_*
 * functions so that they match our functions
 *
 * This test should be run with a MySQL functions enabled
 * 
 * @author    Aziz S. Hussain <azizsaleh@gmail.com>
 * @copyright GPL license 
 * @license   http://www.gnu.org/copyleft/gpl.html 
 * @link      http://www.AzizSaleh.com
 */

/*
 * Test Db Params - user needs db/table create, drop,
 * insert, delete, update permissions)
 */
define('TEST_HOST', 'localhost');
define('TEST_USER', 'root');
define('TEST_PASS', '');

define('TEST_DB', 'unit_sql_v_1');
define('TEST_TABLE', 'unit_sql_table_1');

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Definitions.php');

/**
 * MySQL_Test
 *
 * Test object
 * 
 * @author    Aziz S. Hussain <azizsaleh@gmail.com>
 * @copyright GPL license 
 * @license   http://www.gnu.org/copyleft/gpl.html 
 * @link      http://www.AzizSaleh.com
 */
class MySQL_Test
{
    /**
     * Test Results
     *
     * @var array
     */
    public $results = array(
        'valid'     => 0,
        'invalid'   => 0,
        'naf'       => 0,
        'tests'     => 0,
    );

    /**
     * MySQL Object
     *
     * @var MySQL
     */
    protected $_object;
    
    /**
     * Connection holder
     *
     * @var array
     */
    protected $_cached = array();

    /**
     * Start testing
     *
     * @return void
     */
    public function __construct()
    {
        // Set text on browser
        if (php_sapi_name() != 'cli') {
            header('Content-type: text/plain');
        }

        // Set object
        $this->_object = MySQL::getInstance();

        // Get tests
        $tests = get_class_methods($this);
        
        // Set print mask
        $masker = "| %-30.30s | %7s |" . PHP_EOL;

        // Print header
        printf($masker, '------------------------------', '-------');
        printf($masker, 'Test', 'Result');
        printf($masker, '------------------------------', '-------');

        // Load db first
        $link = mysql_connect(TEST_HOST, TEST_USER, TEST_PASS);
        $statements = explode(';', 
            file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Test_Schema.sql')
        );

        foreach ($statements as $statement) {
            mysql_query(trim($statement)) or die(mysql_error());
        }
        mysql_close($link);

        // Go through each test
        foreach ($tests as $test) {
        
            // Skip private/protected methods
            if (substr($test, 0, 1) == '_') {
                continue;
            }
            
            // Get mysql_* method
            $name = strtolower(str_replace('_Test', '', $test));

            // Increment # of tests
            $this->results['tests']++;
            
            // If it doesn't exist, naf it (not a function)
            if (!function_exists($name)) {
                $this->results['naf']++;
                printf($masker, $test, 'NAF');
                continue;
            }

            // Run tests
            if ($this->$test()) {
                $this->results['valid']++;
                printf($masker, $test, 'Success');
            } else {
                $this->results['invalid']++;
                printf($masker, $test, 'Failure');
            }
        }
        
        // Print footer
        printf($masker, '------------------------------', '-------');
    }

    /**
     * After finishing, print out the results
     *
     * @return void
     */
    public function __destruct()
    {
        // Echo results
        echo PHP_EOL . "Completed \t{$this->results['tests']} Tests" . PHP_EOL;
        echo "Passed \t\t{$this->results['valid']} Tests" . PHP_EOL;
        echo "Failed \t\t{$this->results['invalid']} Tests" . PHP_EOL;
        echo "NAF \t\t{$this->results['naf']} Tests" . PHP_EOL;
        
        // Close our connections
        $this->_object->mysql_close_all();
        
        // Close mysql connections
        foreach ($this->_cached as $resource) {
            // Not a resource
            if (!is_resource($resource)) {
                continue;
            }

            // Check resource type
            $type = get_resource_type($resource);
            if (substr($type, 0, 10) != 'mysql link') {
                continue;
            }

            // Close it
            mysql_close($resource);
        }
    }

    /**
     * Test mysql_connect
     *
     * @return boolean
     */
    public function MySQL_Connect_Test()
    {
        // Simple connection test
        list($mysql, $ourDb) = $this->_getConnection();

        return ($ourDb !== false && is_resource($mysql));
    }
    
    /**
     * Test pconnect
     *
     * @return boolean
     */
    public function MySQL_Pconnect_Test()
    {
        // We need to make sure that the connection ids are the same
        $lastMySQLId = false;
        $lastOurId = false;

        for ($x = 0; $x <= 5; $x++) {
            // Connect
            $mysql = mysql_pconnect(TEST_HOST, TEST_USER, TEST_PASS);
            $ourDb = $this->_object->mysql_pconnect(TEST_HOST, TEST_USER, TEST_PASS);

            // Keep track of resource
            $this->_cached[] = $mysql;

            // Get thread ids
            $thisMySQLId = mysql_thread_id();
            $thisOurId = $this->_object->mysql_thread_id();

            // Get original ids if not set
            if ($lastMySQLId == false) {
                $lastMySQLId = $thisMySQLId;
                $lastOurId = $thisOurId;
            }

            // Keep checking that the ids are the same
            if ($thisMySQLId !== $lastMySQLId || $thisOurId !== $lastOurId) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Test mysql_select_db
     *
     * @return boolean
     */
    public function MySQL_Select_Db_Test()
    {
        // Connect
        $this->_getConnection();

        // Select Db
        $this->_selectDb();

        // Get db name
        $dbName = mysql_fetch_assoc(mysql_query("SELECT DATABASE() as Databasename"));
        $dbNameOur = $this->_object->mysql_fetch_assoc($this->_object->mysql_query("SELECT DATABASE() as Databasename"));

        // Must be identical
        return ($dbName['Databasename'] === $dbNameOur['Databasename'] && $dbName['Databasename'] === TEST_DB);
    }
    
    /**
     * Test mysql_selectdb
     *
     * @return boolean
     */
    public function MySQL_SelectDb_Test()
    {
        // Alias of Mysql_Select_Db_Test
        return true;
    }
    
    /**
     * Test mysql_query
     *
     * @return boolean
     */
    public function MySQL_Query_Test()
    {
        // Done in Mysql_Select_Db_Test
        return true;
    }
    
    /**
     * Test mysql_real_escape_string
     *
     * @return boolean
     */
    public function MySQL_Real_Escape_String_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();

        // Compose string
        $string = "mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', \" and \x1a. ";

        // Select Db
        $this->_selectDb();

        // Escape it
        $val1 = mysql_real_escape_string($string);
        $val2 = $this->_object->mysql_real_escape_string($string);
        $query = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val2')";
        $query2 = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val2')";

        // Add it
        mysql_query($query);
        $this->_object->mysql_query($query2);

        // Get added ID (confirm add)
        $id1 = mysql_insert_id();
        $id2 = $this->_object->mysql_insert_id();

        return $id1 == 1 && $id2 == 2;
    }
    
    /**
     * Test mysql_escape_string
     *
     * @return boolean
     */
    public function MySQL_Escape_String_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        // Compose string
        $string = "mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', \" and \x1a. ";
        
        // Select Db
        $this->_selectDb();

        // Escape it
        $val1 = mysql_escape_string($string);
        $val2 = $this->_object->mysql_escape_string($string);
        $query = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val2')";
        $query2 = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val2')";

        // Add it
        mysql_query($query);
        $this->_object->mysql_query($query2);

        // Get added ID (confirm add)
        $id1 = mysql_insert_id();
        $id2 = $this->_object->mysql_insert_id();

        return $id1 == 3 && $id2 == 4;
    }
    
    /**
     * Test mysql_fetch_array
     *
     * @return boolean
     */
    public function MySQL_Fetch_Array_Test()
    {
        // Select Db
        $this->_selectDb();

        // Get rows (we should have 2 by now)
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 2';
        
        // Results
        $res1 = array();
        $res2 = array();

        // For each fetch type
        foreach (array(MYSQL_ASSOC) as $fetchType) {

            // Query
            $query = mysql_query($sql);
            $query2 = $this->_object->mysql_query($sql);

            // Must match
            while ($r = mysql_fetch_array($query, $fetchType)) {
                $res1[] = $r;
            }
            while($r2 = $this->_object->mysql_fetch_array($query2, $fetchType)) {
                $res2[] = $r2;
            }
        }

        $count = count($res1);
        for ($x = 0; $x < $count; $x++) {
            // Standardize order of keys, data must match
            $row1 = $res1[$x];
            $row2 = $res2[$x];
            sort($row1, SORT_STRING);
            sort($row2, SORT_STRING);
            if ($row1 !== $row2) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test mysql_fetch_assoc
     *
     * @return boolean
     */
    public function MySQL_Fetch_Assoc_Test()
    {
        // Done in Mysql_Select_Db_Test
        return true;
    }

    /**
     * Test mysql_fetch_row
     *
     * @return boolean
     */
    public function MySQL_Fetch_Row_Test()
    {
        // Done in Mysql_Fetch_Array_Test
        return true;
    }
    
    /**
     * Test mysql_fetch_object
     *
     * @return boolean
     */
    public function MySQL_Fetch_Object_Test()
    {
        // Select Db
        $this->_selectDb();

        // Get rows (we should have 2 by now)
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 1';
        
        // Results
        $res1 = array();
        $res2 = array();

        // Query
        $query = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Must match
        while ($r = mysql_fetch_object($query)) {
            $res1[] = $r;
        }
        while($r2 = $this->_object->mysql_fetch_object($query2)) {
            $res2[] = $r2;
        }

        // Can't be identical because of stdclass object resource # difference
        return $res1 == $res2;
    }
    
    /**
     * Test mysql_ping
     *
     * @return boolean
     */
    public function MySQL_Ping_Test()
    {
        return (mysql_ping() === true && $this->_object->mysql_ping() === true);
    }
    
    /**
     * Test mysql_errno
     *
     * @return boolean
     */
    public function MySQL_Errno_Test()
    {
        $badSql = 'SELECT * FROM TABLE.*';
        
        mysql_query($badSql);
        $this->_object->mysql_query($badSql);

        return mysql_errno() === $this->_object->mysql_errno();
    }
    
    /**
     * Test mysql_error
     *
     * @return boolean
     */
    public function MySQL_Error_Test()
    {
        $badSql = 'SELECT * FROM TABLE.*';
        
        mysql_query($badSql);
        $this->_object->mysql_query($badSql);
        
        return mysql_error() === $this->_object->mysql_error();
    }
    
    /**
     * Test mysql_affected_rows
     *
     * @return boolean
     */
    public function MySQL_Affected_Rows_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        // Select Db
        $this->_selectDb();
        
        // Set different SQL
        $sql = 'UPDATE ' . TEST_TABLE . " SET field_name = 'test string' WHERE field_id <= 100";
        $sql2 = 'UPDATE ' . TEST_TABLE . " SET field_name = 'test string 2' WHERE field_id <= 100";
        
        // Query
        mysql_query($sql);
        $this->_object->mysql_query($sql2);
        return mysql_affected_rows() === $this->_object->mysql_affected_rows();
    }
    
    /**
     * Test mysql_client_encoding
     *
     * @return boolean
     */
    public function MySQL_Client_Encoding_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();

        // Select Db
        $this->_selectDb();

        // Get encoding
        $code1 = mysql_client_encoding();
        $code2 = $this->_object->mysql_client_encoding();
        
        return $code1 === $code2;
    }    

    /**
     * Test mysql_close
     *
     * @return boolean
     */
    public function MySQL_Close_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();

        // Select Db
        $this->_selectDb();

        return mysql_close($mysql) === $this->_object->mysql_close($ourDb);
    }
    
    /**
     * Test mysql_create_db
     *
     * @return boolean
     */
    public function MySQL_Create_Db_Test()
    {
        // Connect
        $newDb = 'unit_sql_v_2';

        return mysql_create_db($newDb) === $this->_object->mysql_create_db($newDb);
    }
    
    /**
     * Test mysql_createdb
     *
     * @return boolean
     */
    public function MySQL_CreateDb_Test()
    {
        // Alias of Mysql_Create_Db_Test
        return true;
    }
    
    /**
     * Test mysql_data_seek
     *
     * @return boolean
     */
    public function MySQL_Data_Seek_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        // Select Db
        $this->_selectDb();
        
        // Get rows (we should have 2 by now)
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 2';
        
        // Results
        $res1 = array();
        $res2 = array();

        // Query
        $query = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);
        
        mysql_data_seek($query, 1);
        $this->_object->mysql_data_seek($query2, 1);

        return mysql_fetch_assoc($query) == $this->_object->mysql_fetch_assoc($query2);
    }
    
    /**
     * Test mysql_list_dbs
     *
     * @return boolean
     */
    public function MySQL_List_Dbs_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        $dbs1 = mysql_list_dbs();
        $dbs2 = $this->_object->mysql_list_dbs();
        
        $list1 = array();
        $list2 = array();

        while ($a = mysql_fetch_row($dbs1)) {
            $list1[] = $a[0];        
        }
        while ($a = $this->_object->mysql_fetch_row($dbs2)) {
            $list2[] = $a[0];        
        }

        return $list1 === $list2;
    }
    
    /**
     * Test mysql_listdbs
     *
     * @return boolean
     */
    public function MySQL_ListDbs_Test()
    {
        // Alias of Mysql_List_Dbs_Test
        return true;
    }
    
    /**
     * Test mysql_db_name
     *
     * @return boolean
     */
    public function MySQL_Db_Name_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        $dbs1 = mysql_list_dbs();
        $dbs2 = $this->_object->mysql_list_dbs();
        
        $list1 = array();
        $list2 = array();

        $i = 0;
        $cnt = mysql_num_rows($dbs1);
        while ($i < $cnt) {
            $list1[] = mysql_db_name($dbs1, $i);
            $i++;
        }
        
        $i = 0;
        $cnt = $this->_object->mysql_num_rows($dbs2);

        while ($i < $cnt) {
            $list2[] = $this->_object->mysql_db_name($dbs2, $i);
            $i++;
        }

        return $list1 === $list2;
    }
    
    /**
     * Test mysql_dbname
     *
     * @return boolean
     */
    public function MySQL_Dbname_Test()
    {
        // Alias of Mysql_Db_Name_Test
        return true;
    }
    
    /**
     * Test mysql_db_query
     *
     * @return boolean
     */
    public function MySQL_Db_Query_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();

        // Get row
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 1';
        
        // Select db + query
        $query1 = mysql_db_query(TEST_DB, $sql);
        $query2 = $this->_object->mysql_db_query(TEST_DB, $sql);
        
        // Get first rows
        $row1 = mysql_fetch_assoc($query1);
        $row2 = $this->_object->mysql_fetch_assoc($query2);

        // Match them
        return $row1 === $row2;
    }

    /**
     * Test mysql_drop_db
     *
     * @return boolean
     */
    public function MySQL_Drop_Db_Test()
    {
        // Connect
        $newDb = 'unit_sql_v_2';

        return mysql_drop_db($newDb) === $this->_object->mysql_drop_db($newDb);
    }
    
    /**
     * Test mysql_dropdb
     *
     * @return boolean
     */
    public function MySQL_DropDb_Test()
    {
        // Alias of Mysql_Drop_Db_Test
        return true;
    }
    
    /**
     * Test mysql_unbuffered_query
     *
     * @return boolean
     */
    public function MySQL_Unbuffered_Query_Test()
    {
        // Select Db
        $this->_selectDb();

        // Get rows (we should have 2 by now)
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 1';
        
        // Results
        $res1 = array();
        $res2 = array();

        // For each fetch type
        foreach (array(MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH) as $fetchType) {

            // Query
            $query = mysql_unbuffered_query($sql);
            $query2 = $this->_object->mysql_unbuffered_query($sql);

            // Must match
            while ($r = mysql_fetch_array($query, $fetchType)) {
                $res1[] = $r;
            }
            while($r2 = $this->_object->mysql_fetch_array($query2, $fetchType)) {
                $res2[] = $r2;
            }
        }
        
        $count = count($res1);
        for ($x = 0; $x < $count; $x++) {
            // Standardize order of keys, data must match
            $row1 = $res1[$x];
            $row2 = $res2[$x];
            sort($row1, SORT_STRING);
            sort($row2, SORT_STRING);
            if ($row1 !== $row2) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test mysql_thread_id
     *
     * @return boolean
     */
    public function MySQL_Thread_Id_Test()
    {
        // Done in Mysql_Pconnect_Test
        return true;
    }
    
    /**
     * Test mysql_list_tables
     *
     * @return boolean
     */
    public function MySQL_List_Tables_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();

        // Results
        $res1 = array();
        $res2 = array();

        // Query
        $query = mysql_list_tables(TEST_DB);
        $query2 = $this->_object->mysql_list_tables(TEST_DB);

        // Must match
        while ($r = mysql_fetch_assoc($query)) {
            $res1[] = $r;
        }
        while($r2 = $this->_object->mysql_fetch_assoc($query2)) {
            $res2[] = $r2;
        }

        return $res1 === $res2;
    }
    
    /**
     * Test mysql_listtables
     *
     * @return boolean
     */
    public function MySQL_ListTables_Test()
    {
        // Alias of Mysql_List_Tables_Test
        return true;
    }
    
    /**
     * Test mysql_tablename
     *
     * @return boolean
     */
    public function MySQL_Tablename_Test()
    {
        // Connect
        list($mysql, $ourDb) = $this->_getConnection();
        
        $dbs1 = mysql_list_tables(TEST_DB);
        $dbs2 = $this->_object->mysql_list_tables(TEST_DB);
        
        $list1 = array();
        $list2 = array();

        $i = 0;
        $cnt = mysql_num_rows($dbs1);
        while ($i < $cnt) {
            $list1[] = mysql_tablename($dbs1, $i);
            $i++;
        }
        
        $i = 0;
        $cnt = $this->_object->mysql_num_rows($dbs2);

        while ($i < $cnt) {
            $list2[] = $this->_object->mysql_tablename($dbs2, $i);
            $i++;
        }

        return $list1 === $list2;
    }
    
    /**
     * Test mysql_stat
     *
     * @return boolean
     */
    public function MySQL_Stat_Test()
    {
        // Get stats
        $stat1 = mysql_stat();
        $stat2 = $this->_object->mysql_stat();        

        // Extract all #'s out
        preg_match_all('!\d+!', $stat1, $matches1);
        preg_match_all('!\d+!', $stat2, $matches2);        
        unset($stat1, $stat2);

        // Go through each number
        $count = count($matches1[0]);
        for ($x = 0; $x < $count; $x++) {
            $diff = abs($matches1[0][$x] - $matches2[0][$x]);
            
            // Make sure that the difference is <= 10 (margin of change while queries are running)
            if ($diff > 10) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Test mysql_set_charset
     *
     * @return boolean
     */
    public function MySQL_Set_Charset_Test()
    {
        // Set charset
        $charset = 'latin1';
        mysql_set_charset($charset);
        $this->_object->mysql_set_charset($charset);

        // Get charset
        $enc1 = mysql_client_encoding();
        $enc2 = $this->_object->mysql_client_encoding();

        return $enc1 === $enc2;
    }
    
    /**
     * Test mysql_result
     *
     * @return boolean
     */
    public function MySQL_Result_Test()
    {
        // Select Db
        $this->_selectDb();

        // Get rows
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 2';

        // Query
        $query = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        $string1 = mysql_result($query, 1);
        $string2 = $this->_object->mysql_result($query2, 1);

        return $string1 === $string2;
    }
    
    /**
     * Test mysql_list_processes
     *
     * @return boolean
     */
    public function MySQL_List_Processes_Test()
    {
        $dbs1 = mysql_list_processes();
        $dbs2 = $this->_object->mysql_list_processes();
        
        $list1 = array();
        $list2 = array();

        while ($row = mysql_fetch_assoc($dbs1)) {
            $list1[] = $row;
        }
        
        while ($row = $this->_object->mysql_fetch_assoc($dbs2)) {
            $list2[] = $row;
        }
            
        $count = count($list1);
        for ($x = 0; $x < $count; $x++) {
            if ($list1[$x]['Id'] != $list1[$x]['Id']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test mysql_insert_id
     *
     * @return boolean
     */
    public function MySQL_Insert_Id_Test()
    {
        // Done in Mysql_Real_Escape_String_Test
        return true;
    }
    
    /**
     * Test mysql_get_server_info
     *
     * @return boolean
     */
    public function MySQL_Get_Server_Info_Test()
    {
        $str1 = mysql_get_server_info();
        $str2 = $this->_object->mysql_get_server_info();
        return $str1 === $str2;
    }
    
    /**
     * Test mysql_get_proto_info
     *
     * @return boolean
     */
    public function MySQL_Get_Proto_Info_Test()
    {
        $str1 = mysql_get_proto_info();
        $str2 = $this->_object->mysql_get_proto_info();
        return $str1 === $str2;
    }
    
    /**
     * Test mysql_get_host_info
     *
     * @return boolean
     */
    public function MySQL_Get_Host_Info_Test()
    {
        $str1 = mysql_get_host_info();
        $str2 = $this->_object->mysql_get_host_info();
        return $str1 === $str2;
    }
    
    /**
     * Test mysql_get_client_info
     *
     * @return boolean
     */
    public function MySQL_Get_Client_Info_Test()
    {
        $str1 = mysql_get_client_info();
        $str2 = $this->_object->mysql_get_client_info();
        return $str1 === $str2;
    }
    
    /**
     * Test mysql_free_result
     *
     * @return boolean
     */
    public function MySQL_Free_Result_Test()
    {
        $sql = 'SELECT * FROM ' . TEST_TABLE;
        
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);
        
        $count1 = 0;
        $count2 = 0;
        
        // Validate query to avoid throwing errors
        while (get_resource_type($query1) == 'mysql result' && $row = @mysql_fetch_assoc($query1)) {
            $count1++;
            mysql_free_result($query1);
        }

        while ($query2 && $row = $this->_object->mysql_fetch_assoc($query2)) {
            $count2++;
            $this->_object->mysql_free_result($query2);
        }

        return $count1 === $count2;
    }
    
    /**
     * Test mysql_freeresult
     *
     * @return boolean
     */
    public function MySQL_FreeResult_Test()
    {
        // Alias of Mysql_Free_Result_Test
        return true;
    }
    
    /**
     * Test mysql_fetch_lengths
     *
     * @return boolean
     */
    public function MySQL_Fetch_Lengths_Test()
    {
        $sql = 'SELECT * FROM ' . TEST_TABLE;
        
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        while ($row = mysql_fetch_row($query1)) {
            $row2 = mysql_fetch_lengths($query1);
            foreach ($row as $sub => $string) {
                if (strlen($string) != $row2[$sub]) {
                    return false;
                }
            }
        }

        while ($row = $this->_object->mysql_fetch_row($query2)) {
            $row2 = $this->_object->mysql_fetch_lengths($query1);
            
            foreach ($row as $sub => $string) {
                if (strlen($string) != $row2[$sub]) {
                    return false;
                }
            }
        }

        return true;
    }
    
    /**
     * Test mysql_list_fields
     *
     * @return boolean
     */
    public function MySQL_List_Fields_Test()
    {
        // Select Db
        $this->_selectDb();

        $query1 = mysql_list_fields(TEST_DB, TEST_TABLE);
        $query2 = $this->_object->mysql_list_fields(TEST_DB, TEST_TABLE);

        $list1 = array();
        $list2 = array();

        // Our object gives us num_rows, something that we can't do in mysql_*
        $cnt = $this->_object->mysql_num_rows($query2);

        $i = 0;
        while ($i < $cnt) {
            $list1[] = mysql_field_name($query1, $i);
            $i++;
        }

        $i = 0;
        while ($i < $cnt) {
            $list2[] = $this->_object->mysql_field_name($query2, $i);
            $i++;
        }

        return $list1 === $list2;
    }
    
    /**
     * Test mysql_listfields
     *
     * @return boolean
     */
    public function MySQL_ListFields_Test()
    {
        // Alias of Mysql_List_Fields_Test
        return true;
    }
    
    /**
     * Test mysql_field_len
     *
     * @return boolean
     */
    public function MySQL_Field_Len_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $length1 = mysql_field_len($query1, 0);
        $length2 = $this->_object->mysql_field_len($query2, 0);
        return $length1 === $length2;
    }
    
    /**
     * Test mysql_fieldlen
     *
     * @return boolean
     */
    public function MySQL_FieldLen_Test()
    {
        // Alias of Mysql_Field_Len_Test
        return true;
    }
    
    /**
     * Test mysql_field_flags
     *
     * @return boolean
     */
    public function MySQL_Field_Flags_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $flags1 = mysql_field_flags($query1, 0);
        $flags2 = $this->_object->mysql_field_flags($query2, 0);
        return $flags1 === $flags2;
    }
    
    /**
     * Test mysql_fieldflags
     *
     * @return boolean
     */
    public function MySQL_FieldFlags_Test()
    {
        // Alias of Mysql_Field_Flags_Test
        return true;
    }
    
    /**
     * Test mysql_field_name
     *
     * @return boolean
     */
    public function MySQL_Field_Name_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $name1 = mysql_field_name($query1, 0);
        $name2 = $this->_object->mysql_field_name($query2, 0);

        return $name1 === $name2;
    }
    
    /**
     * Test mysql_fieldname
     *
     * @return boolean
     */
    public function MySQL_FieldName_Test()
    {
        // Alias of Mysql_Field_Name_Test
        return true;
    }
    
    /**
     * Test mysql_field_type
     *
     * @return boolean
     */
    public function MySQL_Field_Type_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $type1 = mysql_field_type($query1, 0);
        $type2 = $this->_object->mysql_field_type($query2, 0);

        return $type1 === $type2;
    }
    
    /**
     * Test mysql_fieldtype
     *
     * @return boolean
     */
    public function MySQL_FieldType_Test()
    {
        // Alias of Mysql_Field_Type_Test
        return true;
    }
    
    /**
     * Test mysql_field_table
     *
     * @return boolean
     */
    public function MySQL_Field_Table_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $table1 = mysql_field_table($query1, 0);
        $table2 = $this->_object->mysql_field_table($query2, 0);

        return $table1 === $table2;
    }
    
    /**
     * Test mysql_fieldtable
     *
     * @return boolean
     */
    public function MySQL_FieldTable_Test()
    {
        // Alias of Mysql_Field_Table_Test
        return true;
    }
    
    /**
     * Test mysql_field_seek
     *
     * @return boolean
     */
    public function MySQL_Field_Seek_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Seek
        mysql_field_seek($query1, 1);
        $this->_object->mysql_field_seek($query1, 1);

        // Get items
        $info1 = mysql_fetch_field($query1);
        $info2 = $this->_object->mysql_fetch_field($query2);

        return $info1 == $info2;
    }
    
    /**
     * Test mysql_fetch_field
     *
     * @return boolean
     */
    public function MySQL_Fetch_Field_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $info1 = mysql_fetch_field($query1, 1);
        $info2 = $this->_object->mysql_fetch_field($query2, 1);

        return $info1 == $info2;
    }
    
    /**
     * Test mysql_num_fields
     *
     * @return boolean
     */
    public function MySQL_Num_Fields_Test()
    {
        // Select Db
        $this->_selectDb();

        // Query
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' LIMIT 1';
        
        // Query
        $query1 = mysql_query($sql);
        $query2 = $this->_object->mysql_query($sql);

        // Get items
        $num1 = mysql_num_fields($query1);
        $num2 = $this->_object->mysql_num_fields($query2);

        return $num1 == $num2;
    }
    
    /**
     * Test mysql_numfields
     *
     * @return boolean
     */
    public function MySQL_Numfields_Test()
    {
        // Alias of Mysql_Num_Fields_Test
        return true;
    }
    
    /**
     * Test mysql_num_rows
     *
     * @return boolean
     */
    public function MySQL_Num_Rows_Test()
    {
        // Done in Mysql_Db_Name_Test
        return true;
    }
    
    /**
     * Test mysql_numrows
     *
     * @return boolean
     */
    public function MySQL_NumRows_Test()
    {
        // Alias of Mysql_Num_Rows_Test
        return true;
    }
    
    /**
     * Test mysql_info
     *
     * @return boolean
     */
    public function MySQL_Info_Test()
    {
        // Select Db
        $this->_selectDb();
        
        $sql1 = 'UPDATE ' . TEST_TABLE . " SET field_name = 'test 2' WHERE field_id <= 1";
        $sql2 = 'UPDATE ' . TEST_TABLE . " SET field_name = 'test 2' WHERE field_id <= 2 AND field_id > 1";

        mysql_query($sql1);
        $this->_object->mysql_query($sql2);

        $info1 = mysql_info();
        $info2 = $this->_object->mysql_info();

        return $info1 == $info2;
    }

    /**
     * Get connection to DB
     *
     * @return array 0 -> mysql resource, 1 -> our resource (#)
     */
    protected function _getConnection()
    {
        $mysql = mysql_connect(TEST_HOST, TEST_USER, TEST_PASS, true);
        $ourDb = $this->_object->mysql_connect(TEST_HOST, TEST_USER, TEST_PASS, true);

        // Keep track of resource
        $this->_cached[] = $mysql;

        return array($mysql, $ourDb);
    }
    
    /**
     * Select Db
     *
     * @param boolean $mysql
     * @param boolean $ourDb
     * 
     * @return void
     */
    protected function _selectDb($mysql = false, $ourDb = false)
    {
        if ($mysql === false) {
            mysql_select_db(TEST_DB);
        } else {
            mysql_select_db(TEST_DB, $mysql);
        }

        if ($ourDb === false) {
            $this->_object->mysql_select_db(TEST_DB);
        } else {
            $this->_object->mysql_select_db(TEST_DB, $ourDb);
        }
    }
}

// Start tests
new MySQL_Test;