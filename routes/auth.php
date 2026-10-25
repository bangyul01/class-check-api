<?php
$action = $segments[0] ?? "";

if ($action === "register" && $method === "POST") {
    foreach (["name", "email", "password"] as $f) {
        if (empty($input[$f])) respond(["error" => "$f is required"], 400);
    }
    if (!filter_var($input["email"], FILTER_VALIDATE_EMAIL)) {
        respond(["error" => "Invalid email"], 400);
    }
    if (strlen($input["password"]) < 8) {
        respond(["error" => "Password must be at least 8 characters"], 400);
    }
    // Public signup can never create admins
    $role = $input["role"] ?? "student";
    if (!in_array($role, ["student", "teacher", "parent"], true)) {
        respond(["error" => "Invalid role"], 400);
    }

    $stmt = db()->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->execute([$input["email"]]);
    if ($stmt->fetch()) respond(["error" => "Email already registered"], 409);

    $stmt = db()->prepare(
        "INSERT INTO users (name, email, password_hash, role)
         VALUES (?, ?, ?, ?) RETURNING id");
    $stmt->execute([$input["name"], $input["email"],
                    password_hash($input["password"], PASSWORD_DEFAULT), $role]);
    respond(["message" => "Registered", "id" => $stmt->fetch()["id"]], 201);

} elseif ($action === "login" && $method === "POST") {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$input["email"] ?? ""]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input["password"] ?? "", $user["password_hash"])) {
        respond(["error" => "Invalid email or password"], 401);
    }

    $token = bin2hex(random_bytes(32));
    db()->prepare("INSERT INTO api_tokens (user_id, token_hash) VALUES (?, ?)")
        ->execute([$user["id"], hash("sha256", $token)]);

    respond([
        "token" => $token,
        "user"  => ["id" => $user["id"], "name" => $user["name"],
                    "email" => $user["email"], "role" => $user["role"]],
    ]);

} elseif ($action === "logout" && $method === "POST") {
    requireAuth();
    db()->prepare("DELETE FROM api_tokens WHERE token_hash = ?")
        ->execute([hash("sha256", bearerToken())]);
    respond(["message" => "Logged out"]);

} else {
    respond(["error" => "Not found"], 404);
}
