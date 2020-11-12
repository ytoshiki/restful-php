<?php

  class Database {
    private static $writeDBConnection;
    private static $readDBConnection;

    private $host = "localhost";
    private $dbname = "tasksdb";
    private $user = "root";
    private $pass = "y7d4RFWY";

    public static function connectWriteDB() {
      if(self::$writeDBConnection === null) {
        self::$writeDBConnection = new PDO("mysql:host=localhost;port=3307;dbname=tasksdb", "root", "y7d4RFWY");

        self::$writeDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$writeDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      }

      return self::$writeDBConnection;
    }

    public static function connectReadDB() {
      if(self::$readDBConnection === null) {
        self::$readDBConnection = new PDO("mysql:host=localhost;port=3307;dbname=tasksdb", "root", "y7d4RFWY");

        self::$readDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$readDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      }

      return self::$readDBConnection;
    }
  }