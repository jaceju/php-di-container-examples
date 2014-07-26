<?php

class App
{
    protected $auth = null;
    protected $session = null;

    public function __construct(Auth $auth, Session $session)
    {
        $this->auth = $auth;
        $this->session = $session;
    }

    public function login($username, $password)
    {
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

$auth = new Auth('mysql://localhost', 'root', '123456');
$session = new Session();
$app = new App($auth, $session);
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
