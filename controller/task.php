<?php

  require_once('../model/Response.php');
  require_once('../model/Task.php');
  require_once('../helper/sanitize/Task.php');

  // Attempting to connect to DB
  $taskModel = new Task();
  $response = new Response();

  // Predefined vars
  $responseFromModel = [];
  
 
  // Query handling 
  if(array_key_exists("taskid", $_GET)) {

    $task_id = $_GET['taskid'];

    if($task_id == '' || !is_numeric($task_id)) {
      $response->errorResponse(400, "Task ID cannot be blank or must be numeric");
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Get Task Data By ID  
        $responseFromModel = $taskModel->getTaskById($task_id);

        // Return to client
        $returnData = [];
        $returnData['rows_returned'] =  $responseFromModel['rowCount'];
        $returnData['tasks'] =  $responseFromModel['taskArray'];

        $response->successResponse(200, "Response success", $returnData);
  
    } elseif($_SERVER['REQUEST_METHOD'] === 'DELETE') {

      if($taskModel->deleteById($task_id)) {
        $response->successResponse(200, "Deleted successfully", );
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

        $responseFromModel = $taskModel->getTasksByCompletion($completed);

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
      $limitPerPage = 20;

      if(!is_numeric($page) || $page == '') {

        $response->errorResponse(400, "something wrong with the number");
      
      }

      $responseFromModel = $taskModel->readPerPage($page, $limitPerPage);
        
        $returnData = [];
        $returnData['rows_returned'] = $responseFromModel["rowCount"];
        $returnData['total_rows'] = $responseFromModel["totalRows"];
        $returnData['total_pages'] = $responseFromModel["totalPages"];
        $nextPageBool = ($page < $responseFromModel["totalPages"]);
        $returnData['has_next_page'] = $nextPageBool;
        $prevPageBool = ($page > 1);
        $returnData['has_prev_page'] = $prevPageBool;
        $returnData['tasks'] = $responseFromModel["taskArray"];
        
        $response->successResponse(200, "Response success", $returnData);

    } else {
     
      $response->errorResponse(405, "Method Not Allowd");
    }

  }elseif(empty($_GET)) {
    if($_SERVER['REQUEST_METHOD'] == 'GET') {

      $responseFromModel =  $taskModel->getAllTasks();

     

        $returnData = [];
        $returnData['rowCount'] = $responseFromModel["rowCount"];
        $returnData['data'] = $responseFromModel["taskArray"];

        $response->successResponse(200, "Response success", $returnData);
      

    } elseif($_SERVER['REQUEST_METHOD'] == 'POST') {

    } else {
   
      $response->NoMethodError();
    }
  }else {
   
    $response->errorResponse(404, "Endpoint Not Allowed");
  }


