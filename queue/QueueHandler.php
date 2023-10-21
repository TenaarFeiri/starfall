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
        ///////
        // Here we need an overview of POST commands from the script.
        // The QueueHandler will receive POST from both the RP tool and the dispenser so we'll have to
        // sort out which.
        //
        // POST cmd:
        //              - { "src" => "dispenser" }
        //              - { "src" => "rptool" } (for both titler and HUD.)
        ///////
        // We'll deal with the dispenser first.
        $database = new Database();
        if(!$vars['src'] or empty($vars['src']))
        {
            // If src is empty, it's a bad request.
            http_response_code(400); // 400 = bad request.
            exit(showError("The 'src' parameter is missing or invalid (empty)."));
        }
        // If we made it here, we have a source.
        if($vars['src'] === "dispenser")
        {
            // We'll check for whether there is a data var in the array.
            if(!isset($vars['data']) or !$vars['data'] or empty($vars['data']))
            {
                http_response_code(400);
                exit(showError("The 'data' parameter is missing or invalid (empty) for " . $vars['src'] . "."));
            }
            $queue;
            try
            {
                $queue = new Queue($vars['func'], $vars['data'], $database->connect());
                echo $queue;
            }
            catch(Exception $e)
            {
                http_response_code(400);
                exit(showError($e->getMessage()));
            }
        }
        else if($vars['src'] === "rptool" and $vars['func'] === "fetch")
        {
            // We just need to know the IDs here.
            if(!isset($vars['id']) or !$vars['id'] or empty($vars['id']))
            {
                http_response_code(400);
                exit(showError("The 'id' parameter is missing or invalid (empty) for " . $vars['src'] . "."));
            }
            // Validate that we are dealing with a whole integer, and not multiple values.
            if(!is_numeric($vars['id']))
            {
                http_response_code(400);
                exit(showError("The 'id' parameter is not a whole number."));
            }
            $queue;
            try
            {
                $queue = new Queue($vars['func'], $vars['id'], $database->connect());
                echo $queue; // __toString() will return the queue result.
            }
            catch(Exception $e)
            {
                http_response_code(400);
                exit(showError($e->getMessage()));
            }
        }
        else
        {
            http_response_code(400);
            exit();
        }
    }
    else
    {
        http_response_code(400);
        exit(showError("There is no POST or GET data, if we are in debug."));
    }
