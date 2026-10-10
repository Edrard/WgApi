# WgApi

Бібліотека для PHP 8.5 формує один URL World of Tanks із регіону, шляху методу та параметрів. HTTP-запитів, повторів і поділу ID на групи тут немає.

Канонічна документація та повний контракт: [README англійською](../../README.md).

Для внутрішнього використання встановлюємо поточну гілку `dev-master`, без релізних тегів. Додайте VCS repository `https://github.com/Edrard/WgApi.git` у кореневий composer.json застосунку, потім виконайте `composer require edrard/wgapi:dev-master`. Повний приклад Git/path repositories для трьох пакетів є у [README WotClient](../../../WotClient/README.md#installation).

`composer update edrard/wgapi --with-all-dependencies` підтягує актуальний коміт гілки. `composer install` відтворює коміт із composer.lock; під час запуску PHP автоматичного оновлення немає.

```php
require 'vendor/autoload.php';

use edrard\WgApi\GetWgApi;

$api = new GetWgApi(['eu' => $applicationId]);
$url = $api->getUrl('eu', 'wot', 'account/info', [
    'account_id' => [1, 2],
    'language' => 'de',
]);
```

`application_id` береться з конфігурації. Додаткові параметри передаються у URL; масиви стають списками через кому. Бібліотека потребує PHP `^8.5`, `ext-ctype`, `ext-filter`.

Окремого логера тут немає: записи про кожен URL роздували б журнал і дублювали діагностику вищих шарів. PSR-3 логування виконання та повторів доступне у WotClient і WgDataGetter. URL із користувацьким токеном не слід записувати у журнал застосунку.

Перевірки розробника: `composer test`, `composer analyse`, `composer format:check`, `composer validate --strict`.
