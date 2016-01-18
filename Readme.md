# Socialcast PHP

[![Master Build Status](https://secure.travis-ci.org/noprotocol/socialcast-php.png?branch=master)](http://travis-ci.org/noprotocol/socialcast-php)


PHP client for [Socialcast’s REST API](http://developers.socialcast.com/api-documentation/)

## Examples

### Using Basic Authentication
```php
$auth = new Socialcast\Auth\BasicAuth('demo', 'emily@socialcast.com', 'demo');
$socialcast = new Socialcast\Client($auth);

$user = $socialcast->getUserinfo();
$messages = $user->getMessages(['page' => 1, 'per_page' => 25]);
var_dump($messages);
```

### Using OAuth 2.0 Authentication
```php
session_start();
$oauth = new Socialcast\Auth\OAuth('demo', $appId, $secret, $callbackUrl, function () {
    return $_SESSION['socialcast'];
}, function ($token) {
    $_SESSION['socialcast'] = $token;
});
// On the callback url page:
if (isset($_GET['code']) {
    $oauth->authenticate($_GET['code']);
    $socialcast = new Socialcast\Client($auth);
    if ($socialcast->getUserinfo()->id) {
        header('Location: /logged-in'); 
        exit();
    }
    echo 'Authentication failed: <a href="'.$oauth->getLoginUrl().''">retry</a>';
    
} else {
    header('Location: '.$oauth->getLoginUrl());
}
```

### Resources are Lazy 
To minimize the amount of api request the Resource objects such as the User resource are loaded lazily.

For example: `$user = $socialcast->getUser(123);` doesn't make any api calls until a property is accessed. 
var_dump($user); // shows #response: false.
$id = $user->id
var_dump($user); // shows #response: array with data.