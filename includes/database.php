<?php
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $connection;
        private $host = 'localhost';
        private $username = 'root';
        private $password = '';
        private $database = 'loan_automate_db';
        private $inTransaction = false;

        private function __construct() {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                die("Connection failed: " . $this->connection->connect_error);
            }
        }

        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function getConnection() {
            return $this->connection;
        }

        public function query($sql) {
            $result = $this->connection->query($sql);
            if ($result === false) {
                throw new Exception("Query failed: " . $this->connection->error);
            }
            return $result;
        }

        public function prepare($sql) {
            if (!$this->connection) {
                throw new Exception("Database connection not established");
            }
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            return $stmt;
        }

        public function escape($value) {
            return $this->connection->real_escape_string($value);
        }

        public function getLastError() {
            return $this->connection->error;
        }

        public function getLastInsertId() {
            return $this->connection->insert_id;
        }

        public function beginTransaction() {
            if ($this->inTransaction) {
                return false;
            }
            $this->connection->begin_transaction();
            $this->inTransaction = true;
            return true;
        }

        public function commit() {
            if (!$this->inTransaction) {
                return false;
            }
            $result = $this->connection->commit();
            $this->inTransaction = false;
            return $result;
        }

        public function rollback() {
            if (!$this->inTransaction) {
                return false;
            }
            $result = $this->connection->rollback();
            $this->inTransaction = false;
            return $result;
        }

        public function inTransaction() {
            return $this->inTransaction;
        }

        public function getAffectedRows() {
            return $this->connection->affected_rows;
        }

        public function __destruct() {
            if ($this->connection) {
                $this->connection->close();
            }
        }
    }
} 