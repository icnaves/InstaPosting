# InstaPosting
PHP скрипт для автоматического постинга картинок в соцсеть instagram.com всего в два шага.

## Принцип работы

- Авторизация в сервисе
- Копирование картинки по url в папку uploads
- Постинг в instagram.com
- Удаление картинки из uploads

>Так как скрипт копирует картинку на сервер, **необходимо папке uploads и корневой папке InstaPosting выставить права 777(на запись)**

>InstaPosting работает только с расширением *.jpg

## Пример использования

- *Авторизация*
```php
$InstaPosting = new InstaPosting('username', 'password');
```

- *Постинг*
```php
$InstaPosting->PostImage('http://www.ronaldrestituyo.com/wp-content/uploads/2016/10/Instagram_Logo1.jpg', 'Привет, Instagram');
```




*Спасибо https://rche.ru за идею!*