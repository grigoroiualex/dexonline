<?php
require_once("../phplib/Core.php");

$file = Request::getFile('avatarFileName');
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$error = '';

if (!$file) {
  $error = 'Ați încărcat un fișier invalid.';
} else if ($file['size'] > (1 << 21)) {
  $error = 'Dimensiunea maximă admisă este 2 MB.';
} else if (!in_array($file['type'], ['image/gif', 'image/jpeg', 'image/png'])) {
  $error = 'Sunt permise doar imagini jpeg, png sau gif.';
} else if ($file['error']) {
  $error = 'A intervenit o eroare la încărcare.';
} else if ((getimagesize($file['tmp_name']) === false) || !in_array($ext, ['png', 'jpg', 'gif'])) {
  $error = 'Sunt permise doar imagini jpeg, png sau gif.';
}

if ($error) {
  FlashMessage::add($error);
  Util::redirect(Core::getWwwRoot() . 'preferinte');
}

$user = User::getActive();
if (!$user) {
  FlashMessage::add('Nu puteți alege o imagine de profil dacă nu sunteți autentificat.');
  Util::redirect(Core::getWwwRoot());
}

// Remove any old files (with different extensions)
$oldFiles = glob(Core::getRootPath() . "wwwbase/img/generated/{$user->id}_raw.*");
foreach ($oldFiles as $oldFile) {
  unlink($oldFile);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$destFileName = Core::getRootPath() . "wwwbase/img/generated/{$user->id}_raw.{$ext}";

if (!move_uploaded_file($file['tmp_name'], $destFileName)) {
  FlashMessage::add('A intervenit o eroare la copierea fișierului.');
  Util::redirect(Core::getWwwRoot() . 'preferinte');
}
chmod($destFileName, 0666);

SmartyWrap::addCss('jcrop');
SmartyWrap::addJs('jcrop');
SmartyWrap::assign('rawFileName', "{$user->id}_raw.{$ext}");
SmartyWrap::display('editare-avatar.tpl');
