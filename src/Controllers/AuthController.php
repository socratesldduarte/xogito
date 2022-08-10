<?php
namespace Src\Controllers;

use Src\Models\User;
use RobThree\Auth\TwoFactorAuth;
use Src\System\DatabaseConnector;

class AuthController {

    private $db;

    private $user;

    public function __construct()
    {
        $this->db = (new DatabaseConnector())->getConnection();

        $this->user = new User($this->db);
    }

    public function checkToken() {
        $headers = apache_request_headers();
        if (!isset($headers["token"])) {
            header("HTTP/1.1 401 Unauthorized");
            $json = '"body" : {"status": "error", "errors":"You need to login."}';
            echo $json;
            exit();
        }
        $statement = "
            SELECT 
                U.id, U.name, U.email, U.is_active, U.is_administrator, U.created_at, U.updated_at
            FROM
                tokens T
            INNER JOIN
                users U ON U.id = T.user_id
            WHERE 
                token = :token
                AND U.is_active = true
                AND T.expires_at > CURRENT_TIMESTAMP;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'token' => $headers["token"],
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($result)) {
                header("HTTP/1.1 401 Unauthorized");
                $json = '"body" : {"status": "error", "errors":"You need to login."}';
                echo $json;
                exit();
            }
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function authenticate($data)
    {
        $statement = "
            SELECT 
                id, name, email, mfa_secret, mfa_validated_at, is_active, is_administrator, created_at, updated_at
            FROM
                users
            WHERE 
                is_active = true
                AND email = :email
                AND password = crypt(:password, password)
            ;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'email' => $data["email"],
                'password' => $data["password"],
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($result)) {
                header("HTTP/1.1 401 Unauthorized");
                $json = '"body" : {"status": "error", "errors":"Invalid credentials."}';
                echo $json;
                exit();
            }
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function checkMFA($data)
    {
        $email = $data["email"];
        $return = $this->getMfaSecret($email);
        $user_id = $return["id"];
        $mfa_secret = $return["mfa_secret"];

        $tfa = new TwoFactorAuth();
        $result = $tfa->verifyCode($mfa_secret, $data["mfa_code"]);
        if (!$result) {
            return $result;
        } else {
            //UPDATE mfa_validated_at IF NULL
            $statement = "
                UPDATE
                    users
                SET
                    mfa_validated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id
                ;
            ";
            try {
                $statement = $this->db->prepare($statement);
                $statement->execute(array(
                    'user_id' => $user_id,
                ));
            } catch (\PDOException $e) {
                exit($e->getMessage());
            }

            $token = md5($email . $user_id . date("Ymdhis"));
            //ADD TOKEN
            $statement = "
                INSERT INTO
                    tokens
                (
                    user_id,
                    token
                )
                VALUES
                (
                    :user_id,
                    :token
                )
                ;
            ";

            try {
                $statement = $this->db->prepare($statement);
                $statement->execute(array(
                    'user_id' => $user_id,
                    'token' => $token,
                ));
                return $token;
            } catch (\PDOException $e) {
                exit($e->getMessage());
            }
        }
    }

    public function getMfaSecret($email)
    {
        $statement = "
            SELECT
                id,
                mfa_secret
            FROM
                users
            WHERE 
                email = :email
            ;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'email' => $email,
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($result)) {
                header("HTTP/1.1 401 Unauthorized");
                $json = '"body" : {"status": "error", "errors":"Invalid credentials."}';
                echo $json;
                exit();
            }
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }
}