<?php
namespace Src\Models;
use Src\System\DatabaseConnector;
use RobThree\Auth\TwoFactorAuth;

class User {

    private $db;

    public function __construct()
    {
        $this->db = (new DatabaseConnector())->getConnection();
    }

    public function isEmailAlreadyRegistered($email)
    {
        $statement = "
            SELECT 
                id, email, name, is_active, is_administrator, created_at, updated_at
            FROM
                users
            WHERE
                email = :email;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'email' => $email,
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return !empty($result);
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function insert(Array $input)
    {
        $tfa = new TwoFactorAuth();
        $secret = $tfa->createSecret();
        $statement = "
            INSERT INTO users 
                (email, name, password, mfa_secret, is_administrator)
            VALUES
                (:email, :name, crypt(:password, gen_salt('md5')), :secret, :is_administrator);
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'email' => $input['email'],
                'name'  => $input['name'],
                'password' => $input['password'],
                'secret' => $secret,
                'is_administrator' => isset($input['is_administrator']) ? $input['is_administrator'] : true,
            ));
            return $secret;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function listAll()
    {
        $statement = "
            SELECT 
                id, email, name, is_active, is_administrator, mfa_validated_at, created_at, updated_at
            FROM
                users
            WHERE
                is_active
            ORDER BY
                id;
        ";

        try {
            $statement = $this->db->query($statement);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function list($id)
    {
        $statement = "
            SELECT 
                id, email, name, is_active, is_administrator, mfa_validated_at, created_at, updated_at
            FROM
                users
            WHERE 
                is_active
                AND id = :id;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => $id,
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function updateName($id, Array $input)
    {
        $statement = "
            UPDATE 
                users
            SET 
                updated_at = CURRENT_TIMESTAMP,
                name = :name
            WHERE 
                id = :id
            RETURNING
                id, email, name, is_active, is_administrator, mfa_validated_at, created_at, updated_at
            ;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => (int) $id,
                'name' => $input['name'],
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function deactivate($id)
    {
        $statement = "
            UPDATE 
                users
            SET 
                updated_at = CURRENT_TIMESTAMP,
                is_active = false
            WHERE 
                id = :id
            RETURNING
                id, email, name, is_active, is_administrator, mfa_validated_at, created_at, updated_at
            ;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => (int) $id,
            ));
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }
}