<?php
    header('Content-type: text/plain');
    define("debug", false);
    spl_autoload_register(function ($class_name) 
    {
        $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        $file_path = __DIR__ . '/classes/' . $file_name;
        if (file_exists($file_path)) 
        {
            require $file_path;
        }
    });
    if(debug)
    {
        error_reporting(E_ALL);
        ini_set('display_errors',1);
    }
    // Create a global error array we can just use for custom stuff.
    $errArr = array(
        400 => "Bad Request (400): "
    );
    function showError($str)
    {
        global $errArr;
        $statusCode = http_response_code();
        $errorMsg = isset($errArr[$statusCode]) ? $errArr[$statusCode] : "Unknown Error: ";
        return $errorMsg . $str;
    }
    // Now let's get all the headers sent to us.
    $headers = getallheaders();
    // Then we have to confirm that we have the headers we want.
    // We're looking for Second Life headers.
    if(!debug)
    {
        // We'll perform these checks only if we are in debug.
        if(!isset($headers['X-SecondLife-Shard']))
        {
            // If this header is not set, then we stop the program right here.
            http_response_code(400); // 400 = bad request.
            exit(showError("X-SecondLife-Shard header not found. An error has occurred of this request did not happen from Second Life."));

        }
    }

    // For ease of writing, we're going to move $_POST into a new array.
    // If we're in debug mode, use GET instead.
    $vars = debug ? $_GET : $_POST;
    if($vars and !empty($vars))
    {
        if(!require_once('../../../database_2-0.php')) // Require database file when we have POST data.
        {
            http_response_code(500); // 500 = internal server error.
            exit(showError("Database file not found."));
        }
        // Code below here to handle titler things.
        if(debug)
        {
            // If we're in debug mode, we'll just use the username and uuid from the $_GET array.
            $username = $vars['username'];
            $uuid = $vars['uuid'];
        }
        else
        {
            // Otherwise we will get username and uuid from the headers.
            // These are: X-SecondLife-Owner-Name and X-SecondLife-Owner-Key.
            $username = $headers['X-SecondLife-Owner-Name'];
            $uuid = $headers['X-SecondLife-Owner-Key'];
        }
        // At this stage, we can instantiate our database object to pass around, and we'll just use the connect function immediately.
        $db = new Database();
        $db = $db->connect();
        // Now we'll instantiate the user manager and it will handle important things like
        // registration, verification and check to see if the user has previously loaded a character!
        $userManager;
        $userData;
        try
        {
            $userManager = new UserManager($username, $uuid, $db);
            $userData = $userManager->getUserData(); // Fetch userdata for use with the current titler stuff!
        }
        catch(Exception $e)
        {
            $db = null;
            exit(showError($e->getMessage()));
        }
        // Now we'll see what kind of commands we're dealing with.
        /*
            We've got several commands for this project:
                loadCharacter -- loads a specific character, always paired with JSON containing charId and attach_point. Request full reattachment if attach_point changes.
                showSaves -- Shows a paginated 9 save slots per page, always accompanied by page
                roll -- initiates a dice roll, paired with vars dieSize, dieCount
                deleteCharacter -- deletes a character, paired with var charId
                updateCharacter -- updates character data, includes a JSON array with all settings.
        */
        if(!isset($vars['cmd']) or empty($vars['cmd']))
        {
            http_response_code(400); // 400 = bad request.
            exit(showError("No command found."));
        }
    }
    else
    {
        http_response_code(400); // 400 = bad request.
        exit(showError("There is no POST or GET data, if we are in debug."));
    }
?>
