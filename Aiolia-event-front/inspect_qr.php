<?php
require __DIR__ . '/vendor/autoload.php';

try {
    $reflection = new ReflectionClass(\Endroid\QrCode\Builder\Builder::class);
    $constructor = $reflection->getConstructor();
    if ($constructor) {
        echo "Constructor parameters:\n";
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            echo " - " . $param->getName() . " (" . ($type ? $type->getName() : 'mixed') . ")\n";
        }
    } else {
        echo "No constructor.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
