<?php
 require_once('../model/Response.php');
 require_once('../model/Session.php');
 require_once('../model/User.php');
 
 $sessionModel = new Session();
 $responseModel = new Response();
 $userModel = new User();
 
// sessions POST -create a session/log in
// sessions/3 DELETE -delete a session/log out
// sessions/3 PUT -refresh session

  if(empty($_GET)) {
     if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $responseModel->errorResponse(405, "Method not allowd");
     }

     if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
      $responseModel->errorResponse(400, "Request header must include application/json");
     }

     if(!$requestBody = json_decode(file_get_contents('php://input'))) {
      $responseModel->errorResponse(400, "Request body must be json format");
     }

     if(!isset($requestBody->username) || !isset($requestBody->password)) {
      $responseModel->errorResponse(400, "Request body must include username and password");
     }

     if(strlen($requestBody->username) < 1 || strlen($requestBody->username) > 255) {
      $responseModel->errorResponse(400, "Username must be between 2 and 255 characters");
     }

     if(strlen($requestBody->password) < 6 || strlen($requestBody->password) > 255) {
      $responseModel->errorResponse(400, "Password must be between 7 and 255 characters");
     }

     $username = trim($requestBody->username);
     $password = $requestBody->password;

     // Only if user found, create random token
     $userExist = $userModel->findUser($username, $password);

     if(isset($userExist["id"])) {

        $userId = $userExist["id"];
       
        $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)));

        $refreshToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)));

        $accessToken_expiry = 1200;
        $refreshToken_expiry = 1209600;

        $responseArray = $sessionModel->loginandGetToken($userId, $accessToken, $refreshToken, $accessToken_expiry, $refreshToken_expiry);

        $responseModel->successResponse(201, "successfully logged in", $responseArray);

     } else {
      $responseModel->errorResponse(400, "User Id not Found");
     }



  } elseif(isset($_GET['session_id'])) {

      $session_id = $_GET['session_id'];

      if(empty($session_id) || !is_numeric($session_id)) {
         $responseModel->errorResponse(400, "session id mustnot be blank");
      }

      // get http header auth
      if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
         $responseModel->errorResponse(401, "Access token is missing from the header or Access token must include at least one character");
      }


      $accessToken = $_SERVER['HTTP_AUTHORIZATION'];

      if($_SERVER['REQUEST_METHOD'] == 'DELETE') {

         $sessionModel->logout($session_id, $accessToken);
         
      
      } elseif($_SERVER['REQUEST_METHOD'] == 'PUT') {

         if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
            $responseModel->errorResponse(400, "Request header must include application/json");
         }

         $requestBody_raw = file_get_contents('php://input');
         if(!$requestBody = json_decode($requestBody_raw)) {
            $responseModel->errorResponse(400, "Request body must be json format");
         }

         if(!isset($requestBody->refreshToken) || strlen($requestBody->refreshToken) < 1) {
            $responseModel->errorResponse(400, "Request must include refresh token");
         }

         $refresh_token = $requestBody->refreshToken;

         

         $responseArray = $sessionModel->refreshToken($session_id, $accessToken, $refresh_token);
         $responseModel->successResponse(201, "successfully refreshed Token", $responseArray);


      } else {
         $responseModel->errorResponse(404, "Method Not Allowed");
      }


  } else {
   $responseModel->errorResponse(404, "Endpoint Not Allowed");
  }