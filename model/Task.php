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
    
    
    public function getTaskById($task_id, $user_id) {
      
      try {
        
      $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE id = :id AND user_id = :user_id');
      $this->DB->bind(':id', $task_id);
      $this->DB->bind(':user=id', $user_id);
      $this->DB->execute();
      $_rowCount = $this->DB->rowCount();
 
      if($_rowCount === 0) {
        $this->response->errorResponse(404, "Task Not Found or Not Authorized");
      } 

       // just sanitizing.
       while($row = $this->DB->getResult()) {
        $id = $row['id'];
        $title = $row['title'];
        $description = $row['description'];
       
        $deadline = $row['deadline'];
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

    public function deleteById($task_id, $user_id) {
      try {
        $this->DB->query('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $this->DB->bind(':id', $task_id);
        $this->DB->bind(':user_id', $user_id);
        $this->DB->execute();
        
        $affectedRows = $this->DB->rowCount();

        if($affectedRows == 0) {
          $this->response = new Response();
          $this->response->setHttpStatusCode(500);
          $this->response->setSuccess(false);
          $this->response->addMessage("Cound Not find Task with Given Id or Not Authorized.");
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

    public function getTasksByCompletion($_completed, $user_id) {
      try {
        $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE completed = :completed AND user_id = :user_id');
        $this->DB->bind(':completed', $_completed);
        $this->DB->bind(':user_id', $user_id);
        $this->DB->execute();

        $_rowCount = $this->DB->rowCount();

        while($row = $this->DB->getResult()) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
        
          $deadline = $row['deadline'];
          $completed = $row['completed'];
          $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
          $this->taskArray_return[] = $task->returnTaskAsArray();
         }

        $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return];
        
        return $this->final_return;
      } 
      catch (TaskException $ex) {
      $this->response = new Response();
      $this->response->setHttpStatusCode(500);
      $this->response->setSuccess(false);
      $this->response->addMessage();
      $this->response->send($ex->getMessage());
      exit();
      }
      catch (PDOException $ex) {
      error_log($ex);
      $this->response = new Response();
      $this->response->setHttpStatusCode(500);
      $this->response->setSuccess(false);
      $this->response->addMessage("Failed to get tasks");
      $this->response->send();
      exit();
      }
    } 

    public function readPerPage($page, $limitPerPage, $user_id) {
      try {
        $this->DB->query('SELECT count(id) as totalNoOfTasks from tasks WHERE user_id = :user_id');
        $this->DB->bind('user_id', $user_id);
        $this->DB->execute();

        $row = $this->DB->getResult();

        $tasksCount = intval($row['totalNoOfTasks']);

        $pageNum = ceil($tasksCount / $limitPerPage);

        if($pageNum == 0) {
          $pageNum = 1;
        }

        if($page > $pageNum || $page == 0) {
          $this->response = new Response();
          $this->response->setHttpStatusCode(404);
          $this->response->setSuccess(false);
          $this->response->addMessage("page number does not exist");
          $this->response->send();
          exit();
        }

        $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));

        $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE user_id = :user_id LIMIT :offset, :pglimit');
        $this->DB->bind(':offset', $offset);
        $this->DB->bind(':user_id', $user_id);
        $this->DB->bind(':pglimit', $limitPerPage);
        $this->DB->execute();

        
        $_rowCount = $this->DB->rowCount();

        while($row = $this->DB->getResult()) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
         
          $deadline = $row['deadline'];
          $completed = $row['completed'];
          $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
          $this->taskArray_return[] = $task->returnTaskAsArray();
      }

      $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return, "totalRows" => $tasksCount, "totalPages" => $pageNum ];

      return $this->final_return;

    } catch (PDOException $ex) {
      error_log($ex);
      $this->response = new Response();
      $this->response->setHttpStatusCode(500);
      $this->response->setSuccess(false);
      $this->response->addMessage("Filed to get tasks");
      $this->response->send();
      exit();
    }
    catch (TaskException $ex) {
      $this->response = new Response();
      $this->response->setHttpStatusCode(500);
      $this->response->setSuccess(false);
      $this->response->addMessage($ex->getMessage());
      $this->response->send();
      exit();
    }
  }

  public function getAllTasks($user_id) {
    try {
      $this->DB->query('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed FROM tasks WHERE user_id = :user_id');
      $this->DB->bind(':user_id', intval($user_id));
      $this->DB->execute();

      $_rowCount = $this->DB->rowCount();

      while($row = $this->DB->getResult()) {
        $id = $row['id'];
        $title = $row['title'];
        $description = $row['description'];
        $deadline = $row['deadline'];
       
        $completed = $row['completed'];
        $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
        $this->taskArray_return[] = $task->returnTaskAsArray();
    }

    $this->final_return = ["rowCount" => $_rowCount, "taskArray" => $this->taskArray_return];

    return $this->final_return;

  } catch (TaskException $ex) {
    $this->response = new Response();
    $this->response->setHttpStatusCode(500);
    $this->response->setSuccess(false);
    $this->response->addMessage($ex->getMessage());
    $this->response->send();
    exit();
  }      
  catch (PDOException $ex) {
    error_log($ex);
    $this->response = new Response();
    $this->response->setHttpStatusCode(500);
    $this->response->setSuccess(false);
    $this->response->addMessage("Failed to get tasks");
    $this->response->send();
    exit();
  }

  }

  public function createTask($jsonData, $user_id) {
    try {
      
      $newTask = new TaskSanitize(null, $jsonData["title"], isset($jsonData["description"]) ? $jsonData["description"] : null, isset($jsonData["deadline"]) ? $jsonData["deadline"] : null, $jsonData["completed"]);

      $sanitizedArray = $newTask->returnTaskAsArray();

      $this->DB->query('INSERT INTO tasks (title, user_id, description, deadline, completed) VALUE (:title, :user_id, :description, STR_TO_DATE(:deadline, "%d/%m/%Y %H:%i"), :completed)');
      $this->DB->bind(":title", $sanitizedArray['title']);
      $this->DB->bind(":description", $sanitizedArray['description']);
      $this->DB->bind(":deadline", $sanitizedArray['deadline']);
      $this->DB->bind(":completed", $sanitizedArray['completed']);
      $this->DB->bind(":user_id", intval($user_id));

      $this->DB->execute();

      $insertedId = $this->DB->getLastId();
     

      $numRowAffected = $this->DB->rowCount();
      
      if($numRowAffected == 0) {
        $this->response->errorResponse(500, "Failed to create a task");
      }

      $this->DB->query("SELECT id, title, description, DATE_FORMAT(deadline, '%d/%m/%Y %H:%i') as deadline, completed FROM tasks WHERE id = :id");
      $this->DB->bind(':id', $insertedId);
      $this->DB->execute();

      $numRowAffected = $this->DB->rowCount();

      if($numRowAffected == 0) {
        $this->response->errorResponse(500, "Failed to create a task");
      }


      while($row = $this->DB->getResult()) {
        $id = $row['id'];
        $title = $row['title'];
        $description = $row['description'];
        $deadline = $row['deadline'];
        $completed = $row['completed'];
        $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
        $this->taskArray_return[] = $task->returnTaskAsArray();
    }

      $this->final_return = ["rowCount" => $numRowAffected, "taskArray" => $this->taskArray_return];

      return $this->final_return;

    } catch (TaskException $ex) {
    
      $this->response->errorResponse(500, $ex->getMessage());
    }      
    catch (PDOException $ex) {
      error_log($ex);
      $this->response->errorResponse(500, "Failed to create a task");
    }

    }

    public function updateTask($task_id, $requestData, $updateQuery, $checksheet, $user_id) {
      try {
        $this->DB->query("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
        $this->DB->bind(':id', $task_id);
        $this->DB->bind(':user_id', $user_id);
        $this->DB->execute();

        if($this->DB->rowCount() == 0) {
          $this->response->errorResponse(400, "Id is incorrect or Not Authorized");
        }
       
     
        $this->DB->query("UPDATE tasks 
        SET 
          $updateQuery
        WHERE 
          id = :id
        AND
          user_id = :user_id
        ");

        foreach($checksheet as $key => $value) {
          if($value == true) {
            $this->DB->bind(":$key", $requestData[$key]);
          }
        }

        $this->DB->bind(":id", $task_id);
        $this->DB->bind(":user_id", $user_id);

        $this->DB->execute();

        if($this->DB->rowCount() == 0) {
          $this->response->errorResponse(400, "Failed to update, rowCount is still 0");
        }

  
        $this->DB->query("SELECT id, title, description, DATE_FORMAT(deadline, '%d/%m/%Y %H:%i') as deadline, completed FROM tasks WHERE id = :id AND user_id = :user_id");
        $this->DB->bind(':id', $task_id);
        $this->DB->bind(":user_id", $user_id);
        $this->DB->execute();
  
        $numRowAffected = $this->DB->rowCount();
  
        if($numRowAffected == 0) {
          $this->response->errorResponse(500, "Failed to create a task");
        }
  
        while($row = $this->DB->getResult()) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
          $deadline = $row['deadline'];
          $completed = $row['completed'];
          $task = new TaskSanitize($id, $title, $description, $deadline, $completed);
          $this->taskArray_return[] = $task->returnTaskAsArray();
      }
  
        $this->final_return = ["rowCount" => $numRowAffected, "taskArray" => $this->taskArray_return];
  
        return $this->final_return;
    
  
      } catch (TaskException $ex) {
      
        $this->response->errorResponse(500, $ex->getMessage());
      }      
      catch (PDOException $ex) {
        error_log($ex);
        $this->response->errorResponse(500, "Failed to create a task");
      }
  
      }
  


}