<?php

  require_once('../model/Response.php');
  require_once('../model/Task.php');
  require_once('../model/Session.php');
  require_once('../helper/sanitize/Task.php');

  // Attempting to connect to DB
  $taskModel = new Task();
  $response = new Response();
  $sessionModel = new Session();

  // Predefined vars
  $responseFromModel = [];

  // Check Authorizaion Error
  if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
    $response->errorResponse(401, "Access token is missing or something wrong with access token");
  }

  $accessToken = $_SERVER['HTTP_AUTHORIZATION'];

  $returnedFromModel = $sessionModel->checkAuth($accessToken);
  $user_id = $returnedFromModel["user_id"];
  
 
  // Query handling 
  if(array_key_exists("taskid", $_GET)) {

    $task_id = $_GET['taskid'];

    if($task_id == '' || !is_numeric($task_id)) {
      $response->errorResponse(400, "Task ID cannot be blank or must be numeric");
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Get Task Data By ID  
        $responseFromModel = $taskModel->getTaskById($task_id, $user_id);

        // Return to client
        $returnData = [];
        $returnData['rows_returned'] =  $responseFromModel['rowCount'];
        $returnData['tasks'] =  $responseFromModel['taskArray'];

        $response->successResponse(200, "Response success", $returnData);
  
    } elseif($_SERVER['REQUEST_METHOD'] === 'DELETE') {

      if($taskModel->deleteById($task_id, $user_id)) {
        $response->successResponse(200, "Deleted successfully", "" );
      }

    }elseif($_SERVER['REQUEST_METHOD'] === 'PUT') {

      if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        $response->errorResponse(400, "Content Type header in not set to JSON");
      }

      $rawBodyData = file_get_contents('php://input');

      if(!$encodedBodyData = json_decode($rawBodyData, true)) {
        $response->errorResponse(400, "Request Data must be JSON format");
      }

      $checksheet = [];
      $checksheet["title"] = false;
      $checksheet["description"] = false;
      $checksheet["deadline"] = false;
      $checksheet["completed"] = false;
      $updatequery = '';

      if(isset($encodedBodyData["title"])) {
        $checksheet["title"] = true;
        $updatequery .= 'title = :title, ';
      }

      if(isset($encodedBodyData["description"])) {
        $checksheet["description"] = true;
        $updatequery .= 'description = :description, ';
      }

      if(isset($encodedBodyData["deadline"])) {
        $checksheet["deadline"] = true;
        $updatequery .= 'deadline = STR_TO_DATE(:deadline, "%d/%m/%Y %H:%i"), ';
      }

      if(isset($encodedBodyData["completed"])) {
        $checksheet["completed"] = true;
        $updatequery .= 'completed = :completed, ';
      }


      $updatequery = rtrim($updatequery, ', ');

      if($checksheet["title"] == false && $checksheet["description"] == false && $checksheet["deadline"] == false && $checksheet["completed"] == false) {
        $response->errorResponse(400, "No task field provided");
      }
    
       $responseFromModel = $taskModel->updateTask($task_id, $encodedBodyData, $updatequery, $checksheet, $user_id);

   
       $returnData = [];
       $returnData['rows_returned'] =  $responseFromModel['rowCount'];
       $returnData['tasks'] =  $responseFromModel['taskArray'];

       $response->successResponse(200, 'Updated Successfully ', $returnData);
      


    } else {
      $response->NoMethodError();
    }
  
  } elseif(array_key_exists("complete", $_GET)) {
 
    $completed = $_GET['complete'];

    if($completed !== 'Y' && $completed !== 'N') {
     $response->FOFError('Complete must be Y or N');
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET') {

        $responseFromModel = $taskModel->getTasksByCompletion($completed, $user_id);

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
      $limitPerPage = 10;

      if(!is_numeric($page) || $page == '') {

        $response->errorResponse(400, "something wrong with the number");
      
      }

      $responseFromModel = $taskModel->readPerPage($page, $limitPerPage, $user_id);
        
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

      $responseFromModel =  $taskModel->getAllTasks($user_id);

     

        $returnData = [];
        $returnData['rowCount'] = $responseFromModel["rowCount"];
        $returnData['data'] = $responseFromModel["taskArray"];

        $response->successResponse(200, "Response success", $returnData);
      

    } elseif($_SERVER['REQUEST_METHOD'] == 'POST') {

      // Get data
      // 1 - check if the request is JSON format 
      // 2- convert JSON to normal
      if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        $response->errorResponse(400, "Content Type header in not set to JSON");
      }

      // file_get_contents('php://input') gets request body
      $rawPostData = file_get_contents('php://input');

      if(!$jsonData = json_decode($rawPostData, true)) {
        $response->errorResponse(400, "Request body is not JSON");
      }

      if(!isset($jsonData["title"]) || !isset($jsonData["completed"])) {
        $response->errorResponse(400, "Either title and completed is kept black. Both title and complete must be provided");
      }


      $responseFromModel = $taskModel->createTask($jsonData, $user_id);

      // Send data
      $returnData = [];
      $returnData['rowCount'] = $responseFromModel["rowCount"];
      $returnData['data'] = $responseFromModel["taskArray"];
 

      $response->successResponse(200, "Created successfuly", $returnData);
   

    } else {
   
      $response->NoMethodError();
    }
  }else {
   
    $response->errorResponse(404, "Endpoint Not Allowed");
  }


