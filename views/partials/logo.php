<?php
$size = $size ?? 40;
$alt = $alt ?? 'TSU-SAMS';
$class = $class ?? 'brand-logo';
?>
<img src="<?= e(base_url('assets/img/logo.png')) ?>" alt="<?= e($alt) ?>" width="<?= (int)$size ?>" height="<?= (int)$size ?>" class="<?= e($class) ?>">
