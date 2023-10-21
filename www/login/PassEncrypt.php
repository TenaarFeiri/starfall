<?php
    class PassEncrypt
    {
        // Encryptor class for passwords.
        // Also decrypts passwords.
        // Combine with 16-letter decoder key from password to decrypt.
        private $cipher = "";
        function __construct()
        {
            // If we need to construct something here, do so.
            // But it probably isn't necessary.
            echo "PassEncrypt also loaded.";
        }

        function verifyPass($username, $password, $hash)
        {
            return password_verify($password, $hash);
        }

        function encryptPass($password)
        {
            $options = [
                'cost' => 14,
            ];
            return password_hash($password, PASSWORD_BCRYPT, $options);
        }


    }
?>
