<?php
    // Queue class for the attachment system.
    // Utilises other classes for queue management.

    class Queue
    {
        private $vars;
        private $errMsg = "";
        private $date;
        private $pdo;
        private $response;
        function __toString()
        {
            return (string) $this->response;
        }
        function __construct($func, $data, $pdo)
        {
            // Add data we need to process to vars here.
            // Data will be a CSV string that we turn into an array.
            // Depending on the $func var, we will either deal with UUIDs
            // or regular integer IDs.
            // $func will either be "update" or "fetch".
            $this->vars = array(
                "func" => $func,
                "data" => explode(",", str_replace(' ', '', $data))
            );
            $this->pdo = $pdo;
            $this->date = new DateTime();
            $this->date->setTimezone(new DateTimeZone("Europe/Oslo"));
            if(!in_array($this->vars['func'], ["update", "fetch"]))
            {
                $this->errMsg = "Queue Class, Invalid function; must be 'update' or 'fetch'.";
                throw new Exception($this->getError());
            }
            else
            {
                // Check for expired entries.
                $this->checkExpired();
                switch($this->vars['func'])
                {
                    case "update":
                        if($this->allInts())
                        {
                            // Error stuff
                            $this->errMsg = "Queue Class, Invalid data; must be UUIDs for func " . $this->vars['func'] . ".";
                            throw new Exception($this->getError());
                        }
                        $this->response = $this->addToQueue();
                        if(!$this->response)
                        {
                            $this->response = "null";
                        }
                        break;
                    case "fetch":
                        if(!$this->allInts())
                        {
                            // Error stuff
                            $this->errMsg = "Queue Class, Invalid data; must be integer IDs for func " . $this->vars['func'] . ".";
                            throw new Exception($this->getError());
                        }
                        $this->fetchIdFromQueue(); // Then fetch the ID.
                        $this->checkTruncate(); // Check if we can truncate.
                        break;
                }
            }
        }
        function getError()
        {
            return $this->errMsg;
        }
        function allInts() : bool
        {
            // Validate data in the $vars array.
            // Return true if all ints, false otherwise.
            foreach ($this->vars['data'] as $item) 
            {
                if (!is_numeric($item) || !is_int($item + 0)) 
                {
                    return false; // If any element is not an integer, return false immediately.
                }
            }
            return true; // If all elements are integers, return true.
        }
        function truncateQueue()
        {
            $stmt = "TRUNCATE TABLE attach_queue";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute();
            }
            catch(PDOException $e)
            {
                $this->errMsg = "Queue Class, truncateQueue() failure: " . $e->getMessage();
                throw new Exception($this->getError());
            }
        }
        function deleteExpired()
        {
            $datetime = $this->date->format("Y-m-d H:i:s");
            $stmt = "DELETE FROM attach_queue WHERE timeout <= ?";
            $this->pdo->beginTransaction();
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute([$datetime]);
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                $this->errMsg = "Queue Class, deleteExpired() failure: " . $e->getMessage();
                throw new Exception($this->getError());
            }
            $this->pdo->commit();
        }
        function checkExpired()
        {
            // Look for expired queue entries and delete them.
            // Then we also truncate the queue table if it is empty after purging.
            // First get the current datetime.
            // Then get 'id' from attach_queue where timeout is equal to or less than $datetime.
            $stmt = "SELECT id FROM attach_queue";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute();
                $result = $do->fetchAll(PDO::FETCH_ASSOC);
            }
            catch(PDOException $e)
            {
                $this->errMsg = "Queue Class, checkExpired() failure timeout check: " . $e->getMessage();
                throw new Exception($this->getError());
            }
            if(!$result)
            {
                // Truncate if empty.
                $this->truncateQueue();
            }
            else
            {
                // Otherwise deleted the expired entries.
                $this->deleteExpired();
                // Then check if any entries remain at all.
                $stmt = "SELECT id FROM attach_queue";
                try
                {
                    $do = $this->pdo->prepare($stmt);
                    $do->execute();
                    $result = $do->fetchAll(PDO::FETCH_ASSOC);
                }
                catch(PDOException $e)
                {
                    $this->errMsg = "Queue Class, checkExpired() failure: " . $e->getMessage();
                    throw new Exception($this->getError());
                }
                if(!$result)
                {
                    // Then truncate.
                    $this->truncateQueue();
                }
            }
        }
        function addToQueue()
        {
            // Initiate the adder object.
            $addToQueue = new AddToQueue($this->vars['data'], $this->pdo, $this->date);
            // Then perform the addition.
            try
            {
                $id_output = $addToQueue->add();
            }
            catch(Exception $e)
            {
                $this->errMsg = "Queue Class, addToQueue failure: " . $e->getMessage();
                throw new Exception($this->getError());
            }
            return $id_output;
        }
        function checkTruncate()
        {
            // Truncate attach_queue if table is empty.
            $stmt = "SELECT id FROM attach_queue";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute();
                $result = $do->fetchAll(PDO::FETCH_ASSOC);
                if(!$result)
                {
                    $this->truncateQueue();
                }
            }
            catch(PDOException $e)
            {
                $this->errMsg = "Queue Class, checkTruncate() failure: " . $e->getMessage();
                throw new Exception($this->getError());
            }
        }
        function fetchIdFromQueue()
        {
            // Retrieve UUID and other information from the database,
            // including attachment slots, positions, rotations,
            // and more.
            $id = $this->vars['data'][0]; // First element is always the ID in this case.
            if(!is_numeric($id) || !is_int($id + 0))
            {
                $this->errMsg = "Queue Class, fetchIdFromQueue failure: id param is not integer.";
                throw new Exception($this->getError());
            }
            $stmt = "SELECT uuid FROM attach_queue WHERE id = ?";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute([$id]);
                $result = $do->fetch(PDO::FETCH_ASSOC);
                if($result)
                {
                    $this->response = $result['uuid'];
                    // Then add 1 to row attachments, and delete if that total hits 2.
                    $stmt = "UPDATE attach_queue SET attachments = attachments + 1 WHERE id = ?";
                    $do = $this->pdo->prepare($stmt);
                    $do->execute([$id]);
                    $stmt = "SELECT attachments FROM attach_queue WHERE id = ?";
                    $do = $this->pdo->prepare($stmt);
                    $do->execute([$id]);
                    $result = $do->fetch(PDO::FETCH_ASSOC);
                    if($result['attachments'] >= 2)
                    {
                        // Delete row.
                        $stmt = "DELETE FROM attach_queue WHERE id = ?";
                        $do = $this->pdo->prepare($stmt);
                        $do->execute([$id]);
                    }
                }
                else
                {
                    $this->response = "0";
                }
            }
            catch(PDOException $e)
            {
                $this->errMsg = "Queue Class, fetchIdFromQueue failure: " . $e;
                throw new Exception($this->getError());
            }
        }
    }
?>
