<?php
function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
