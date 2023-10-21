<?php
    class CreateUser extends PassEncrypt
    {
        private $username;
        private $password;
        private $pdo;
        private $errorMsg;
        function __construct($username, $password, $pdo)
        {
            $this->username = $username;
            $this->password = $password;
            $this->pdo = $pdo;
        }
        function getError()
        {
            return $this->errorMsg;
        }
        function addUser()
        {
            $exists = $this->chkExists();
            if($exists)
            {
                $this->errorMsg = "User already exists.";
                return false;
            }
            $this->password = parent::encryptPass($this->password);
            $stmt = "INSERT INTO web_accounts (username,password) VALUES (:usr, :pass)";
            try
            {
                $this->pdo->beginTransaction();
                $do = $this->pdo->prepare($stmt);
                $do->bindParam(':usr', $this->username);
                $do->bindParam(':pass', $this->password);
                $do->execute();
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                $this->errorMsg = $e->getMessage();
                return false;
            }
            $exists = $this->chkExists();
            if($exists)
            {
                $this->pdo->commit();
                return true;
            }
            $this->pdo->rollBack();
            $this->errorMsg = "Could not register due to unknown error.";
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
