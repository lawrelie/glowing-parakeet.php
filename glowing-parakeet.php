<?php
set_error_handler(
    function(int $errno, string $errstr, string $errfile, int $errline): bool {
        if (error_reporting() & $errno) {
            throw new ErrorException($errstr, severity: $errno, filename: $errfile, line: $errline);
        }
        return true;
    },
);
set_exception_handler(
    function(Throwable $ex): void {
        if (!error_reporting()) {
            return;
        }
        printf("Uncaught %s\n", htmlspecialchars($ex, ENT_HTML5 | ENT_QUOTES));
        die;
    },
);
spl_autoload_register(
    function(string $className): void {
        $filename = __DIR__ . "/src/$className.php";
        if (file_exists($filename)) {
            require_once $filename;
        }
    },
);
