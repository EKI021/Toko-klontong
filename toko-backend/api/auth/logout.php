<?php
require_once __DIR__ . '/../../helpers/bootstrap.php';

$token = getBearerToken();
if ($token) {
    getDB()->prepare("DELETE FROM sesi_login WHERE token = :t")->execute(['t' => $token]);
}
jsonResponse(['ok' => true]);
