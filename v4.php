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

interface Auth
{
    public function check($username, $password);
}

class DbAuth implements Auth
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

class HttpAuth implements Auth
{
    public function check($username, $password)
    {
        echo "Checking username, password from HTTP Authentication...\n";
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

$app = new App(new DbAuth('mysql://localhost', 'root', '123456'), new Session());
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
