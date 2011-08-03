<?php

/**
 * Some settings for the sql class:
 */
define('HALT_ON_SQL_ERROR', false);
define('SILENT_AFTER_FAILED_CONNECT', true);
define('SQL_ERROR_HANDLER', 'setError');

$return_silent = false;

/**
 * Class SQL: a simple DB class.
 *
 * For more information and instructions, see: http://www.twokings.eu/tools/
 *
 * This file is part of A Simple SQL Class. A Simple SQL Class and all its
 * parts are licensed under the GPL version 2. see:
 * http://www.twokings.eu/tools/license for more information.

 * @version 1.0
 * @author Bob den Otter, bob@twokings.nl
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 */
class sql {

    // mysql database credentials
    var $dbhost;
    var $dbuser;
    var $dbpass;
    var $dbase;

    var $sql_query;
    var $sql_link;
    var $sql_result;
    var $last_query;
    var $last_num_results;
    var $num_affected_rows;
    var $halt_on_sql_error;
    var $silent_after_failed_connect;
    var $return_silent;
    var $error_handler;

    function sql($type="", $dbase="", $host="", $user="", $pass="") {
        global $cfg;

        $this->type = $type;
        $this->dbhost = $host;
        $this->dbuser = $user;
        $this->dbpass = $pass;
        $this->dbase =$dbase;

        $this->sql_link = 0;
        $this->sql_result = '';
        $this->last_query = '';
        $this->last_num_results = 0;
        $this->num_affected_rows = 0;
        $this->halt_on_sql_error = HALT_ON_SQL_ERROR;
        $this->silent_after_failed_connect = SILENT_AFTER_FAILED_CONNECT;
        $this->error_handler = SQL_ERROR_HANDLER;
        
        $this->allow_functions = false;
    }



