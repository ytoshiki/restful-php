<?php

  
  require_once('../model/Response.php');
  require_once('../model/Task.php');
  require_once('../helper/sanitize/Task.php');

  // Attempting to connect to DB
  $taskModel = new Task();
  $response = new Response();
  $responseFromModel = [];
  
 
  // Query handling 
  if(array_key_exists("taskid", $_GET)) {

    $task_id = $_GET['taskid'];

    if($task_id == '' || !is_numeric($task_id)) {
      $response->setHttpStatusCode(400);
      $response->setSuccess(false);
      $response->addMessage("Task ID cannot be blank or must be numeric");
      $response->send();
      exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Get Task By ID  
         $responseFromModel = $taskModel->getTaskById($task_id);

        // Return to client
        $returnData = [];
        $returnData['rows_returned'] =  $responseFromModel['rowCount'];
        $returnData['tasks'] =  $responseFromModel['taskArray'];

        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Response success");
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit();
  
    } elseif($_SERVER['REQUEST_METHOD'] === 'DELETE') {

      if($taskModel->deleteById($task_id)) {

        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Deleted Successfuly");
        $response->setData([]);
        $response->send();

      }

    }elseif($_SERVER['REQUEST_METHOD'] === 'PUT') {

    } else {
      $response->NoMethodError();
    }
  
  } elseif(array_key_exists("complete", $_GET)) {
 
    $completed = $_GET['complete'];

    if($completed !== 'Y' && $completed !== 'N') {
     $response->FOFError('Complete must be Y or N');
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {

        $responseFromModel = $taskModel->getTasksByCompletion();

        
        $returnData = [];
        $returnData['rows_returned'] =  $responseFromModel['rowCount'];
        $returnData['tasks'] =  $responseFromModel['taskArray'];

        $response->successResponse(200, 'Tasks Successfully Fetched', $returnData);
       
    } else {
      $response->NoMethodError();
    }


  } elseif(array_key_exists("page", $_GET)){
    if($_SERVER['REQUEST_METHOD'] == "GET") {

      $page = $_GET['page'];

      if(!is_numeric($page) || $page == '') {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("something wrong with the page number");
        $response->send();
        exit();
      }

      $limitPerPage = 20;
      $pageNum;

      try {
        $stmt = $readDB->prepare('SELECT count(id) as totalNoOfTasks from tasks');
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

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

        $stmt = $readDB->prepare('SELECT * FROM tasks LIMIT :offset, :pglimit');
        $stmt->bindParam(':offset', $offset);
        $stmt->bindParam(':pglimit', $limitPerPage);
        $stmt->execute();

        $rowCount = $stmt->rowCount();

        
        $taskArray = [];

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
          $date = date_create($row['deadline']);
          $deadline = date_format($date, 'd/m/Y H:i');
          $completed = $row['completed'];
          $task = new Task($id, $title, $description, $deadline, $completed);
          $taskArray[] = $task->returnTaskAsArray();
      }

        $returnData = [];
        $returnData['rows_returned'] = $rowCount;
        $returnData['total_rows'] = $tasksCount;
        $returnData['total_pages'] = $pageNum;
        $nextPageBool = ($page < $pageNum);
        $returnData['has_next_page'] = $nextPageBool;
        $prevPageBool = ($page > 1);
        $returnData['has_prev_page'] = $prevPageBool;
        $returnData['tasks'] = $taskArray;
        


        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Response success");
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit();

      } 
      catch (PDOException $ex) {
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

    } else {
      $response = new Response();
      $response->setHttpStatusCode(405);
      $response->setSuccess(false);
      $response->addMessage("Method Not Allowed");
      $response->send();
      exit();
    }
  }elseif(empty($_GET)) {
    if($_SERVER['REQUEST_METHOD'] == 'GET') {

      try {
        
        $stmt = $readDB->query('SELECT * FROM tasks');
        $stmt->execute();
        $taskCount = $stmt->rowCount();

        $taskArray = [];

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $id = $row['id'];
          $title = $row['title'];
          $description = $row['description'];
          $date=date_create($row['deadline']);
          $deadline = date_format($date,"d/m/Y H:i");
          $completed = $row['completed'];
          $task = new Task($id, $title, $description, $deadline, $completed);
          $taskArray[] = $task->returnTaskAsArray();
        }

        $returnData = [];
        $returnData['rowCount'] = $taskCount;
        $returnData['data'] = $taskArray;

        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Response success");
        $response->toCache(true);
        $response->setData($returnData);
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
      catch (PDOException $ex) {
        error_log($ex);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Failed to get tasks");
        $response->send();
        exit();
      }
      


    } elseif($_SERVER['REQUEST_METHOD'] == 'POST') {

    } else {
      $response = new Response();
      $response->setHttpStatusCode(405);
      $response->setSuccess(false);
      $response->addMessage("METHOD Not Allowed");
      $response->send();
      exit();
    }
  }
  else {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint Not Allowed");
    $response->send();
    exit();
  }


