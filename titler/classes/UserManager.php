<?php
    class UserManager
    {
        private $pdo;
        private $username;
        private $uuid;
        private $errorMsg;
        private $userData;
        private $module;
        private $moduleUri;
        public function __construct($username, $uuid, $db, $module, $moduleUri)
        {
            // Store construct variables into private class vars for use.
            $this->pdo = $db;
            $this->username = $username;
            $this->uuid = $uuid;
            $this->module = $module;
            if($this->module == "titler")
            {
                // Only the titler needs to have a uri.
                $this->moduleUri = $moduleUri;
            }
            // Automatically grab the user data from the database.
            $this->userData = $this->checkAndCreateUser();
            // Make absolutely sure that everything matches!
            if($this->userData['uuid'] != $this->uuid)
            {
                $this->errorMsg = "UUID mismatch! " . $this->userData['uuid'] . " stored in userData for this connection, but connector uuid is " . $this->uuid;
                throw new Exception($this->getErrorMsg());
            }
            if($this->updateUri() and $this->userData['uri_titler'] != $this->moduleUri)
            {
                // If we updated the URI, manually update userData with the new one so we don't
                // have to do ANOTHER database query.
                $this->userData['uri_titler'] = $this->moduleUri;
            }
            // Now we'll create a function that updates their username if it mismatches userData.
            $this->updateUsername();
            if(debug)
            {
                print_r($this->userData);
            }
            echo PHP_EOL, "1";
        }

        function updateUri()
        {
            // Update the URIs connecting database to the RP tools.
            // We just need the titler.
            if($this->module != "titler")
            {
                return false;
            }
            $sql = "";
            if($this->userData['uri_titler'] == $this->moduleUri)
            {
                // Do nothing if we don't need to update anything.
                return false;
            }
            try
            {
                $sql = "UPDATE users SET uri_titler = :uri WHERE uuid = :uuid AND id = :id";
                $query = $this->pdo->prepare($sql);
                $query->bindParam(":uri", $this->moduleUri);
                $query->bindParam(":uuid", $this->userData['uuid']);
                $query->bindParam(":id", $this->userData['id']);
                // Check if we're in an active transaction.
                if($this->pdo->inTransaction())
                {
                    // If we are, just execute the query.
                    $query->execute();
                }
                else
                {
                    // Otherwise, begin a transaction.
                    $this->pdo->beginTransaction();
                    $query->execute();
                    $this->pdo->commit(); // Then commit.
                }
                return true;
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                http_response_code(400);
                $this->errorMsg = "Failure to update uri." . PHP_EOL . $e->getMessage();
                throw new Exception($this->getErrorMsg());
            }
            return false;
        }

        function getUserData()
        {
            return $this->userData;
        }

        function updateUsername()
        {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE users SET username = :username, last_login = :lastlogged WHERE uuid = :uuid");
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":uuid", $this->uuid);
            $fullDate = $this->getFullDate();
            $stmt->bindParam(":lastlogged", $fullDate);
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
                return $result; // Then return it.
            }
            else
            {
                // Otherwise we're going to check them up against the old database.
                $stmt = $this->pdo->prepare("SELECT * FROM rp_tool.users WHERE uuid = :id");
                $stmt->bindParam(":id", $this->uuid);
                try
                {
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                catch(PDOException $e)
                {
                    http_response_code(400);
                    $this->errorMsg = "Failure to check user against old database." . PHP_EOL . $e->getMessage();
                    throw new Exception($this->getErrorMsg());
                }
            }
            $legacy = "0";
            if($result)
            {
                // If result is not empty, then we'll find the legacy ID.
                $legacy = $result['id'];
            }
            // Otherwise we'll create the user.
            $this->pdo->beginTransaction();
            $fullDate = $this->getFullDate();
            $stmt = $this->pdo->prepare("INSERT INTO users (uuid, username, created, legacy) VALUES (:uuid, :username, :creation, :legacy)");
            $stmt->bindParam(":uuid", $this->uuid);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":creation", $fullDate);
            $stmt->bindParam(":legacy", $legacy);
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
