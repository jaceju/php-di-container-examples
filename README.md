# 理解 Dependency Injection 實作原理

現代較新的 Web Framework 都強調自己有 Dependency Injection (以下簡稱 DI ) 的特色，只是很多人對它的運作原理還是一知半解。

所以接下來我將用一個簡單的範例，來為各位介紹在 PHP 中如何實現簡易的 DI 。

<!--more-->

## 基本範例

這是一個應用程式的範例，它只包含了登入處理程序。在這個範例中， `App` 類別的建構式參考了新的 `Auth` 與 `Session` 的物件實體，並在 `App::login()` 中使用。

註：請特別注意，為了呈現重點，我忽略掉很多程式碼，同時也沒有進行良好的架構設計；所以請不要把這個範例用在你的程式中，或是對為什麼我沒有進行錯誤處理，以及為什麼要採用奇怪的設計提出質疑。

```php
<?php
class App
{
    protected $auth = null;
    protected $session = null;

    public function __construct($dsn, $username, $password)
    {
        $this->auth = new Auth($dsn, $username, $password);
        $this->session = new Session();
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
?>
```

而 `Auth` 類別是從資料庫驗證使用者身份，這裡我僅用簡單的描述來呈現效果。

```php
<?php
class Auth
{
    public function __construct($dsn, $user, $pass)
    {
        echo "Connecting to '$dsn' with '$user'/'$pass'...\n";
    }

    public function check($username, $password)
    {
        echo "Checking username, password from database...\n";
        return true;
    }
}
?>
```

`Session` 類別也是概念性的實作：

```php
<?php
class Session
{
    public function set($name, $value)
    {
        echo "Set session variable '$name' to '$value'.";
    }
}
?>
```

最後我們讓程式動起來， client 程式如下：

```php
<?php
$app = new App('mysql://localhost', 'root', '123456');
$username = 'jaceju';
if ($app->login($username, 'password')) {
    echo "$username just signed in.\n";
}
?>
```

註：這裡的 client 程式指的是實際操作這些物件實體的程式。

各位可以先試著想想這個程式在可擴充性上有什麼問題？例如我想把身份認證方式換成第三方服務的機制，或是改用其他媒介來存放 session 內容等。

還有如果想在沒有資料庫連線、或是沒有 HTTP session 的環境下對 `App::login()` 方法的邏輯進行隔離測試，各位會怎麼做呢？

## 解除依賴關係

上面的範例因為 `App` 類別已經依賴了 `Auth` 類別和 `Session` 類別，而這兩個類別都有實作跟系統環境有關的程式邏輯，這麼一來就會讓 `App` 類別難以進行底層機制的切換或是隔離測試。所以接下來我們要做的，就是把它們的依賴關係解除。

修改後的 `App` 類別如下：

```php
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
}

$auth = new Auth('mysql://localhost', 'root', '123456');
$session = new Session();
$app = new App($auth, $session);
?>
```

首先我們在 `App` 類別的建構式 `__construct` 原本的資料庫設定參數移除，並將原來直接以 `new` 關鍵字所產生的物件實體，改用方法參數的方式來注入。而使用 `new` 關鍵字產生物件實體的程式碼，就移到 `App` 類別外。

這種「將依賴的類別改用方法參數來注入」的作法，就是我們說的「依賴注入 (Dependency Injection) 」。

依賴注入常見的方式有兩種： Constructor Injection 及 Setter Injection 。它們的實作形式並沒有什麼不同，差別只在於是不是類別建構式而已。

不過 Constructor Injection 必須在建立物件實體時就進行注入，而 Setter Injection 則是可以在物件實體建立後才透過 setter 函式來進行注入。而這裡為了方便解說，我採用的是 Constructor Injection 。

## 依賴抽象介面

好了，現在的問題是 `Auth` 類別的實作還是依賴在資料庫上，所以我們也要讓 `Auth` 類別跟資料庫之間解除依賴關係，讓它成為一個抽象介面。這裡的抽象介面是指觀念上的意義，而非語言層級上的抽象類別 (Abstract Class) 或介面 (Interface) 。至於在實作上該用抽象類別還是介面，在這個範例裡並沒有差別，大家可以自行判斷；這裡我用介面 (Interface) ，因為我僅需要 `check` 這個介面方法的定義而已。

這一步首先我把原來的 `Auth` 類別重新命名為 `DbAuth` 類別：

