<?php

 class Response {
    private $_success;
    private $_httpStatusCode;
    private $_messages = [];
    private $_data;
    private $_toCache = false;
    private $_responseData = [];

    
    public function setSuccess($success) {
      $this->_success = $success;
    }

    public function setHttpStatusCode($httpStatusCode) {
      $this->_httpStatusCode = $httpStatusCode;
    }

    public function addMessage($message) {
      $this->_messages[] = $message;
    }

    public function setData($data) {
      $this->_data = $data;
    }

    public function toCache($toCache) {
      $this->_toCache = $toCache;
    }

    public function send() {
      
      header('Content-Type: application/json;charset=utf-8');

      if($this->_toCache) {
        header('Cache-control: max-age=60');
      } else {
        header('Cache-control: no-cache, no-store');
      }


      // Error handling
      if(($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode)) {
        http_response_code(500);
        $this->_responseData['statusCode'] = 500;
        $this->_responseData['success'] = false;
        $this->addMessage("Response Creation Error");
        $this->_responseData['messages'] = $this->_messages;
      } else {
        http_response_code($this->_httpStatusCode);
        $this->_responseData['statusCode'] = $this->_httpStatusCode;
        $this->_responseData['success'] = $this->_success;
        $this->_responseData['messages'] = $this->_messages;
        $this->_responseData['data'] = $this->_data;
      }

      echo json_encode($this->_responseData);

    }


    public function successResponse($statusCode, $msg, $data) {
 
      $this->setHttpStatusCode($statusCode);
      $this->setSuccess(true);
      $this->addMessage($msg);
      $this->toCache(true);
      $this->setData($data);
      $this->send();
      //exit();
    }

    public function NoMethodError() {
      $this->setHttpStatusCode(405);
      $this->setSuccess(false);
      $this->addMessage("Method Not Allowd");
      $this->send();
      // exit();
    }

    public function FOFError($msg) {
 
      $this->setHttpStatusCode(400);
      $this->setSuccess(false);
      $this->addMessage($msg);
      $this->send();
      // exit();
    }



 }