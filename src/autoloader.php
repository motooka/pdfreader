<?php
spl_autoload_register(function ($class) {
	if(strpos($class, 'motooka\\PDFReader\\') === 0) {
		$filePath = preg_replace('/^motooka\\\\PDFReader\\\\/', __DIR__ . DIRECTORY_SEPARATOR, $class);
		$filePath = str_replace('\\', DIRECTORY_SEPARATOR, $filePath) . '.php';
		require $filePath;
	}
}, true);