    /**
     * Set up the Database connection, depending on the selected DB model.
     */
    function connection() {
        global $return_silent;

        /**
         * If we had a connection error before, perhaps we should return
         * quietly, to prevent the user's screen from overflowing with SQL
         * errors. We use the global $return_silent, so it works if you have
         * multiple instances of the sql object.
         */
        if ($return_silent == true) {
            return false;
        }



        switch($this->type) {

            case "mysql":

                 /**
                 * Set up a link for MySQL model
                 */

                // Set up the link, if not already done so.
                if ($this->sql_link == 0) {

                    // See if we can connect to the Mysql Database Engine.
                    if ($this->sql_link = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpass,true)) {
                       // Yes, so now see if we can select the database.

                       if (!mysql_select_db($this->dbase, $this->sql_link)) {

                          // We couldn't connect to the database. Print an error.
                          $this->error( "Can't select Database '<tt>". $this->dbase ."</tt>'" , '', mysql_errno($this->sql_link) );

                          // If silent_after_failed_connect is set, from now on return without errors/warnings
                          if($this->silent_after_failed_connect) {
                              $return_silent = true;
                          }


                          return false;

                       }
                       
                        // Set the DB to always use UTF-8, if we're on MySQL 4.1 or higher..
                        $result = mysql_query("SELECT VERSION() as version;");
                        $row = mysql_fetch_assoc($result);

                        if (checkVersion($row['version'], "4.1.0")) {
                            mysql_query("SET CHARACTER SET 'utf8'", $this->sql_link);
			    mysql_query('SET NAMES utf8', $this->sql_link);
			    mysql_query('SET collation_connection = "utf8_unicode_ci"', $this->sql_link);
                        }

                    } else {

                        // No, couldn't. So we print an error
                        $this->error( "Can't connect to MySQL Database Engine", '', '' );

                        // If silent_after_failed_connect is set, from now on return without errors/warnings
                        if($this->silent_after_failed_connect) {
                            $return_silent = true;
                        }

                        return false;

                    }

                }

                return true;
                break;

            case "sqlite":

                /**
                 * Set up a link for SQLite model
                 */

                // .. TODO

                break;


            case "postgresql":

                /**
                 * Set up a link for PostgreSQL model
                 */

                // .. TODO

                break;

            default:

                $this->error("Unknown Database Model!");
                break;


        }


    }


    /**
     * Close Mysql link
     */
    function close() {

        mysql_close( $this->sql_link );

    }


    /**
     * Gets the current MySQL version
     *
     * @return string
     */
    function get_server_info() {

        $version = mysql_get_server_info();
        list($version) = explode("_", $version);

        return $version;

    }

    /**
     * If an error has occured, we print a message. If 'halt_on_sql_error' is
     * set, we die(), else we continue.
     *
     * @param string $error_msg
     * @param string $sql_query
     * @param string $error_no
     *
     */
    function error( $error_msg="", $sql_query, $error_no )  {
        global $cfg;

        // if no error message was given, use the mysql error:
        if ( ($error_msg == "") && ($error_no != 0) ) {
            $error_msg = mysql_error();
        }


        /**
         * If we have a defined error_handler, we call that, else we'll print our own.
         */
        if (($this->error_handler!="") && (function_exists($this->error_handler))) {

            $handler = $this->error_handler;

            $handler('sql', $error_msg, $sql_query, $error_no);

        } else {

            $error_date = date("F j, Y, g:i a");

            $error_page = "<div style='border: 1px solid #AAA; padding: 4px; background-color: #EEE; font-family: Consolas, Courier, \"Courier New\", monospace; font-size: 80%;'><strong>mySQL Error</strong>".
            "\n\nThere appears to be an error while trying to complete your request.\n\n".
            "<strong>Query: </strong>      ".htmlentities($sql_query)."\n".
            "<strong>mySQL error:</strong> ".htmlentities($error_msg)."\n".
            "<strong>Error code:</strong>  {$error_no}\n".
            "<strong>Date:</strong>        {$error_date}\n</div>\n";

            echo(nl2br($error_page));

        }

        if (function_exists('debug_printbacktrace')) {
            // call debug_printbacktrace if it's available..
            debug_printbacktrace();
        }

        if ($this->halt_on_sql_error == true ) {
            die();
        }

    }




    /**
     * Performs a query. Either pass the query to e executed as a parameter,
     *
     * @param string query
     */
    function query( $query="" ) {
        global $PIVOTX, $timetaken;

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        // perhaps use the cached query
        if ($query=="") {
            $query = $this->cached_query;
        }


        // Set the last_query
        $this->last_query = $query;

		$now = timeTaken('int');

        // execute it.
        $this->sql_result = @mysql_query( $query, $this->sql_link );

        // If we're profiling, we use the following to get an array of all queries.
        // We also debug queries that took relatively long to perform.
        if ($PIVOTX['config']->get('debug')) {
            
            $querytimetaken = round(timeTaken('int') - $now, 4);

            if ((timeTaken('int') - $now) > 0.4) {
                debug("\nStart: ". $now ." - timetaken: " . $querytimetaken);
			    debug(htmlentities($query)."\n\n");
                debug_printbacktrace();
            }

            $query = preg_replace("/\s+/", " ", $query);

            // If debug is enabled, we add a small comment to the query, so that it's
            // easier to track where it came from
            if ( function_exists('debug_backtrace') ) {
                $trace = debug_backtrace();
                $comment = sprintf(" -- %s - %s():%s ", basename($trace[0]['file']), $trace[1]['function'], $trace[0]['line']);
                $query .= $comment;               
            }
            
            //echo "<pre>\n"; print_r($query); echo "</pre>";
            
            $GLOBALS['query_log'][] = $query . " -- $querytimetaken sec. ";
            
        }

        

        if (!$this->sql_result) {

            // If an error occured, we output the error.
            $this->error('', $this->last_query, mysql_errno( $this->sql_link ) );

            $this->num_affected_rows = 0;

            return false;

        } else {

            // Count the num of results, and raise the total query count.
            $timetaken['query_count']++;
            $timetaken['sql'] += timetaken('int') - $now;

            $this->num_affected_rows = mysql_affected_rows($this->sql_link);

            return true;

        }


    }



    /**
     * Get the last performed or stored query
     *
     * @param  none
     */
    function get_last_query () {

        return $this->last_query;

    }



    /**
     * Get the last inserted id
     *
     * @param  none
     */
    function get_last_id() {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }


        $this->query("SELECT LAST_INSERT_ID() AS id");

        $row = $this->fetch_row();


        return $row['id'];

    }

    /**
     * Gets the number of selected rows
     */
    function num_rows()  {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $mysql_rows = mysql_num_rows( $this->sql_result );

        return $mysql_rows;

    }


    /**
     * Gets the number of affected rows
     */
    function affected_rows() {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $mysql_affected_rows = mysql_affected_rows( $this->sql_link );

        return $mysql_affected_rows;

    }



    /**
     * Quote variable to make safe to use in a SQL query. If you pass
     * $skipquotes as true, the string will just have added slashes, otherwise it
     * will be wrapped in quotes for convenience
     *
     * @param string $value to quote
     * @param boolean $skipquotes  to skip adding quotes
     * @return string quoted value
     */
    function quote($value, $skipquotes=false) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        // Stripslashes
        //if (get_magic_quotes_gpc()) {
        //    $value = stripslashes($value);
        //}

        //check if this function exists
        if( function_exists( "mysql_real_escape_string" ) ) {
            $value = mysql_real_escape_string( $value );
        }  else   {
            //for PHP version < 4.3.0 use addslashes
            $value = addslashes( $value );
        }

        if(!$skipquotes) {
            $value = "'" . $value . "'";
        }

        return $value;
    }


    /**
     * Fetch a single row from the last results.
     *
     * @param string $getnames
     * @return array row
     *
     */
    function fetch_row($getnames="with_names") {

        if ( $this->num_rows() > 0 ) {

            if ($getnames != "no_names") {
                $mysql_array = mysql_fetch_assoc( $this->sql_result );
            } else {
                $mysql_array = mysql_fetch_row( $this->sql_result );
            }

            if (!is_array( $mysql_array )) {
                return false;
            } else {
                return $mysql_array;
            }
        } else {

            return false;

        }
    }


    /**
     * Fetch all rows from the last results.
     *
     * @param string $getnames
     * @return array rows
     *
     */
    function fetch_all_rows($getnames="with_names") {

        $results = array();

        if ( $this->num_rows() > 0 ) {

            if ($getnames!="no_names") {
                while($row = mysql_fetch_assoc( $this->sql_result )) {
                    $results[] = $row;
                }
            } else {
                while($row = mysql_fetch_row( $this->sql_result )) {
                    $results[] = $row;
                }

            }

            return $results;

        } else {

            return false;

        }

    }

    /**
     * Returns the number of executed queries
     */
    function query_count() {
        global $timetaken;

        return intval($timetaken['query_count']);

    }


    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * // Simple select query instruction:
     * $qry = array();
     * $qry['select'] = "*";
     * $qry['from'] = "employees";
     * $qry['where'][] = "firstname LIKE 'b%'";
     *
     * // build and execute query..
     * $database->build_select($qry);
     * $database->query();
     *
     * $rows = $database->fetch_all_rows();
     *
     *
     * Example 2:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * // Simple select query instruction:
     * $qry = array();
     * $qry['select'] = "employees.*, inventory.*, computers.*";
     * $qry['from'] = "inventory";
     * $qry['leftjoin']['employees'] = "employees.uid = inventory.employee_uid";
     * $qry['leftjoin']['computers'] = "computers.uid = inventory.employee_uid";
     * $qry['limit'] = "3";
     *
     * // build and execute query..
     * $database->build_select($qry);
     * $database->query();
     *
     * $rows = $database->fetch_all_rows();
     *
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_select($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "SELECT ". $q['select'] ;
        $output .= "\nFROM ". $q['from'] ;


        // plak de left join's aan elkaar
        if ( (isset($q['leftjoin'])) && (is_array($q['leftjoin'])) ) {
            foreach ($q['leftjoin'] as $table => $leftjoin) {
                $output .= "\nLEFT JOIN $table ON ( ".$leftjoin." )";
            }
        }

        // plak de straight join's aan elkaar
        if ( (isset($q['join'])) && (is_array($q['join'])) ) {
            foreach ($q['join'] as $table => $join) {
                $output .= "\nJOIN $table ON ( ".$join." )";
            }
        }


        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }



        // plak de where_or's aan elkaar
        if ( (isset($q['where_or'])) && (is_array($q['where_or'])) ) {

            // remove empty where_or's
            foreach($q['where_or'] as $key=>$value) {
                if ($value=="") { unset($q['where_or'][$key]); }
            }

            $where = implode(" OR ", $q['where_or']);

            if (count($q['where'])>=1) {
                $output .= "\nAND ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where_or'])) && (is_string($q['where_or'])) ) {

            if (count($q['where'])>1) {
                $output .= "\nAND ( ". $q['where_or'] ." ) ";
            } else {
                $output .= "\nWHERE ". $q['where_or'];
            }
        }



        // plak de group's aan elkaar
        if ( (isset($q['group'])) && (is_array($q['group'])>0) ) {
            // if $q['group'] is an array..
            $group = implode(", ", $q['group']);
            $output .= "\nGROUP BY ". $group;
        } else if ( (isset($q['group'])) && (is_string($q['group'])) ) {
            // if $q['group'] is a single string..
            $output .= "\nGROUP BY ". $q['group'];
        }

        // plak de order's aan elkaar
        if ( (isset($q['order'])) && (is_array($q['order'])) ) {
            // if $q['order'] is an array..
            $order = implode(", ", $q['order']);
            $output .= "\nORDER BY ". $order;
        } else if ( (isset($q['order'])) && (is_string($q['order'])) ) {
            // if $q['order'] is a single string..
            $output .= "\nORDER BY ". $q['order'];
        }

        // eventueel een limit
        if (isset($q['limit'])) {
            $output .= "\nLIMIT  ". $q['limit'];
        }

        $output .=";";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }



    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['into'] = "employees";
     * $qry['value']['firstname'] = "Henk";
     * $qry['value']['lastname'] = "de Vries";
     *
     * // build and execute query..
     * $database->build_insert($qry);
     * if($database->query()) {
     *   echo "<p>updated!</p>";
     * } else {
     *   echo "<p>not updated!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_insert($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "INSERT INTO ". $q['into'] ." (";

        // Value looks like: $qry['value']['name'] = $value;
        if (count($q['value'])>0) {
            foreach ($q['value'] as $key => $val) {
                $q['fields'][] = $key;
                $q['values'][] = $val;
            }
        }

        // plak de velden aan elkaar
        if (count($q['fields'])>0) {

            $fields = "`" . implode("`, `", $q['fields']). "`" ;
            $output .= $fields;
        }
        $output .= ") \nVALUES (";

        // plak de waarden aan elkaar
        if (count($q['values'])>0) {

            foreach( $q['values'] as $key => $value) {

                if($this->is_mysql_function($value)) {
                    $q['values'][$key] = $value;
                } else {
                    $q['values'][$key] = $this->quote($value);
                }

            }

            $values = implode(", ", $q['values']);
            $output .= $values;
        }
        $output .= ");";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }



    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['update'] = "inventory";
     * $qry['value']['amount'] = "(RAND())*100";
     * $qry['where'][] = "uid=100";
     *
     * // build and execute query..
     * $database->build_update($qry);
     * if($database->query()) {
     *   echo "<p>updated!</p>";
     * } else {
     *   echo "<p>not updated!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_update($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "UPDATE ". $q['update'] ." SET ";

        // Value looks like: $qry['value']['name'] = $value;
        if (count($q['value'])>0) {
            foreach ($q['value'] as $key => $val) {
                $q['fields'][] = $key;
                $q['values'][] = $val;
            }
        }

        // plak de velden aan elkaar
        if (count($q['fields'])>0) {

            $values = array();
            for ($i=0; $i<count($q['fields']); $i++) {

                $key = $q['fields'][$i];
                $value =$q['values'][$i];

                if($this->is_mysql_function($value)) {
                    $values[] = sprintf(" `%s`=%s ",$key, $value );
                } else {
                    $values[] = sprintf(" `%s`=%s ",$key, $this->quote($value) );
                }
            }

            $values = implode(", ", $values);
            $output .= $values;
        }

        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }

        $output .=";";

        // store as cached function$output .=";";
        $this->cached_query = $output;

        return $output;
    }

    /**
     * A function to build select queries. After compiling the query it is stored
     * internally, so we can use it when calling query()
     *
     * Example 1:
     *
     * // Initialize the sql object.
     * $database = new sql();
     *
     * $qry = array();
     * $qry['delete'] = "inventory";
     * $qry['where'][] = "uid=100";
     * $qry['limit'] = "3";
     *
     * // build and execute query..
     * $database->build_delete($qry);
     * if($database->query()) {
     *   echo "<p>deleted!</p>";
     * } else {
     *   echo "<p>not deleted!</p>";
     * }
     *
     * @param array $q
     * @return string the compiled query
     */
    function build_delete($q) {

        // If there's no DB connection yet, set one up if we can.
        if(!$this->connection()) {
            return false;
        }

        $output = "DELETE FROM ". $q['delete'];

        // plak de where's aan elkaar
        if ( (isset($q['where'])) && (is_array($q['where'])) ) {

            // remove empty where's
            foreach($q['where'] as $key=>$value) {
                if ($value=="") { unset($q['where'][$key]); }
            }

            $where = implode(" AND ", $q['where']);

            if (count($q['where'])>1) {
                $output .= "\nWHERE ( ". $where ." ) ";
            } else {
                $output .= "\nWHERE ". $where;
            }
        } else if ( (isset($q['where'])) && (is_string($q['where'])) ) {
            $output .= "\nWHERE ". $q['where'];
        }

        // eventueel een limit
        if (isset($q['limit'])) {
            $output .= "\nLIMIT  ". $q['limit'];
        }

        $output .=";";

        // store as cached function
        $this->cached_query = $output;

        return $output;
    }


    /**
     * Checks if the parameter is an mysql function or not. used to determine
     * whether or not a parameter needs to be escaped.
     *
     * $this->is_mysql_function("some value");
     * // returns true
     *
     * $this->is_mysql_function("some value");
     * // returns true
     *
     * @param string string
     * @return boolean
     */
    function is_mysql_function($str) {

        // Check if we're even allowed to use MySQL functions. If not, return right away..
        if (!$this->allow_functions) {
            return false;
        }
        
        // Determine if value is a literal value, or a mysql function.
        if(preg_match("/^([A-Z]{3,}\((.*)\))/", $str, $match)) {
            return true;
        } else {
            return false;
        }

    }


    /**
     * Set if we're allowed to use MySQL functions in our queries. This is disabled
     * by default, for security reasons.
     *
     * @param boolean $value
     */
    function set_allow_functions($value) {
        $this->allow_functions = ($value ? true : false);
    }


    /**
     * Sets whether or not execution of the script should stop when a
     * mysql error has occured.
     *
     *
     * @param boolean value
     */
    function set_halt_on_error($value) {

        $this->halt_on_sql_error = ($value ? true : false);

    }
    
}


?>
