<?php
include_once 'db_cred.php';

/**a
 *@version 1.1
 */
if (!class_exists('db_connection')) {
    class db_connection
    {
        //properties
        public $db = null;
        public $results = null;

        //connect
        /**
         * Database connection
         * @return boolean
         **/
        function db_connect()
        {
            //connection
            $this->db = mysqli_connect(SERVER, USERNAME, PASSWD, DATABASE);

            //test the connection
            if (mysqli_connect_errno()) {
                return false;
            } else {
                // Set charset to UTF-8 with compatible collation
                // Use utf8mb4_general_ci and allow conversion to latin1 tables
                mysqli_set_charset($this->db, "utf8mb4");
                mysqli_query($this->db, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
                // Allow conversion between collations for compatibility with latin1 tables
                mysqli_query($this->db, "SET character_set_connection = utf8mb4");
                mysqli_query($this->db, "SET collation_connection = utf8mb4_general_ci");
                return true;
            }
        }

        function db_conn()
        {
            //connection
            $this->db = mysqli_connect(SERVER, USERNAME, PASSWD, DATABASE);

            //test the connection
            if (mysqli_connect_errno()) {
                return false;
            } else {
                // Set charset to UTF-8 with compatible collation
                // Use utf8mb4_general_ci and set character_set_connection to handle latin1 tables
                mysqli_set_charset($this->db, "utf8mb4");
                mysqli_query($this->db, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
                mysqli_query($this->db, "SET character_set_connection = utf8mb4");
                mysqli_query($this->db, "SET collation_connection = utf8mb4_general_ci");
                return $this->db;
            }
        }

        //execute a query for SELECT statements
        /**
         * Query the Database for SELECT statements
         * @param string $sqlQuery
         * @return boolean
         **/
        function db_query($sqlQuery)
        {
            if (!$this->db_connect()) {
                return false;
            } elseif ($this->db == null) {
                return false;
            }

            // Ensure UTF-8 charset is set with compatible collation
            mysqli_set_charset($this->db, "utf8mb4");
            mysqli_query($this->db, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
            mysqli_query($this->db, "SET character_set_connection = utf8mb4");
            mysqli_query($this->db, "SET collation_connection = utf8mb4_general_ci");

            //run query 
            $this->results = mysqli_query($this->db, $sqlQuery);

            if ($this->results == false) {
                return false;
            } else {
                return true;
            }
        }

        //execute a query for INSERT, UPDATE, DELETE statements
        /**
         * Query the Database for INSERT, UPDATE, DELETE statements
         * @param string $sqlQuery
         * @return boolean
         **/
        function db_write_query($sqlQuery)
        {
            // Reuse existing connection if available, otherwise create new one
            if (!isset($this->db) || !$this->db) {
                if (!$this->db_connect()) {
                    return false;
                }
            }

            // Ensure UTF-8 charset is set with compatible collation
            mysqli_set_charset($this->db, "utf8mb4");
            mysqli_query($this->db, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
            mysqli_query($this->db, "SET character_set_connection = utf8mb4");
            mysqli_query($this->db, "SET collation_connection = utf8mb4_general_ci");

            //run query 
            $result = mysqli_query($this->db, $sqlQuery);

            if ($result == false) {
                // Log the actual MySQL error for debugging
                $error = mysqli_error($this->db);
                $errno = mysqli_errno($this->db);
                error_log("MySQL Error ($errno): $error");
                error_log("Failed Query: " . substr($sqlQuery, 0, 200));
                return false;
            } else {
                return true;
            }
        }

        //fetch a single record
        /**
         * Get a single record
         * @param string $sql
         * @return array|false
         **/
        function db_fetch_one($sql)
        {
            // if executing query returns false
            if (!$this->db_query($sql)) {
                return false;
            }
            //return a record
            return mysqli_fetch_assoc($this->results);
        }

        //fetch all records
        /**
         * Get all records
         * @param string $sql
         * @return array|false
         **/
        function db_fetch_all($sql)
        {
            // if executing query returns false
            if (!$this->db_query($sql)) {
                return false;
            }
            //return all records
            return mysqli_fetch_all($this->results, MYSQLI_ASSOC);
        }

        //count data
        /**
         * Get count of records
         * @return int|false
         **/
        function db_count()
        {
            //check if result was set
            if ($this->results == null) {
                return false;
            } elseif ($this->results == false) {
                return false;
            }

            //return count
            return mysqli_num_rows($this->results);
        }

        function last_insert_id()
        {
            return mysqli_insert_id($this->db);
        }

        //execute a prepared statement
        /**
         * Execute a prepared statement
         * @param string $sql
         * @param string $types
         * @param array $params
         * @return boolean|mysqli_result
         **/
        function db_prepare_execute($sql, $types = '', $params = [])
        {
            if (!$this->db_connect()) {
                return false;
            } elseif ($this->db == null) {
                return false;
            }

            // Ensure UTF-8 charset is set with compatible collation
            mysqli_set_charset($this->db, "utf8mb4");
            mysqli_query($this->db, "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
            mysqli_query($this->db, "SET character_set_connection = utf8mb4");
            mysqli_query($this->db, "SET collation_connection = utf8mb4_general_ci");

            $stmt = mysqli_prepare($this->db, $sql);
            if (!$stmt) {
                return false;
            }

            if (!empty($types) && !empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }

            $result = mysqli_stmt_execute($stmt);

            if (!$result) {
                mysqli_stmt_close($stmt);
                return false;
            }

            // Check if this is a SELECT statement that returns a result set
            $stmt_result = mysqli_stmt_get_result($stmt);

            if ($stmt_result !== false) {
                // This was a SELECT statement, return the result set
                mysqli_stmt_close($stmt);
                return $stmt_result;
            } else {
                // This was an INSERT/UPDATE/DELETE statement, return true on success
                mysqli_stmt_close($stmt);
                return true;
            }
        }

        //fetch one using prepared statement
        /**
         * Get a single record using prepared statement
         * @param string $sql
         * @param string $types
         * @param array $params
         * @return array|false
         **/
        function db_prepare_fetch_one($sql, $types = '', $params = [])
        {
            $result = $this->db_prepare_execute($sql, $types, $params);
            if (!$result || !is_object($result)) {
                return false;
            }
            return mysqli_fetch_assoc($result);
        }

        //fetch all using prepared statement
        /**
         * Get all records using prepared statement
         * @param string $sql
         * @param string $types
         * @param array $params
         * @return array|false
         **/
        function db_prepare_fetch_all($sql, $types = '', $params = [])
        {
            $result = $this->db_prepare_execute($sql, $types, $params);
            if (!$result || !is_object($result)) {
                return false;
            }
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
    }
}
