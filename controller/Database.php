<?php

  require_once('../model/Response.php');

  class Database {

    private $DBConnection;
    private $stmt;
    private $response;
    private $host = "localhost";
    private $dbname = "tasksdb";
    private $user = "root";
    private $pass = "y7d4RFWY";

    public function __construct() {
      $this->response = new Response();
      $this->connectDB();
    }

    public function connectDB() {

      if($this->DBConnection === null) {

          try {
            $this->DBConnection = new PDO("mysql:host=localhost;port=3307;dbname=tasksdb", "root", "y7d4RFWY");

            $this->DBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->DBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $this->DBConnection;
          } catch (PDOException $ex) {
            // display error for developers
            error_log("Database query Error: " . $ex, 0);
            $this->response->setHttpStatusCode(500);
            $this->response->setSuccess(false);
            $this->response->addMessage("Database query Error");
            $this->response->send();
            // Below this code won't fire anymore
            exit();
          }
          }

    }

    public function query($query) {
      $this->stmt = $this->DBConnection->prepare($query);
    }

    public function bind($param, $value, $type = null) {
      if(is_null($type)) {
        switch(true) {
          case is_int($value):
            $type = PDO::PARAM_INT;
          break;
          case is_bool($value):
            $type = PDO::PARAM_BOOL;
          break;
          case is_null($value):
            $type = PDO::PARAM_NULL;
          break;
          default:
          $type = PDO::PARAM_STR;
        }
      }
      $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
      return $this->stmt->execute();
    }

    public function getResult() {
      return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount() {
      return $this->stmt->rowCount();
    }


  }