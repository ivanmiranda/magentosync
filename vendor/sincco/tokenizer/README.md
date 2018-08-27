# tokenizer
Create and validate token for user data.
This tool simplifies the creation and validation for token with user data, the resulting string can be used as a URL parameter.

##Installation

To add this package as a local, per-project dependency to your project, simply add a dependency on phpunit/php-token-stream to your project's composer.json file. Here is a minimal example of a composer.json file that just defines a dependency on Tokenizer:

```json
{
    "require": {
        "sincco/tokenizer": "~1.0"
    }
}
```


##Use

###Creation

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use \Sincco\Tools;

$userData = [ 'idUser'=>666, 'emailUser'=>'ivan.miranda@sincco.com' ];
$password = "p4$sw0rD";
$minutesExpiration = 10;
echo Tokenizer::create( $userData, $password, $minutesExpiration );
```

###Validation
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use \Sincco\Tools;

$password = "p4$sw0rD";
$valid = Tokenizer::validate( $token, $password );
```


#### NOTICE OF LICENSE
This source file is subject to the Open Software License (OSL 3.0) that is available through the world-wide-web at this URL:
http://opensource.org/licenses/osl-3.0.php

**Happy coding!**
- [ivan miranda](http://ivanmiranda.me)
