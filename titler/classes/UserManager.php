<?php
    class UserManager
    {
        private $pdo;
        private $username;
        private $uuid;
        private $errorMsg;
        private $userData;
        public function __construct($username, $uuid, $db)
        {
            // Store construct variables into private class vars for use.
            $this->pdo = $db;
            $this->username = $username;
            $this->uuid = $uuid;

            // Automatically grab the user data from the database.
            $this->userData = $this->checkAndCreateUser();

            // Now we'll create a function that updates their username if it mismatches userData.
            $this->updateUsername();
        }

        function getUserData()
        {
            return $this->userData;
        }

        function updateUsername()
        {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE users SET username = :username WHERE uuid = :uuid");
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":uuid", $this->uuid);
            try
            {
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                http_response_code(400);
                $this->errorMsg = "Failure to update username." . PHP_EOL . $e->getMessage();
                throw new Exception($this->getErrorMsg());
            }
            // If we made it here, commit.
            $this->pdo->commit();
            return true;
        }

        function getErrorMsg()
        {
            // For if we ever need to get the error message from this class into any other class.
            if (!$this->errorMsg) {
                return false;
            }
            return $this->errorMsg;
        }

        // Create a function that returns the full date, hour, minute and second, European format, in the PST timezone.
        function getFullDate()
        {
            date_default_timezone_set("America/Los_Angeles");
            $date = date("d-m-Y H:i:s");
            return $date;
        }

        // Next we need to confirm whether or not the user already exists.
        // If not, we're going to create them.
        function checkAndCreateUser()
        {
            // Check to see if the user already exists.
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE uuid = :uuid");
            $stmt->bindParam(":uuid", $this->uuid);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                // If the user exists, we'll just return the user's data.
                return $result;
            }
            // Otherwise we'll create the user.
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO users (uuid, username, created) VALUES (:uuid, :username, :creation)");
            $stmt->bindParam(":uuid", $this->uuid);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":creation", $this->getFullDate());
            try
            {
                $stmt->execute();
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                http_response_code(400);
                $this->errorMsg = "Failure to create user." . PHP_EOL . $e->getMessage();
                throw new Exception($this->getErrorMsg());
            }
            // If we made it here, commit.
            $this->pdo->commit();
            // Return the newly created user.
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE uuid = :uuid");
            $stmt->bindParam(":uuid", $this->uuid);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // If no result, something went wrong and we're killing the script.
            if (!$result) {
                http_response_code(400);
                $this->errorMsg = "Failure to create user after successful insert." . PHP_EOL . "No result returned.";
                throw new Exception($this->getErrorMsg());
            }
            return $result;
        }
    }
?>
