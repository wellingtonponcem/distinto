<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
deslogarUsuario();
header('Location: ../../index.php');
exit;
