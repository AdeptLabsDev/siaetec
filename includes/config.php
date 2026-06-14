<?php

// Fuso horário padrão — alinha date() do PHP com os horários do MySQL
date_default_timezone_set('America/Sao_Paulo');

// Banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_alimentar');
define('DB_USER', 'root');       // usuário padrão do XAMPP
define('DB_PASS', '');           // senha padrão do XAMPP é vazia

// Evolution API (WhatsApp)
define('EVOLUTION_API_URL', '');
define('EVOLUTION_API_TOKEN', '');
define('EVOLUTION_INSTANCIA', '');
define('WHATSAPP_DESTINO', '');
