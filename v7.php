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

class Container
{
    protected static $map = [];

    public static function register($name, $class, $args = null)
    {
        static::$map[$name] = [$class, $args];
    }

    public static function get($name)
    {
        list($class, $args) = isset(static::$map[$name]) ?
                              static::$map[$name] :
                              [$name, null];

        if (class_exists($class, true)) {
            $reflectionClass = new ReflectionClass($class);
            return !empty($args) ?
                   $reflectionClass->newInstanceArgs($args) :
                   new $class();
        }

        return null;
    }

    public static function inject()
    {
        $lists = func_get_args();
        $callback = array_pop($lists);
        $injectArgs = [];

        foreach ($lists as $name) {
            $injectArgs[] = Container::get($name);
        }

        return call_user_func_array($callback, $injectArgs);
    }

    public static function resolve($name)
    {
        if (!class_exists($name, true)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($name);
        $reflectionConstructor = $reflectionClass->getConstructor();
        $reflectionParams = $reflectionConstructor->getParameters();

        $args = [];
        foreach ($reflectionParams as $param) {
            $class = $param->getClass()->getName();
            $args[] = static::get($class);
        }

        return !empty($args) ?
               $reflectionClass->newInstanceArgs($args) :
               new $class();
    }
}

class Session
{
    public function set($name, $value)
    {
        echo "Set session variable '$name' to '$value'.\n";
    }
}

if ('production' !== getenv('APP_ENV')) {
    Container::register('Auth', 'DbAuth', ['mysql://localhost', 'root', '123456']);
} else {
    Container::register('Auth', 'HttpAuth');
}

$app = Container::resolve('App');
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
