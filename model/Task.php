<?php 

class TaskException extends Exception{

}

class Task {

  public function __construct($id, $title, $description, $deadline, $completed) {
    $this->setId($id);
    $this->setTitle($title);
    $this->setDescription($description);
    $this->setDeadLine($deadline);
    $this->setCompleted($completed);
  }
  
  private $_id;
  private $_title;
  private $_description;
  private $_deadline;
  private $_completed;

  public function setId($id) {

    // Error handling
    if($id !== null && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775897 || $this->_id !== null)) {
      throw new TaskException("Task ID error");
    }

    $this->_id = $id;
  }

  public function setTitle($title) {

    // Error handling
    if(strlen($title) <= 0 || strlen($title) > 255) {
      throw new TaskException("Task Title Error");
    }

    $this->_title = $title;
  }

  public function setDescription($description) {

    // Error handling
    if($description !== null  && strlen($description) > 16777215) {
      throw new TaskException("Task description Error");
    }

    $this->_description = $description;
  }

  public function setDeadLine($deadline) {

    if($deadline !== null && date_format(date_create_from_format('d/m/Y H:i', $deadline), 'd/m/Y H:i') != $deadline) {
      throw new TaskException("Task deadline data time error");
    }

    $this->_deadline = $deadline;
  }

  public function setCompleted($completed) {

    // Error handling
    if(strtoupper($completed) !== 'Y' && strtoupper($completed) !== 'N') {
      throw new TaskException("Task completed Error");
    }

    $this->_completed = $completed;
  }

  public function returnTaskAsArray() {
    $task = array();
    $task['id'] = $this->_id;
    $task['title'] = $this->_title;
    $task['description'] = $this->_description;
    $task['deadline'] = $this->_deadline;
    $task['completed'] = $this->_completed;
    return $task;
  }

  


}