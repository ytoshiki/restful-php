<?php

require_once('../model/Response.php');
require_once('../model/User.php');

$userModel = new User();
$responseModel = new Response();


// Request method
if($_SERVER["REQUEST_METHOD"] !== 'POST') {
  $responseModel->errorResponse(405, "Request method not allowed");
}

// Request header
if($_SERVER["CONTENT_TYPE"] !== 'application/json') {
  $responseModel->errorResponse(400, "Request header not set to app/json");
}

// JSON to obj
if(!$requestBody = json_decode(file_get_contents('php://input'))) {
  $responseModel->errorResponse(400, "Request body must be json format");
}

// Mandatory field
if(!isset($requestBody->fullname) || !isset($requestBody->username) || !isset($requestBody->password)) {

  $msg = '';
  isset($requestBody->fullname) ? $msg .= "" : $msg .= " fullname";
  isset($requestBody->username) ? $msg .= "" : $msg .= " username";
  isset($requestBody->password) ? $msg .= "" : $msg .= " password";
  $msg = ltrim($msg, ' ');
  $msg .= " is(are) not provided";

  $responseModel->errorResponse(400, $msg);
}

// Check input length
$error_msg = [];
$error_msg['fullname'] = '';
$error_msg['username'] = '';
$error_msg['password'] = '';
if(strlen($requestBody->fullname) < 1 || strlen($requestBody->fullname) > 255) {
  $error_msg['fullname'] = 'fullname must be between 2 and 255';
} 

if(strlen($requestBody->username) < 1 || strlen($requestBody->username) > 255) {
  $error_msg['username'] = 'username must be between 2 and 255';
} 

if(strlen($requestBody->password) < 6 || strlen($requestBody->password) > 255) {
  $error_msg['password'] = 'password must be between 6 and 255';
} 

if(strlen($error_msg['fullname']) > 0 || strlen($error_msg['username']) > 0 || strlen($error_msg['password']) > 0) {
  $responseModel->errorResponse(400, $error_msg);
}

// trim whitespace
$fullname = trim($requestBody->fullname);
$username = trim($requestBody->username);
$password = $requestBody->password;

// return true if no username exists yet
$sameUsernameTaken = $userModel->checkUserOverlap($username);

if(!$sameUsernameTaken) {
  $responseModel->errorResponse(409, "Username already exists");
}

// hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$newUser = $userModel->createUser($fullname, $username, $hashed_password);

$responseModel->successResponse(201, "User successfully created", $newUser);



