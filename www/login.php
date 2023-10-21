<?php
    header('Content-type: text/html');
    spl_autoload_register(function ($class_name) 
    {
        $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        $file_path = __DIR__ . '/login/' . $file_name;
        if (file_exists($file_path)) 
        {
            require $file_path;
        }
    });
    ini_set('display_errors',1);
    if($_POST and !empty($_POST))
    {
        require_once('../../database.php');
        if(!$_GET or empty($_GET) or !isset($_GET['func']) or empty($_GET['func']) or $_GET['func'] == "login")
        {
            // Perform login features.
            $db = new Database(); // Open up a new database connection, out of scope, naturally.
            $connection = $db->connect(); // PDO object.
            // Create a new $login object, move the PDO reference to it.
            $login = new LoginUser($_POST['username'], $_POST['password'], $connection);
            $confirmed = $login->login();
            if(!$confirmed)
            {
                echo "Could not log in! Error: ", $login->getError(), PHP_EOL;
                $login = null;
                $db = null;
                $connection = null;
                include_once('login/html/login.php');
            }
            else
            {
                echo "WOULD HAVE BEEN A SUCCESSFUL LOGIN!<br />";
                include_once('login/html/login.php');
            }
        }
        else if($_GET['func'] == "register")
        {
            // Handle registration here.
            $db = new Database();
            $connection = $db->connect();
            $create = new CreateUser($_POST['username'], $_POST['password'], $connection);
            $confirmed = $create->addUser();
            if(!$confirmed)
            {
                echo "Could not register user. Error: ", $create->getError(), PHP_EOL;
                $connection = null;
                $db = null;
                $create = null;
                include_once('login/html/login.php');
            }
            else
            {
                echo "You have now been registered. Please log in!<br />";
                include_once('login/html/login.php');
            }
        }
    }
    else
    {
        // If there's no POST then we include the HTML code from login/html.
        if(!$_GET or empty($_GET) or !isset($_GET['func']) or empty($_GET['func']) or $_GET['func'] == "login")
        {
            include_once('login/html/login.php');
        }
        else if($_GET['func'] == "register")
        {
            include_once('login/html/register.php');
        }
    }
?>