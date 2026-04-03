<?php
session_start();
unset($_SESSION['aktif_tc']);
echo json_encode(['success' => true]);
?>