<?php
    // Handles character management like loading, saving, titlers, updates, etc.
    // Dice roll class will inherit the character handler.
    class CharacterManager
    {
        private $userData;
        private $pdo;
        private $errorMsg;
        public function __construct(array $usrData, $db)
        {
            $this->userData = $usrData;
            $this->pdo = $db;
            // Standby until instructions are received.
        }

        function getErrorMsg()
        {
            // Just in case, for whatever reason, we need to get the error message here specifically
            // even though under normal circumstances we wouldn't need to do that.
            // We'll use this function internally anyway.
            return $this->errorMsg;
        }

        function checkLastLoaded()
        {
            // Look for the last loaded character (lastchar). Create one, if 0.
            // Get that from our userData.
            $lastchar = $this->userData['lastchar'];
            if($lastchar == 0)
            {
                // Check legacy account for the last character they loaded there.
                $legacyLast = $this->findLastLegacyCharacterId();
                if(!$legacyLast)
                {
                    // Create a new character if everything is false.

                }
                else
                {
                    // Otherwise import $legacyLast from rp_tool.rp_tool_character_repository using
                    // the dedicated legay import function. This will also convert the old character data into
                    // the new format.
                    $importedCharacter = $this->importLegacyCharacter($legacyLast);
                    if(!$importedCharacter)
                    {
                        // If this fails, spit an error.
                        http_response_code(400);
                        $this->errorMsg = "Failed to import legacy character id $legacyLast.";
                        throw new Exception($this->errorMsg);
                    }
                    else
                    {
                        // $imported character will be a JSON list.
                        // print_r in debug mode.
                        return $importedCharacter;
                    }
                }
            }
            else
            {
                // Otherwise code to load a character here. Either way, we're doing a return!
            }
        }

        function importLegacyCharacter($legacyId)
        {
            $sql = "SELECT * FROM rp_tool.rp_tool_character_repository WHERE character_id = :id AND user_id = :usr";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":id", $legacyId);
            $stmt->bindParam(":usr", $this->userData['legacy']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$result)
            {
                return false;
            }
            $lastCharData = $result; // Store the char data here.
            // And now we will import this character. First ensure that this character wasn't previously imported.
            $sql = "SELECT * FROM characters WHERE legacy_id = :legId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(":legId", $legacyId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if($result)
            {
                // Remove legacy ID from the $result.
                unset($result['legacy_id']);
                // Then parse the array into a JSON string.
                $result = json_encode($result);
                return $result;
            }
            // Time to format data.
            // We will format into name, and titler.
            if(debug)
            {
                print_r($lastCharData);
            }
            $constants = $lastCharData['constants'];
            $titles = $lastCharData['titles'];
            $constants = explode("=>", $constants);
            $titles = explode("=>", $titles);
            // Create a new array that appends the value of $titles[] to the value of its equivalent $constants[], ending in newlines.
            $compiledArray = array();
            $x = 0;
            foreach($constants as $value)
            {
                $compiledArray[] = str_replace("@invis@", "", $value) . " " . $titles[$x] . "\n";
                ++$x;
            }
            if(debug)
            {
                print_r($compiledArray);
            }
            $name = $compiledArray[0];
            // Find any "$p" symbols in the $name variable, and remove them + one character.
            $name = str_replace(['$p', '$n'], "", $name);
            // Trim the leading and trailing whitespace from $name.
            $name = trim($name);
            // Then dump $compiledArray into a regular string from index 1 to end.
            $titler = implode("", array_slice($compiledArray, 1));
            $titler = str_replace(['$p', '$n'], "", $titler);
            if(debug)
            {
                echo PHP_EOL, $name;
                echo PHP_EOL, $titler;
            }
            // FINALLY we can perform the insert!
            $sql = "INSERT INTO characters (name, titler, legacy_id, owner) VALUES (:name, :titler, :legId, :usrId)";
            $stmt = $this->pdo->prepare($sql);
            // Begin transaction.
            try
            {
                $this->pdo->beginTransaction();
                // Bind parameters.
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":titler", $titler);
                $stmt->bindParam(":legId", $legacyId);
                $stmt->bindParam(":usrId", $this->userData['id']);
                // Then execute!
                $stmt->execute();
                // Then get the last inserted; we'll use legacy id, owner and $name to confirm.
                $sql = "SELECT * FROM characters WHERE legacy_id = :legId AND owner = :usrId AND name = :name";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(":legId", $legacyId);
                $stmt->bindParam(":usrId", $this->userData['id']);
                $stmt->bindParam(":name", $name);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if($result)
                {
                    // Commit if we have a result.
                    $this->pdo->commit();
                }
                else
                {
                    $this->pdo->rollBack();
                    http_response_code(400);
                    $this->errorMsg = "Failed to import legacy character $legacyId at commit! This is a forced error and happens when insert gives no errors, but new character cannot be found.";
                    throw $this->getErrorMsg();
                }
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                http_response_code(400);
                $this->errorMsg = "Failed to import legacy character $legacyId at insertion: " . $e;
                throw $this->getErrorMsg();
            }
            return $result;
        }

        function findLastLegacyCharacterId()
        {
            // Find the last loaded character off a legacy account.
            $stmt = $this->pdo->prepare("SELECT * FROM rp_tool.users WHERE id = :id");
            $stmt->bindParam(":id", $this->userData['legacy']); // Bind the legacy id.
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result as an associative array.
            if(!$result)
            {
                return false;
            }
            else
            {
                if($result['lastchar'] == 0)
                {
                    // If no last character, then we're just gonna return false.
                    return false;
                }
                else
                {
                    return $result['lastchar']; // Otherwise return the ID!
                }
            }
            return false; // If we get here, something went wrong, and we'll just return false.
        }

        function createNewCharacter()
        {
            // Create a brand new character!
            // Default JSON list:
            /*
                {
                    "settings": {
                        "colour": "255,255,255",
                        "attach_point": "ATTACH_HEAD",
                        "pos": "<0.00000,0.00000,0.18143>"
                    }

                }
            */
            // Just in case we're forced to generate it manually, though we shouldn't.
        }

        function loadCharacter($id)
        {
            // Load a character with $id.
        }
    }
?>
