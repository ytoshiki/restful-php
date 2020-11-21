<?php

  require_once('../controller/Database.php');
  require_once('Response.php');

  class Session {

    private $db;

    public function __construct() {
      $this->db = new Database();
      $this->response = new Response();
    

    }

    public function loginandGetToken($id, $accessToken, $refreshToken, $accessToken_expiry, $refreshToken_expiry) {
      try{
        $this->db->begintransaction();
        $this->db->query('UPDATE users SET loginattempts = 0 WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->execute();
  
        $this->db->query('INSERT INTO sessions 
        (user_id, access_token, access_token_expiry, refresh_token ,refresh_token_expiry) 
        VALUES
        (:user_id, :access_token, DATE_ADD(NOW(), INTERVAL :access_token_expiry SECOND), :refresh_token, DATE_ADD(NOW(), INTERVAL :refresh_token_expiry SECOND))
        ');

        $this->db->bind(':user_id', $id);
        $this->db->bind(':access_token', $accessToken);
        $this->db->bind(':access_token_expiry', $accessToken_expiry);
        $this->db->bind(':refresh_token', $refreshToken);
        $this->db->bind(':refresh_token_expiry', $refreshToken_expiry);
        $this->db->execute();  
        $session_id = $this->db->getLastId();
      
        $this->db->commit();

        $backToClient = [];
        $backToClient['session_id'] = intval($session_id);
        $backToClient['access_token'] = $accessToken;
        $backToClient['refresh_token'] = $refreshToken;
        $backToClient['access_token_expires_in'] = $accessToken_expiry;
        $backToClient['refresh_token_expires_in'] = $refreshToken_expiry;
       
        return $backToClient;

      } catch (PDOException $ex) {
        $this->db->rollBack();
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong reseting login attempts");
      }
    
    }

    public function logout($id, $access_token) {
      try {
        
        $this->db->query('DELETE FROM sessions WHERE id = :id AND access_token = :access_token');
        $this->db->bind(':access_token', $access_token);
        $this->db->bind(':id', $id);
        $this->db->execute();
        $rowCount = $this->db->rowCount();

        if($rowCount == 0) {
          $this->response->errorResponse(400, "Failed to log out of this sessions using access token provided");
        }

        $data = [];
        $data['session_id'] = intval($id);
        $this->response->successResponse(200, "Successfully logged out", $data);
        


      } catch (PDOException $ex) {
        
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong loging out attempts");
      }
    }

    public function refreshToken($session_id, $access_token, $refresh_token) {
      try {
    
        $this->db->query('SELECT 
        sessions.id as session_id, 
        sessions.user_id as session_user_id, 
        access_token, refresh_token, access_token_expiry, refresh_token_expiry,useractive, loginattempts 
        FROM users, sessions
        WHERE users.id = sessions.user_id
        AND sessions.id = :session_id 
        AND sessions.access_token = :access_token
        AND sessions.refresh_token = :refresh_token');

        $this->db->bind(':session_id', $session_id);
        $this->db->bind(':access_token', $access_token);
        $this->db->bind(':refresh_token', $refresh_token);
        $this->db->execute();
        $rowCount = $this->db->rowCount();

        if($rowCount == 0) {
          $this->response->errorResponse(401, "Accesstoken or refreshtoken is incorrect");
        }

        $row = $this->db->getResult();

        $returned_session_id = $row["session_id"];
        $returned_session_user_id = $row["session_user_id"];
        $returned_access_token = $row["access_token"];
        $returned_refresh_token = $row["refresh_token"];
        $returned_access_token_expiry = $row["access_token_expiry"];
        $returned_refresh_token_expiry = $row["refresh_token_expiry"];
        $returned_useractive = $row["useractive"];
        $returned_loginattempts = $row["loginattempts"];

        if($returned_useractive !== 'Y') {
          $this->response->errorResponse(401, "User currently not active");
        }

        if($returned_loginattempts >= 3) {
          $this->response->errorResponse(401, "Exceed login attempts limit");
        }
    
        if(strtotime($returned_refresh_token_expiry) < time()) {
          $this->response->errorResponse(401, "token has expired. log in again");
        }

        $new_access_token = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)));
        $new_refresh_token = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)));

        $new_access_token_expiry = 1200;
        $new_refresh_token_expiry = 1209600;

        $this->db->query('UPDATE sessions SET 
        access_token = :access_token,
        refresh_token = :refresh_token, 
        access_token_expiry = DATE_ADD(NOW(), 
        INTERVAL :access_token_expiry SECOND), 
        refresh_token_expiry = DATE_ADD(NOW(), 
        INTERVAL :refresh_token_expiry SECOND)
        WHERE id = :id
        ');

        $this->db->bind(':access_token', $new_access_token);
        $this->db->bind(':refresh_token', $new_refresh_token);
        $this->db->bind(':refresh_token_expiry', $new_refresh_token_expiry);
        $this->db->bind(':access_token_expiry', $new_access_token_expiry);
        $this->db->bind(':id', $session_id);
      
        $this->db->execute();

        $rowCount = $this->db->rowCount();
        if($rowCount < 1) {
          $this->response->errorResponse(401, "Cound not refresh token: try logging in again");
        }

        $backToClient = [];
        $backToClient['session_id'] = intval($session_id);
        $backToClient['access_token'] = $new_access_token;
        $backToClient['refresh_token'] = $new_refresh_token;
        $backToClient['access_token_expires_in'] = $new_access_token_expiry;
        $backToClient['refresh_token_expires_in'] = $new_refresh_token_expiry;
       
        return $backToClient;


      } catch (PDOException $ex) {
        
        $this->response->errorResponse(500, "PDO ERROR: There is something wrong refreshing token" . $ex->getMessage());
      }
    }

    public function checkAuth($access_token) {

      try {
        $this->db->query('SELECT user_id, access_token_expiry FROM sessions, users WHERE users.id = sessions.user_id AND access_token = :access_token');
        $this->db->bind(':access_token', $access_token);
        $this->db->execute();
        $rowCount = $this->db->rowCount();
        if($rowCount < 1) {
          $this->response->errorResponse(401, "Could not get access token provided");
        }
  
        while($row = $this->db->getResult()) {
          $returned_access_token_expiry = $row["access_token_expiry"];
          $returned_user_id = $row["user_id"];
    
        }
  
    
        if(time() > strtotime($returned_access_token_expiry)) {
          $this->response->errorResponse(401, "Access token expired");
        }

        $backToCont = [];
        $backToCont["user_id"] = $returned_user_id;
        return $backToCont;

      } catch (PDOException $ex) {
        $this->response->errorResponse(500, "Query Error" . $ex->getMessage());
      }
     
    }


}