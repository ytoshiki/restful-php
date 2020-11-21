<?php

  require_once('../controller/Database.php');
  require_once('Response.php');

  class User {

    private $db;

    public function __construct() {
      $this->db = new Database();
      $this->response = new Response();
    }

    public function checkUserOverlap($username) {
      try {
        $this->db->query('SELECT id FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();
        $rowCount = $this->db->rowCount();
        if($rowCount == 0) {
          return true;
        } else {
          return false;
        }
      } catch (PDOException $ex) {
        error_log($ex);
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong creating a user");
      }
    }

    public function createUser($fullname, $username, $password) {
      try {
        $this->db->query('INSERT INTO users 
        (fullname, username, password) 
        VALUES (:fullname, :username, :password)
        ');
        $this->db->bind(':fullname', $fullname);
        $this->db->bind(':username', $username);
        $this->db->bind(':password', $password);
        $this->db->execute();
        $rowCount = $this->db->rowCount();

        if($rowCount == 0) {
          $this->response->errorResponse(500, "There was an error creating user");
        }

        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $this->db->getLastId());
        $this->db->execute();
      
        $return_data = [];
        while($row = $this->db->getResult()) {
          $return_data['id'] = $row['id'];
          $return_data['fullname'] = $row['fullname'];
          $return_data['username'] = $row['username'];
        }

        return $return_data;


      } catch (PDOException $ex) {
        error_log($ex);
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong creating a user");
      }
    }

    public function findUser($username, $password) {
      try {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $this->db->execute();
        $rowCount = $this->db->rowCount();
        if($rowCount == 0) {
          $this->response->errorResponse(401, "Could not find User");
        }


        $row = $this->db->getResult();
        $_id = $row["id"];
        $_hashedPassword = $row["password"];
        $_fullname = $row["fullname"];
        $_username = $row["username"];
        $_useractive = $row["useractive"];
        $_loginattempts = $row["loginattempts"];

        if($_useractive !== "Y") {
          $this->response->errorResponse(401, "User is not currently active");
        }

        if($_loginattempts >= 5) {
          $this->response->errorResponse(401, "Too many attempts found: user is currently locked out");
        }

        if(!password_verify($password, $_hashedPassword)) {
          $this->db->query('UPDATE users SET loginattempts = :loginattempts WHERE id = :id');
          $this->db->bind(':id', $_id);
          $this->db->bind(':loginattempts', ++$_loginattempts);
          $this->db->execute();

          $this->response->errorResponse(401, "Username or password is incorrect");
        }

        $backToClient = [];
        $backToClient['id'] = $_id;
        return $backToClient;



      } catch (PDOException $ex) {
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong finding User");
      }
    }

  


  }