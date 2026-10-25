<?php
$admin = requireRole(["admin"]);
$id = $segments[0] ?? null;
$cols = "id, name, email, role, created_at";

switch ($method) {
    case "GET":
        if ($id) {
            $stmt = db()->prepare("SELECT $cols FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? respond($row) : respond(["error" => "User not found"], 404);
        }
        respond(db()->query("SELECT $cols FROM users ORDER BY name")->fetchAll());

    case "PUT":
        if (!$id) respond(["error" => "ID required"], 400);
        if (isset($input["role"]) &&
            !in_array($input["role"], ["admin", "teacher", "student", "parent"], true)) {
            respond(["error" => "Invalid role"], 400);
        }
        if (isset($input["email"]) && !filter_var($input["email"], FILTER_VALIDATE_EMAIL)) {
            respond(["error" => "Invalid email"], 400);
        }
        $hash = null;
        if (!empty($input["password"])) {
            if (strlen($input["password"]) < 8) {
                respond(["error" => "Password must be at least 8 characters"], 400);
            }
            $hash = password_hash($input["password"], PASSWORD_DEFAULT);
        }
        $stmt = db()->prepare(
            "UPDATE users SET
               name = COALESCE(?, name),
               email = COALESCE(?, email),
               role = COALESCE(?, role),
               password_hash = COALESCE(?, password_hash)
             WHERE id = ? RETURNING $cols");
        $stmt->execute([$input["name"] ?? null, $input["email"] ?? null,
                        $input["role"] ?? null, $hash, $id]);
        $row = $stmt->fetch();
        $row ? respond($row) : respond(["error" => "User not found"], 404);

    case "DELETE":
        if (!$id) respond(["error" => "ID required"], 400);
        if ((int)$id === (int)$admin["id"]) {
            respond(["error" => "You cannot delete your own account"], 400);
        }
        $stmt = db()->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->rowCount() ? respond(["message" => "Deleted"])
                          : respond(["error" => "User not found"], 404);

    default:
        respond(["error" => "Method not allowed"], 405);
}
