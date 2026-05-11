<?php

declare(strict_types=1);

$__target = __DIR__ . '/../home/home.php';

if (!is_file($__target)) {
	http_response_code(500);
	error_log('View not found: ' . $__target);
	echo 'Erro interno.';
	return;
}

require $__target;
