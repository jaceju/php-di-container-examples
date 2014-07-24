<?php

class App
{
    protected $auth = null;

    public function login($username, $password)
    {
        $this->auth = new Auth();
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

$app = new App();
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
