<?php
require_once '../config/db.php';
session_destroy();
redirect(APP_URL . '/auth/login.php');
