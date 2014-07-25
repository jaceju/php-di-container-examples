<?php

class App
{
    protected $auth = null;
    protected $session = null;

    public function login($username, $password)
    {
        $this->auth = new Auth('mysql://localhost', 'root', '123456');
        $this->session = new Session();
        if ($this->auth->check($username, $password)) {
            $this->session->set('username', $username);
            return true;
        }
        return false;
    }
}

class Auth
{
    public function __construct($dsn, $user, $pass)
    {
        echo "Connecting to '$dsn' with '$user'/'$pass'...\n";
        sleep(1);
    }

    public function check($username, $password)
    {
        echo "Checking username, password from database...\n";
        sleep(1);
        return true;
    }
}

class Session
{
    public function set($name, $value)
    {
        echo "Set session variable '$name' to '$value'.\n";
    }
}

$app = new App();
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