```php
<?php
class DbAuth
{
    public function __construct($dsn, $user, $pass)
    {
        echo "Connecting to '$dsn' with '$user'/'$pass'...\n";
    }

    public function check($username, $password)
    {
        echo "Checking username, password from database...\n";
        return true;
    }
}
?>
```

接著建立一個 `Auth` 介面，它包含了 `check` 方法的定義：

```php
<?php
interface Auth
{
    public function check($username, $password);
}
?>
```

然後讓 `DbAuth` 類別實作 `Auth` 介面。

```php
<?
class DbAuth implements Auth
{
    // ...
}
?>
```

最後把原來初始化 `Auth` 類別的物件實體的程式碼，改為初始化 `DbAuth` 的物件實體。

```php
<?php
$auth = new DbAuth('mysql://localhost', 'root', '123456');
$session = new Session();
$app = new App($auth, $session);
?>
```

透過 `Auth` 介面的幫助，我們已經讓 `App` 類別與實際的資料庫操作類別分離開來了。現在只要是實作 `Auth` 介面的類別，都可以被 `App` 類別所接受，例如我們可能會改用 HTTP 認證來取代資料庫認證：

```php
<?php
class HttpAuth implements Auth
{
    public function check($username, $password)
    {
        echo "Checking username, password from HTTP Authentication...\n";
        return true;
    }
}

$auth = new HttpAuth();
$session = new Session();
$app = new App($auth, $session);
?>
```

當然其他類型的認證方式也可以透過建立新的類別來使用，而不會影響到 `App` 類別的內部實作。

## DI 容器

現在又有個問題， client 程式還是依賴於 `DbAuth` 類別或是 `HttpAuth` 類別；通常這種狀況在需要編譯型的語言 (例如 Java ) 中，程式一旦編譯完成佈署出去後，就很難再進行修改。

如果我們可以改用設定的方式來告訴程式，在不同的狀況下對應不同的類別，然後讓程式自行判斷環境來產生需要的物件實體，這樣就可以解開 client 程式對實作類別的依賴關係。

這裡要引入一個技術，稱為 DI 容器 (Dependency Injection Container) 。 DI 容器主要的作用在於幫我們解決產生物件實體時，應該參考哪一個類別。我們先來看看用法：

```php
<?php
Container::register('Auth', 'DbAuth', ['mysql://localhost', 'root', '123456']);

$auth = Container::get('Auth');
$session = new Session();
$app = new App($auth, $session);
?>

首先我們在 DI 容器中先以 `Container::register()` 方法來註冊 `Auth` 這個別名實際上要對應哪個類別，以及建立物件實體時會用到的初始化參數。要注意，這裡的別名並不是指真正的類別或介面，但我們可以用相同的名稱以避免邏輯上的問題。

然後我們用 `Container::get()` 方法取得別名所對應類別的物件實體，上面例子裡的 `$auth` 就是 `DbAuth` 類別的物件實體。

這麼一來，我們就可以把註冊的程式碼移出 client 程式之外，並將註冊參數改用設定檔引入，順利解開 client 程式對實作類別的依賴。

## DI 容器原理

那麼 DI 容器的原理是怎麼運作的呢？首先在 `Container::register()` 方法註冊的部份，它其實只是把參數記到 `$map` 這個類別靜態屬性裡。

```php
<?php
class Container
{
    protected static $map = [];

    public static function register($name, $class, $args = null)
    {
        static::$map[$name] = [$class, $args];
    }

    // ...
}
?>
```

重點在 `Container::get()` 方法，它透過 `$name` 別名，把 `$map` 屬性中對應的類別名稱和初始化參數取出；接著判斷類別是不是存在，如果存在的話就建立對應的物件實體。

```php
<?php
class Container
{
    // ...

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
}
?>
```

比較特別的是，如果初始化參數不是空值 (`null`) 時，則必須透過 `ReflectionClass::newInstanceArgs()` 方法來建立物件實體。 `ReflectionClass` 類別可以映射出指定類別的內部結構，並提供方法來操作這個結構； Reflection 是現代語言常見的機制， PHP 在這方面也提供了完整的 API 供開發者使用，請參考： [PHP: Reflection](http://php.net/manual/en/book.reflection.php) 。

`Container::get()` 方法也可以在沒有註冊的狀況下，直接把別名當成類別名稱，然後協助我們初始化對應的物件實體；例如：

```php
<?php
$session = Container::get('Session');
?>
```

## 手動注入

現在我們的 client 程式已經修改成以下的樣子：

```php
<?php
$auth = Container::get('Auth');
$session = Container::get('Session');
$app = new App($auth, $session);
?>
```

不過當初始化參數較多的狀況下，重複寫 `Container::get()` 看起來也是挺囉嗦的。

接下來我們實作一個 `Container::inject()` 方法，提供開發者可以一次注入所有依賴物件實體：

```php
<?php
$app = Container::inject('Auth', 'Session', function ($auth, $session) {
    return new App($auth, $session);
});
?>
```

這裡我們讓 `Container::inject()` 接受不定個數的參數，除了最後一個參數必須是 callback 型態外，其他都是要傳遞給 `Container::get()` 的參數。 `Container::inject()` 的實作方式如下：

```php
<?php
class Container
{
    // ...

