<?php
require_once '../config/db.php';
requireRole(['admin','developer']);
redirect(APP_URL . '/developer/admissions.php' . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
