<?php
/**
 * This is a simple unit test for our functions
 * to insure that they return the data as intended
 *
 * This test should be run with a MySQL functions removed
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
define('TEST_PASS', 'root');

define('TEST_DB', 'unit_sql_v_1');
define('TEST_TABLE', 'unit_sql_table_1');

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Definitions.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MySQL_Functions.php');

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

        // Get tests
        $tests = get_class_methods($this);
        
        // Set print mask
        $masker = "| %-30.30s | %7s |" . PHP_EOL;

        // Print header
        printf($masker, '------------------------------', '-------');
        printf($masker, 'Test', 'Result');
        printf($masker, '------------------------------', '-------');

        // Load db first
        $link = mysql_pconnect(TEST_HOST, TEST_USER, TEST_PASS);
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

        // Close all connections
        mysql_close_all();
    }

    /**
     * Test mysql_connect
     *
     * @return boolean
     */
    public function MySQL_Connect_Test()
    {
        // Simple connection test
        $ourDb = $this->_getConnection();

        return is_int($ourDb);
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

        for ($x = 0; $x <= 5; $x++) {
            // Connect
            $mysql = mysql_pconnect(TEST_HOST, TEST_USER, TEST_PASS);

            // Keep track of resource
            $this->_cached[] = $mysql;

            // Get thread ids
            $thisMySQLId = mysql_thread_id();

            // Get original ids if not set
            if ($lastMySQLId == false) {
                $lastMySQLId = $thisMySQLId;
            }

            // Keep checking that the ids are the same
            if ($thisMySQLId !== $lastMySQLId) {
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
        $query = mysql_query("SELECT DATABASE() as Databasename");
        $dbName = mysql_fetch_assoc($query);

        // Must be identical
        return ($dbName['Databasename'] === TEST_DB);
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
        $this->_getConnection();

        // Compose string
        $string = "mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', \" and \x1a. ";

        // Select Db
        $this->_selectDb();

        // Escape it
        $val1 = mysql_real_escape_string($string);
        $query = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val1')";

        // Add it & Get added ID (confirm add)
        mysql_query($query);
        $id1 = mysql_insert_id();

        return $id1 == 1;
    }
    
    /**
     * Test mysql_escape_string
     *
     * @return boolean
     */
    public function MySQL_Escape_String_Test()
    {
        // Connect
        $this->_getConnection();
        
        // Compose string
        $string = "mysql_real_escape_string() calls MySQL's library function mysql_real_escape_string, which prepends backslashes to the following characters: \x00, \n, \r, \, ', \" and \x1a. ";
        
        // Select Db
        $this->_selectDb();

        // Escape it
        $val1 = mysql_escape_string($string);
        $query = 'INSERT INTO ' . TEST_TABLE . " (field_name) VALUES ('$val1')";

        // Add it & Get added ID (confirm add)
        mysql_query($query);
        $id1 = mysql_insert_id();

        return $id1 == 2;
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
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 1';
        
        // Results
        $res1 = array();

        // For each fetch type
        foreach (array(MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH) as $fetchType) {

            // Query
            $query = mysql_query($sql);

            // Must match
            while ($r = mysql_fetch_array($query, $fetchType)) {
                $res1[] = $r;
            }
        }

        $count = count($res1);
        for ($x = 0; $x < $count; $x++) {
            $keys = array_keys($res1[$x]);
            $thisCount = count($res1[$x]);
            if ($x == 0) {
                if ($thisCount != 3 || !is_string($keys[0]) || !is_string($keys[1])) {
                    return false;
                }
            } else if ($x == 1) {
                if ($thisCount != 3 || !is_int($keys[0]) || !is_int($keys[1])) {
                    return false;
                }
            } else {
                if ($thisCount != 6 || !is_string($keys[0]) || !is_int($keys[1])) {
                    return false;
                }
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

        // Query
        $query = mysql_query($sql);

        // Must match
        while ($r = mysql_fetch_object($query)) {
            $res1[] = $r;
        }

        // Can't be identical because of stdclass object resource # difference
        return (is_object($res1[0]) && count((array) $res1[0]) == 3);
    }
    
    /**
     * Test mysql_ping
     *
     * @return boolean
     */
    public function MySQL_Ping_Test()
    {
        return (mysql_ping() === true);
    }
    
    /**
     * Test mysql_errno
     *
     * @return boolean
     */
    public function MySQL_Errno_Test()
    {
        $badSql = 'SELCT * FROM TABL.*';
        
        mysql_query($badSql);

        return mysql_errno() == 4;
    }
    
    /**
     * Test mysql_error
     *
     * @return boolean
     */
    public function MySQL_Error_Test()
    {
        $badSql = 'SELCT * FROM TABLE.*';
        
        mysql_query($badSql);
        return (stripos(mysql_error(), 'You have an error in your SQL syntax') !== false);
    }
    
    /**
     * Test mysql_affected_rows
     *
     * @return boolean
     */
    public function MySQL_Affected_Rows_Test()
    {
        // Connect
        $this->_getConnection();
        
        // Select Db
        $this->_selectDb();
        
        // Set different SQL
        $sql = 'UPDATE ' . TEST_TABLE . " SET field_name = 'test string' WHERE field_id <= 100";
        
        // Query
        mysql_query($sql);

        return mysql_affected_rows() === 2;
    }
    
    /**
     * Test mysql_client_encoding
     *
     * @return boolean
     */
    public function MySQL_Client_Encoding_Test()
    {
        // Connect
        $this->_getConnection();

        // Select Db
        $this->_selectDb();

        // Get encoding
        $code1 = mysql_client_encoding();

        return strlen($code1) > 4;
    }    

    /**
     * Test mysql_close
     *
     * @return boolean
     */
    public function MySQL_Close_Test()
    {
        // Connect
        $mysql = $this->_getConnection();

        // Select Db
        $this->_selectDb();

        return mysql_close($mysql) === true;
    }
    
    /**
     * Test mysql_create_db
     *
     * @return boolean
     */
    public function MySQL_Create_Db_Test()
    {
        // Drop db
        return mysql_create_db('unit_sql_v_2') === true;
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
        $this->_getConnection();
        
        // Select Db
        $this->_selectDb();
        
        // Get rows (we should have 2 by now)
        $sql = 'SELECT * FROM ' . TEST_TABLE . ' ORDER BY field_id ASC LIMIT 2';
        
        // Results
        $res1 = array();

        // Query
        $query = mysql_query($sql);
        
        mysql_data_seek($query, 1);

        return mysql_fetch_assoc($query) == true;
    }
    
    /**
     * Test mysql_list_dbs
     *
     * @return boolean
     */
    public function MySQL_List_Dbs_Test()
    {
        // Connect
        $this->_getConnection();
        
        $dbs1 = mysql_list_dbs();
        
        $list1 = array();

        while ($a = mysql_fetch_row($dbs1)) {
            $list1[] = $a[0];        
        }

        // Must have the two dbs we created
        return (in_array('unit_sql_v_1', $list1) && in_array('unit_sql_v_2', $list1));
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
        $this->_getConnection();
        
        $dbs1 = mysql_list_dbs();        

        $list1 = array();

        $i = 0;
        $cnt = mysql_num_rows($dbs1);
        while ($i < $cnt) {
            $list1[] = mysql_db_name($dbs1, $i);
            $i++;
        }

        return (in_array('unit_sql_v_1', $list1) && in_array('unit_sql_v_2', $list1));  
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
        
        // Get first rows
        $row1 = mysql_fetch_assoc($query1);

        // Match them
        return (count($row1) === 3);
    }

    /**
     * Test mysql_drop_db
     *
     * @return boolean
     */
    public function MySQL_Drop_Db_Test()
    {
        return mysql_drop_db('unit_sql_v_2') === true;
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

        // For each fetch type
        foreach (array(MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH) as $fetchType) {

            // Query
            $query = mysql_unbuffered_query($sql);

            // Must match
            while ($r = mysql_fetch_array($query, $fetchType)) {
                $res1[] = $r;
            }
        }
        
        $count = count($res1);
        for ($x = 0; $x < $count; $x++) {
            $keys = array_keys($res1[$x]);
            $thisCount = count($res1[$x]);
            if ($x == 0) {
                if ($thisCount != 3 || !is_string($keys[0]) || !is_string($keys[1])) {
                    return false;
                }
            } else if ($x == 1) {
                if ($thisCount != 3 || !is_int($keys[0]) || !is_int($keys[1])) {
                    return false;
                }
            } else {
                if ($thisCount != 6 || !is_string($keys[0]) || !is_int($keys[1])) {
                    return false;
                }
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
        $this->_getConnection();

        // Results
        $res1 = array();

        // Query
        $query = mysql_list_tables(TEST_DB);

        // Must match
        while ($r = mysql_fetch_assoc($query)) {
            $res1[] = $r;
        }

        return in_array('unit_sql_table_1', $res1[0]);
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
        $this->_getConnection();
        
        $dbs1 = mysql_list_tables(TEST_DB);
        
        $list1 = array();

        $i = 0;
        $cnt = mysql_num_rows($dbs1);
        while ($i < $cnt) {
            $list1[] = mysql_tablename($dbs1, $i);
            $i++;
        }

        return $list1[0] === 'unit_sql_table_1';
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

        $finds = array('Uptime:', 'Threads:', 'Questions:', 'Open tables:', 'Queries per second');

        foreach ($finds as $find) {
            if (stripos($stat1, $find) === false) {
                return false;
            }
        }
        // Compare
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

        // Get charset
        $enc1 = mysql_client_encoding();

        return $enc1 === "latin1";
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

        $string1 = mysql_result($query, 1);

        return is_numeric($string1);
    }
    
    /**
     * Test mysql_list_processes
     *
     * @return boolean
     */
    public function MySQL_List_Processes_Test()
    {
        $dbs1 = mysql_list_processes();
        
        while ($row = mysql_fetch_assoc($dbs1)) {
            if (!is_numeric($row['Id'])) {
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

        return preg_match('/[0-9\.]+(\-.*)?/', $str1);
    }
    
    /**
     * Test mysql_get_proto_info
     *
     * @return boolean
     */
    public function MySQL_Get_Proto_Info_Test()
    {
        $str1 = mysql_get_proto_info();
        return is_int($str1);
    }
    
    /**
     * Test mysql_get_host_info
     *
     * @return boolean
     */
    public function MySQL_Get_Host_Info_Test()
    {
        $str1 = mysql_get_host_info();
        return stripos($str1, 'via');
    }
    
    /**
     * Test mysql_get_client_info
     *
     * @return boolean
     */
    public function MySQL_Get_Client_Info_Test()
    {
        $str1 = mysql_get_client_info();
        return is_string($str1);
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
        
        $count1 = 0;
        // Validate query to avoid throwing errors
        while (is_resource_custom($query1) && get_resource_type_custom($query1) == 'mysql result' && $row = mysql_fetch_assoc($query1)) {
            $count1++;            
            mysql_free_result($query1);
        }

        return $count1 === 1;
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

        while ($row = mysql_fetch_row($query1)) {
            $row2 = mysql_fetch_lengths($query1);
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

        $list1 = array();
        $cnt = count($query1);

        $i = 0;
        while ($i < $cnt) {
            $list1[] = mysql_field_name($query1, $i);
            $i++;
        }

        return count($list1) === 3;
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

        // Get items
        $length1 = mysql_field_len($query1, 0);

        return $length1 === 20;
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

        // Get items
        $flags1 = mysql_field_flags($query1, 0);
        $finds = array('not_null', 'primary_key', 'unsigned', 'zerofill', 'auto_increment');

        foreach ($finds as $find) {
            if (stripos($flags1, $find) === false) {
                return false;
            }
        }

        return true;
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

        // Get items
        $name1 = mysql_field_name($query1, 0);
        return $name1 === 'field_id';
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

        // Get items
        $type1 = mysql_field_type($query1, 0);
        return $type1 === 'int';
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

        // Get items
        $table1 = mysql_field_table($query1, 0);
        return $table1 === 'unit_sql_table_1';
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

        // Seek
        mysql_field_seek($query1, 1);

        // Get items
        $info1 = mysql_fetch_field($query1);
        return $info1->name == 'field_name';
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

        // Get items
        $info1 = mysql_fetch_field($query1, 1);

        return $info1 == true;
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

        // Get items
        $num1 = mysql_num_fields($query1);

        return $num1 == true;
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

        mysql_query($sql1);

        $info1 = mysql_info();

        $finds = array('Rows matched:', 'Changed:', 'Warnings:');

        foreach ($finds as $find) {
            if (stripos($info1, $find) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get connection to DB
     *
     * @return our resource (#)
     */
    protected function _getConnection()
    {
        $mysql = mysql_connect(TEST_HOST, TEST_USER, TEST_PASS, true);

        // Keep track of resource
        $this->_cached[] = $mysql;

        return $mysql;
    }
    
    /**
     * Select Db
     *
     * @param boolean $mysql
     * 
     * @return void
     */
    protected function _selectDb($mysql = false)
    {
        if ($mysql === false) {
            mysql_select_db(TEST_DB);
        } else {
            mysql_select_db(TEST_DB, $mysql);
        }
    }
}

// Start tests
new MySQL_Test;