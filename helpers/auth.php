<?php
function bearerToken(): ?string {
    $h = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
    if ($h === "" && function_exists("getallheaders")) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower($k) === "authorization") { $h = $v; break; }
        }
    }
    return preg_match('/^Bearer\s+(.+)$/i', $h, $m) ? $m[1] : null;
}

function currentUser(): ?array {
    $token = bearerToken();
    if (!$token) return null;
    $stmt = db()->prepare(
        "SELECT u.id, u.name, u.email, u.role
         FROM api_tokens t JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ?");
    $stmt->execute([hash("sha256", $token)]);
    return $stmt->fetch() ?: null;
}

function requireAuth(): array {
    $user = currentUser();
    if (!$user) respond(["error" => "Unauthorized"], 401);
    return $user;
}

function requireRole(array $roles): array {
    $user = requireAuth();
    if (!in_array($user["role"], $roles, true)) {
        respond(["error" => "Forbidden"], 403);
    }
    return $user;
}
