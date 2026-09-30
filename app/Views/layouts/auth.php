<?php
use function App\Core\e;
$appcfg = $appcfg ?? ['name' => 'LUNATIC', 'tagline' => ''];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Connexion') ?> — <?= e($appcfg['name']) ?></title>
<meta name="csrf-token" content="<?= e($csrf ?? '') ?>">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?= $content ?? '' ?>
</body>
</html>