    public static function inject()
    {
        $args = func_get_args();
        $callback = array_pop($args);
        $injectArgs = [];

        foreach ($args as $name) {
            $injectArgs[] = Container::get($name);
        }

        return call_user_func_array($callback, $injectArgs);
    }
?>
```

在參數個數不定的狀況下，可以用 `func_get_args()` 函式來取得所有參數；而 `array_pop()` 可以取出最後一個參數值做為 callback 。剩下的參數就透過 `Container::get()` 來取得物件實體，最後再透過 `call_user_func_array()` 函式將處理好的參數傳遞給 callback 執行。

## 自動解決所有依賴注入

如果在我們的範例裡， `Container` 類別如果可以提供一個方法，自動為我們解決所有 `App` 類別依賴問題，那麼程式就可以更乾淨些。要做到這點，我們就必須知道要注入的方法，它所需要參數的類型。

回到 `App::__construct()` 建構子上：

```php
<?php
class App
{
    public function __construct(Auth $auth, Session $session)
    {
    }
}
?>
```

我們為 `$auth` 與 `$session` 兩個參數都寫上了它們的 type hint ，剛好就可以用來當做我們做自動依賴注入的條件。

接著我們為 `Container` 類別提供一個 `resolve()` 方法，它可以接受一個類別名稱用來建立物件實體，並自動產生參數所對應的物件，解決這個類別建構子所需要的依賴關係，而不再用 `new` 關鍵字。

```php
<?php
$app = Container::resolve('App');
?>
```

它的實作如下：

```php
<?php
class Container
{
    // ...

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
?>
```

`Container::resolve()` 方法與 `Container::get()` 方法的原理類似，但較特別的是它使用了 `ReflectionClass::getConstructor()` 方法來取得類別建構子的 `ReflectionMethod` 實體；接著再用 `ReflectionMethod::getParameters()` 取出參數的 `ReflectionParameter` 物件集合 (陣列) 。

而後我們就可以在迴圈中一一透過 `ReflectionParameter::getClass()` 方法與 `ReflectionClass::getName()` 方法來取得 type hint 所指向的類別名稱。當有了參數所對應的類別名稱後，就可以用 `Container::get()` 方法來取得參數的物件實體。

最後把這些物件帶回建構子的參數裡並初始化我們所需要的物件實體，就完成了 `App` 類別的自動依賴注入。

## 深入思考

再強調一次，這裡的範例只是為了介紹 DI 容器的原理，並不能真正用在實務上。因為一個完整的 DI 容器還要考慮以下的問題：

* 類別不存在時的處理。
* 與其他非類別的參數整合。
* 如何建立設定檔機制以便切換依賴關係。
* 遞迴地自動注入物件實體。
* 取得 Singleton 物件實體。
* 可以透過原始碼上的 DocBlock 註解來註明依賴關係。

目前已經有很多 DI Framework 幫我們處理好這些事情了，建議大家如果真的需要在專案中使用 DI 時，應該採用這些 Framework 。

## 總結

如果專案並不會有太多變化性，那麼依賴注入對我們來說就不是那麼重要。但是如果希望程式對特定類別的依賴性降低，只針對抽象介面實作，那麼依賴注入就有其必要性。

在 PHP 上的 DI 容器的基本實作原理也不複雜，透過 Reflection 機制就可以看到類別內部的結構，讓我們對它的建構子注入我們想要的參數值。

DI 容器要考量的部份也不少，但這些功能都已經有 Framework 實作，我們可以在專案中使用它們。

希望透過以上的介紹，可以讓大家對 Framework 的依賴注入機制有基本的認知。