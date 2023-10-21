<html>
<head>

</head>
<body>
    <center>
        <p>Test</p>
    </center>
    <br />
    <br />
    <div id="loginfield">
        <form action="" method="post">
            <p>Register user</p>
            <p>User: <input type="text" placeholder="Username" name="username" required></p>
            <p>Password: <input type="password" placeholder="Password" name="password" required></p>
            <p><button type="submit">Register</button></p>
        </form>
        <form action="" method="get">
            <p>Not registering? Login here: 
            <input type="hidden" id="func" name="func" value="login">
            <button type="submit">Go to login</button></p>
        </form>
    </div>
</body>
</html>