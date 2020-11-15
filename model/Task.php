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
 
      if($_rowCount === 0) {
        $this->response->errorResponse(404, "Task Not Found");
      } 

       // just sanitizing.
       while($row = $this->DB->getResult()) {
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

        while($row = $this->DB->getResult()) {
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
    } 

    public function readPerPage($page, $limitPerPage) {
      try {
        $this->DB->query('SELECT count(id) as totalNoOfTasks from tasks');
        $this->DB->execute();

        $row = $this->DB->getResult();

        $tasksCount = intval($row['totalNoOfTasks']);

        $pageNum = ceil($tasksCount / $limitPerPage);

        if($pageNum == 0) {
          $pageNum = 1;
        }

        if($page > $pageNum || $page == 0) {
          $response = new Response();
          $response->setHttpStatusCode(404);
          $response->setSuccess(false);
          $response->addMessage("page number does not exist");
          $response->send();
          exit();
        }

        $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));

        $this->DB->query('SELECT * FROM tasks LIMIT :offset, :pglimit');
        $this->DB->bind(':offset', $offset);
        $this->DB->bind(':pglimit', $limitPerPage);
        $this->DB->execute();

        
        $_rowCount = $this->DB->rowCount();

        while($row = $this->DB->getResult()) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
          $date = date_create($row['deadline']);
          $deadline = date_format($date, 'd/m/Y H:i');
          $completed = $row['completed'];
          $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
          $this->taskArray_return[] = $task->returnTaskAsArray();
      }

      $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return, "totalRows" => $tasksCount, "totalPages" => $pageNum ];

      return $this->final_return;

    } catch (PDOException $ex) {
      error_log($ex);
      $response = new Response();
      $response->setHttpStatusCode(500);
      $response->setSuccess(false);
      $response->addMessage("Filed to get tasks");
      $response->send();
      exit();
    }
    catch (TaskException $ex) {
      $response = new Response();
      $response->setHttpStatusCode(500);
      $response->setSuccess(false);
      $response->addMessage($ex->getMessage());
      $response->send();
      exit();
    }
  }

  public function getAllTasks() {
    try {
      $this->DB->query('SELECT * FROM tasks');
      $this->DB->execute();

 

      $_rowCount = $this->DB->rowCount();

      while($row = $this->DB->getResult()) {
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
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage($ex->getMessage());
    $response->send();
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

  }



}