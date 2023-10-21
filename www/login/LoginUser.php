<?php
    class LoginUser extends PassEncrypt
    {
        private $username;
        private $password;
        private $pdo;
        private $errorMsg;
        function __construct($usr, $pass, $connection)
        {
            $this->username = $usr;
            $this->password = $pass;
            $this->pdo = $connection;
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        function getError()
        {
            return $this->errorMsg;
        }
        function login()
        {
            // Perform login ops.
            $exists = $this->chkExists();
            if(!$exists)
            {
                $this->errorMsg = "User not found in database.";
                return false; // User does not exist.
            }
            $hashedPass = $this->getPassword();
            if(!$hashedPass)
            {
                return false;
            }
            if(password_verify($this->password, $hashedPass))
            {
                return true;
            }
            else
            {
                $this->errorMsg = "Couldn't log in. Password is incorrect.";
            }
            return false;
        }
        function getPassword()
        {
            $stmt = "SELECT password FROM web_accounts where username = :usr";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->bindParam(':usr', $this->username);
                $do->execute();
            }
            catch(PDOException $e)
            {
                $this->errorMsg = "Could not retrieve password from database.";
                return false;
            }
            $result = $do->fetch(PDO::FETCH_ASSOC);
            if($result)
            {
                return $result['password'];
            }
            return false;
        }
        function chkExists()
        {
            $stmt = "SELECT * FROM web_accounts WHERE username = :usr";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->bindParam(':usr', $this->username);
                $do->execute();
            }
            catch(PDOException $e)
            {
                echo $e->getMessage(), PHP_EOL, PHP_EOL;
                return false;
            }
            $result = $do->fetch(\PDO::FETCH_ASSOC);
            if($result)
            {
                return true;
            }
            return false;
        }
    }
?>
