<?php
namespace Src\Controllers;

use Src\Models\User;
use Src\System\DatabaseConnector;

class UserController {

    private $db;

    private $user;
    private $authController;

    public function __construct()
    {
        $this->db = (new DatabaseConnector())->getConnection();

        $this->user = new User();
        $this->authController = new AuthController();
    }

    public function processRequest($route)
    {
        //JSON DATA SENT AS PAYLOAD TO API
        $data = (array) json_decode(file_get_contents('php://input'), true);

        //AUTHORIZATION AND ADMINISTRATOR
        $loggedAuthor = null;
        if (isset($route["middleware"]) && in_array("authenticated", $route["middleware"])) {
            $loggedAuthor = $this->authController->checkToken();
        }
        if (isset($route["middleware"]) && in_array("administrator", $route["middleware"])) {
            if (!$loggedAuthor["is_administrator"]) {
                header("HTTP/1.1 403 Forbidden");
                $json = '"body": {"status": "error", "errors": "The logged user should be an administrator."}';
                echo $json;
                exit();
            }
        }

        switch ($route["action"]["function"]) {
            case "register":
            case "createUser":
                $messages = [];
                if (!$this->validateUserRegister($data, $messages)) {
                    header("HTTP/1.1 422 Unprocessable Entity");
                    $json = '"body": {
                        "status": "error", 
                        "errors": ' . json_encode($messages) . '
                    }';
                    echo $json;
                    exit();
                }
                //INSERT
                $return = $this->user->insert($data);
                header("HTTP/1.1 201 Created");
                $json = '"body": {
                        "status": "OK", 
                        "message": "You need to add the following code to you authenticator app (eg. Google Authenticator): ' . $return . '"
                    }';
                echo $json;
                exit();
                break;
            case "login":
                $messages = [];
                if (!$this->validateUserLogin($data, $messages)) {
                    header("HTTP/1.1 422 Unprocessable Entity");
                    $json = '"body": {
                        "status": "error", 
                        "errors": ' . json_encode($messages) . '
                    }';
                    echo $json;
                    exit();
                }
                //AUTHENTICATE
                $return = $this->authController->authenticate($data);
                if (isset($return["mfa_validated_at"])) {
                    header("HTTP/1.1 200 Authenticated - needs to Multi Factor Authentication");
                    $json = '"body": {
                        "status": "OK", 
                        "message": "You need to send a POST request the /mfa endpoint, with payload json {"email": "' . $return["email"] . '", "mfa_code": "<value from your authenticator app (eg. Google Authenticator)>"} to get the token to be send in header of all requests."
                    }';
                    echo $json;
                    exit();
                    break;
                } else {
                    header("HTTP/1.1 200 Authenticated - needs to add code to an authenticator APP");
                    $json = '"body": {
                        "status": "OK", 
                        "mfa_code": ' . $return["mfa_secret"] . ',
                        "message": "You need to add the following code to you authenticator app (eg. Google Authenticator): ' . $return["mfa_secret"] . '. After it, you need to send a POST request the /mfa endpoint, with payload json {"email": "' . $return["email"] . '", "mfa_code": "<value from your authenticator app>"} to get the token to be send in header of all requests."
                    }';
                    echo $json;
                    exit();
                    break;
                }
                break;
            case "mfa":
                $messages = [];
                if (!$this->validateMFA($data, $messages)) {
                    header("HTTP/1.1 422 Unprocessable Entity");
                    $json = '"body": {
                        "status": "error", 
                        "errors": ' . json_encode($messages) . '
                    }';
                    echo $json;
                    exit();
                }
                //GET MFA_SECRET FROM USER (BY EMAIL)
                $return = $this->authController->checkMFA($data);

                if (!$return) {
                    header("HTTP/1.1 401 Error to check MFA");
                    $json = '"body": {
                        "status": "Error", 
                        "message": "Incorrect MFA Code"
                    }';
                    echo $json;
                    exit();
                    break;
                } else {
                    header("HTTP/1.1 200 OK");
                    $json = '"body": {
                        "status": "OK", 
                        "token": "' . $return . '",
                        "message": "Token generated successfully."
                    }';
                    echo $json;
                    exit();
                    break;
                }
            case "show":
                $id = $this->getParameter($route["parameters"], "id");
                $return = $this->user->list($id);
                if (empty($return)) {
                    header("HTTP/1.1 400 Resouce not found");
                    $json = '"body": {
                        "status": "error", 
                        "error": "Resource not found"
                    }';
                    echo $json;
                    exit();
                    break;
                }

                header("HTTP/1.1 200 OK");
                $json = '"body": {
                    "status": "OK", 
                    "data": ' . json_encode($return) . '
                }';
                echo $json;
                exit();
                break;
            case "listAll":
                $return = $this->user->listAll();

                header("HTTP/1.1 200 OK");
                $json = '"body": {
                    "status": "OK", 
                    "data": ' . json_encode($return) . '
                }';
                echo $json;
                exit();
                break;
            case "updateName":
                $messages = [];
                if (!$this->validateUpdateName($data, $messages)) {
                    header("HTTP/1.1 422 Unprocessable Entity");
                    $json = '"body": {
                        "status": "error", 
                        "errors": ' . json_encode($messages) . '
                    }';
                    echo $json;
                    exit();
                }
                $id = $this->getParameter($route["parameters"], "id");
                if (!(($loggedAuthor["id"] == $id) || $loggedAuthor["is_administrator"])) {
                    header("HTTP/1.1 403 Unauthorized");
                    $json = '"body": {
                        "status": "error", 
                        "message": "Non-admins can not update other users than himself."
                    }';
                    echo $json;
                    exit();
                    break;
                }
                $return = $this->user->updateName($id, $data);

                header("HTTP/1.1 200 OK");
                $json = '"body": {
                    "status": "OK", 
                    "data": ' . json_encode($return) . '
                }';
                echo $json;
                exit();
                break;
            case "deactivate":
                $messages = [];
                $id = $this->getParameter($route["parameters"], "id");
                if ($loggedAuthor["id"] == $id) {
                    header("HTTP/1.1 403 Unauthorized");
                    $json = '"body": {
                        "status": "error", 
                        "message": "Admin can not deactivate himself."
                    }';
                    echo $json;
                    exit();
                    break;
                }
                $return = $this->user->deactivate($id);

                header("HTTP/1.1 200 OK");
                $json = '"body": {
                    "status": "OK", 
                    "data": ' . json_encode($return) . '
                }';
                echo $json;
                exit();
                break;
        }
    }

    private function getParameter($parameters, $search)
    {
        foreach ($parameters as $parameter) {
            if ($parameter["parameter"] == $search) {
                return $parameter["value"];
            }
        }
        header("HTTP/1.1 400 Incorrect parameter");
        $json = '"body" : {"status": "error", "errors": "Invalid parameter"}';
        echo $json;
        exit();
    }

    private function validateUserRegister($data, &$messages = [])
    {
        $messages = [];
        $blnSuccess = true;
        if (! isset($data['email'])) {
            $messages[] = 'The email field is mandatory';
            $blnSuccess = false;
        } else {
            if ($this->user->isEmailAlreadyRegistered($data['email'])) {
                $messages[] = 'The email was already taken';
                $blnSuccess = false;
            }
        }
        if (! isset($data['name'])) {
            $messages[] = 'The name field is mandatory';
            $blnSuccess = false;
        }
        if (! isset($data['password'])) {
            $messages[] = 'The password field is mandatory';
            $blnSuccess = false;
        }
        return $blnSuccess;
    }

    private function validateUserLogin($data, &$messages = [])
    {
        $messages = [];
        $blnSuccess = true;
        if (! isset($data['email'])) {
            $messages[] = 'The email field is mandatory';
            $blnSuccess = false;
        }
        if (! isset($data['password'])) {
            $messages[] = 'The password field is mandatory';
            $blnSuccess = false;
        }
        return $blnSuccess;
    }

    private function validateMFA($data, &$messages = [])
    {
        $messages = [];
        $blnSuccess = true;
        if (! isset($data['email'])) {
            $messages[] = 'The email field is mandatory';
            $blnSuccess = false;
        }
        if (! isset($data['mfa_code'])) {
            $messages[] = 'The MFA Code field is mandatory';
            $blnSuccess = false;
        }
        return $blnSuccess;
    }

    private function validateUpdateName($data, &$messages = [])
    {
        $messages = [];
        $blnSuccess = true;
        if (! isset($data['name'])) {
            $messages[] = 'The name field is mandatory';
            $blnSuccess = false;
        }
        return $blnSuccess;
    }
}