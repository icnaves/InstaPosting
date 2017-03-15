<?php

require_once 'lib/InstaPosting.php';

# Логин и пароль Инстаграм аккаунта
$username = '';
$password = '';

# Авторизация
$InstaPosting = new InstaPosting($username, $password);

# URL картинки
$image_url = 'http://www.ronaldrestituyo.com/wp-content/uploads/2016/10/Instagram_Logo1.jpg';

# Комментарий к записи
$comment = 'Привет, Instagram';

# Постинг в Инстаграм
$InstaPosting->PostImage($image_url, $comment);