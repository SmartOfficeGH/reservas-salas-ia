<?php
declare(strict_types=1);
// Copiar como config.php SOLO en la carpeta privada del servidor.
return [
    'environment' => 'production',
    'app_url' => 'https://reservasalas.metavisuals.es',
    'timezone' => 'Europe/Madrid',
    'secure_cookies' => true,
    'db' => [
        'host' => 'localhost', // Confirmado por soporte; conexión real pendiente de probar.
        'port' => 3306, // Confirmado por soporte para PHP en esta cuenta.
        'name' => 'delanada_reservas_sala',
        'user' => 'delanada_reservas_app',
        'password' => '', // Introducir únicamente en el servidor.
    ],
    'smtp' => [
        'host' => 'smtp.gmail.com', // Salida permitida por soporte; envío real pendiente de probar.
        'port' => 587, // STARTTLS permitido por soporte de Webempresa.
        'encryption' => 'tls',
        'username' => 'smartofficepalma@gmail.com',
        'password' => '', // Contraseña de aplicación de Google, solo en el servidor.
        'from' => 'smartofficepalma@gmail.com',
        'from_name' => 'Aplicación Reserva Salas',
    ],
];
