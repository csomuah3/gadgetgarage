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
            if (!$this->db_connect()) {
                return false;
            } elseif ($this->db == null) {
                return false;
            }

            //run query 
            $result = mysqli_query($this->db, $sqlQuery);

            if ($result == false) {
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
