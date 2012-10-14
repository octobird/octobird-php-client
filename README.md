Octobird Client
===============

Класс для работы с рекламной сетью [Octobird](http://octobird.com "Мобильная рекламная сеть Ocobird")

Установка
------------------

Загрузите исходный код с помощью добавления git модуля или скачав архив

### Загрузить как git модуль

	$ git submodule add git://github.com/octobird/octobird-php-client.git vendor/octobird/octobird-php-client

### Загрузить как архив

Скачайте архив по адресу [https://github.com/octobird/octobird-php-client/zipball/master](https://github.com/octobird/octobird-php-client/zipball/master "https://github.com/octobird/octobird-php-client/zipball/master") и разархивируйте файлы в директорию vendor/octobird/octobird-php-client

Настройка
------------------

Для показа рекламы нужно подключить и настроить класс OctobirdClient до вывода каких либо данных.
Класс позволяет запрашивать рекламу в форматах HTML, JSON, XML.
Для вывода одного рекламного советуем использовать HTML формат.

### Один рекламный блок

#### Простой вариант

``` php
<?php

require_once('vendor/octobird/octobird-php-client/OctobirdClient.php');

//настройка клиента и отправка запроса на рекламу
OctobirdClient::getInstance()
    ->setSiteId(1234)//id сайта
    ->send();

...
//вывод рекламы
echo OctobirdClient::getInstance()->get();
```

#### Вариант с установкой типа и количества баннеров

Запрашиваем 4 графических баннера

``` php
<?php

require_once('vendor/octobird/octobird-php-client/OctobirdClient.php');

//настройка клиента и отправка запроса на рекламу
OctobirdClient::getInstance()
    ->setSiteId(1234)//id сайта
    ->setBannerNumber(4)
    ->setBannerType(OctobirdClient::BANNER_TYPE_IMAGE)
    ->send();

...
//вывод рекламы
echo OctobirdClient::getInstance()->get();
```

### Несколько рекламных блоков

Для вывода нескольких рекламных блоков советуем использовать формат json-html. В данном формате отдается JSON объект, где каждый рекламный уже содержит готовый HTML код.
Пример вывода двух рекламных блоков.
В первом блоке будут два графических баннера, во втором один баннер любого типа.

``` php
<?php

require_once('vendor/octobird/octobird-php-client/OctobirdClient.php');

//настройка клиента и отправка запроса на рекламу
OctobirdClient::getInstance()
    ->setSiteId(1234)//id сайта
    ->setResponseFormat(OctobirdClient::RESPONSE_FORMAT_JSON_HTML)
    ->addBlock('first', 2, OctobirdClient::BANNER_TYPE_IMAGE)
    ->addBlock('second', 1)
    ->send();

...
//первый блок
echo OctobirdClient::getInstance()->getBlock('first');
//второй блок
echo OctobirdClient::getInstance()->getBlock('second');
```