<?php

require_once('Database.php');
require_once('../model/Response.php');
require_once('../helper/sanitize/Task.php');

 class Task {

    private $DB;
    private $response;
    public $taskArray_return = [];
    public $final_return = [];


    public function __construct() {
      $this->DB = new Database();
      $this->response = new Response();
    }
    
    
    public function getTaskById($task_id) {
      
      try {
      $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE id = :id');
      $this->DB->bind(':id', $task_id);
      $this->DB->execute();
      $_rowCount = $this->DB->rowCount();
      $_tasksArray = $this->DB->getResult();


      if($_rowCount === 0) {
        $this->response->setHttpStatusCode(404);
        $this->response->setSuccess(false);
        $this->response->addMessage("Task Not Found");
        $this->response->send();
        exit();
      } 

       // just sanitizing.
       while($row = $_tasksArray) {
        $id = $row['id'];
        $title = $row['title'];
        $description = $row['description'];
        $date = date_create($row['deadline']);
        $deadline = date_format($date, 'd/m/Y H:i');
        $completed = $row['completed'];
        $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
        $this->taskArray_return[] = $task->returnTaskAsArray();
        
      } 

        $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return];

        return $this->final_return;
        
      } catch (TaskException $ex) {
        //throw $th;
        $this->response = new this->Response();
        $this->response->setHttpStatusCode(500);
        $this->response->setSuccess(false);
        $this->response->addMessage($ex->getMessage());
        $this->response->send();
        // Below this code won't fire anymore
        exit();
      } 
    }

    public function deleteById($task_id) {
      try {
        $this->DB->query('DELETE FROM tasks WHERE id = :id');
        $this->DB->bind(':id', $task_id);
        $this->DB->execute();
        
        $affectedRows = $this->DB->rowCount();

        if($affectedRows == 0) {
          $this->response = new Response();
          $this->response->setHttpStatusCode(500);
          $this->response->setSuccess(false);
          $this->response->addMessage("Cound Not find the id specified, thus deletion is not completed.");
          $this->response->send();
          // Below this code won't fire anymore
          exit();
        }

        return true;


      } catch (PPOException $ex) {
        // display error for developers
        error_log("Database query Error: " . $ex, 0);
        $this->response = new Response();
        $this->response->setHttpStatusCode(500);
        $this->response->setSuccess(false);
        $this->response->addMessage("Database query Error");
        $this->response->send();
        // Below this code won't fire anymore
        exit();
      }
    }

    public function getTasksByCompletion($completed) {
      try {
        $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE completed = :completed');
        $this->DB->bind(':completed', $completed);
        $this->DB->execute();

        $_rowCount = $this->DB->rowCount();
        $_tasksArray = $this->DB->getResult();

        while($row = $_tasksArray) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
          $date = date_create($row['deadline']);
          $deadline = date_format($date, 'd/m/Y H:i');
          $completed = $row['completed'];
          $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
          $this->taskArray_return[] = $task->returnTaskAsArray();
         }

        $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return];
        
        return $this->final_return;
      } 
      catch (TaskException $ex) {
      $response = new Response();
      $response->setHttpStatusCode(500);
      $response->setSuccess(false);
      $response->addMessage();
      $response->send($ex->getMessage());
      exit();
      }
      catch (PDOException $ex) {
      error_log($ex);
      $response = new Response();
      $response->setHttpStatusCode(500);
      $response->setSuccess(false);
      $response->addMessage("Failed to get tasks");
      $response->send();
      exit();
      }
    } else {
      $response = new Response();
      $response->setHttpStatusCode(405);
      $response->setSuccess(false);
      $response->addMessage("Method Not Allowed");
      $response->send();
      exit();
    }
    }



}