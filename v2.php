<?php

class App
{
    protected $auth = null;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function login($username, $password)
    {
        if ($this->auth->check($username, $password)) {
            return true;
        }
        return false;
    }
}

class Auth
{
    public function check($username, $password)
    {
        echo "Checking username, password from database...\n";
        sleep(1);
        return true;
    }
}

$app = new App(new Auth());
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}