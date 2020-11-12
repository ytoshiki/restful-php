<?php 

  require_once('Task.php');
  require_once('Response.php');

  try {

    $task = new Task(4, "", "", "01/01/2019 12:00", "Y");

    header('Content-Type: application/json;charset=utf-8');

    $taskArray = $task->returnTaskAsArray();

    echo json_encode($taskArray);
    

  } catch(TaskException $ex) {
    echo $ex->getMessage();
  }

