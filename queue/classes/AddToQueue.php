<?php
    class AddToQueue
    {
        // Manages adding things to a queue. D'oh.
        private $pdo;
        private $uuids;
        private $date;
        function __construct(array $uuids, $pdo, $date)
        {
            $this->pdo = $pdo;
            $this->uuids = $uuids;
            $this->date = $date;
        }
        function makeWildcards()
        {
            $wilds = array();
            foreach($this->uuids as $var)
            {
                $wilds[] = "?";
            }
            return $wilds;
        }
        function filterExisting()
        {
            // Filter duplicate entries out of the array.
            $wilds = $this->makeWildcards();
            $wilds = implode(",", $wilds);
            $stmt = "SELECT uuid FROM attach_queue WHERE uuid IN ($wilds)";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute($this->uuids);
                $result = $do->fetchAll(PDO::FETCH_ASSOC);
            }
            catch(PDOException $e)
            {
                throw new Exception($e);
            }
            if($result)
            {
                // If we have existing entries,
                // simply yeet them out of the thing!
                $arr = array();
                foreach($result as $var)
                {
                    $arr[] = $var['uuid'];
                }
                //echo PHP_EOL, "AFTER:", PHP_EOL;
                $this->uuids = array_diff($this->uuids, $arr);
            }
        }
        function insertUuids($future)
        {
            if(empty($this->uuids))
            {
                return false;
            }
            $placeholder = array();
            $vars;
            foreach($this->uuids as $var)
            {
                $placeholder[] = "(?,?)";
                $vars[] = $var;
                $vars[] = $future;
            }
            $placeholder = implode(",", $placeholder);
            $stmt = "INSERT IGNORE INTO attach_queue (`uuid`, `timeout`) VALUES $placeholder";
            $this->pdo->beginTransaction();
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute($vars);
            }
            catch(PDOException $e)
            {
                $this->pdo->rollBack();
                throw new Exception($e);
            }
            $this->pdo->commit();
            return true;
        }
        function getIds()
        {
            // Get the IDs the UUIDs we just inserted.
            if(empty($this->uuids))
            {
                return false;
            }
            $wilds = implode(",", $this->makeWildcards());
            $stmt = "SELECT id FROM attach_queue WHERE uuid IN ($wilds);";
            try
            {
                $do = $this->pdo->prepare($stmt);
                $do->execute($this->uuids);
                $result = $do->fetchall(PDO::FETCH_ASSOC);
            }
            catch(PDOException $e)
            {
                throw new Exception($e);
            }
            if(!$result)
            {
                return false;
            }
            $output;
            foreach($result as $var)
            {
                $output[] = $var['id'];
            }
            return implode(",", $output);
        }
        function add()
        {
            // Add the new entries to the queue.
            // First we filter existing entries out so we don't get duplicates.
            $this->filterExisting();

            // After that, we perform the insert.
            $datetime = $this->date->format("Y-m-d H:i:s");
            // Then add a 2min timeout.
            $this->date->add(new DateInterval("PT2M"));
            $futureDatetime = $this->date->format("Y-m-d H:i:s");

            // With that out of the way, we can finally perform the insert.
            // For the sake of readability and isolation, we're doing this
            // in a separate function.
            if($this->insertUuids($futureDatetime))
            {
                return $this->getIds();
            }
            else
            {
                return false;
            }
        }
    }
?>
