<?php
$user = requireAuth();
$staff = in_array($user["role"], ["admin", "teacher"], true);
$a = $segments[0] ?? null;
$b = $segments[1] ?? null;

$select = "SELECT a.*, u.name AS user_name
           FROM attendances a JOIN users u ON u.id = a.user_id";

if ($method === "GET") {
    if ($a === "class" && $b) {                     // GET /attendances/class/{id}
        if (!$staff) respond(["error" => "Forbidden"], 403);
        $stmt = db()->prepare("$select WHERE a.class_id = ? ORDER BY a.checked_in_at DESC");
        $stmt->execute([$b]);
        respond($stmt->fetchAll());
    }
    if ($a === "user" && $b) {                      // GET /attendances/user/{id}
        if (!$staff && (int)$b !== (int)$user["id"]) {
            respond(["error" => "Forbidden"], 403);
        }
        $stmt = db()->prepare("$select WHERE a.user_id = ? ORDER BY a.checked_in_at DESC");
        $stmt->execute([$b]);
        respond($stmt->fetchAll());
    }
    if ($a) {                                       // GET /attendances/{id}
        $stmt = db()->prepare("$select WHERE a.id = ?");
        $stmt->execute([$a]);
        $row = $stmt->fetch();
        if (!$row) respond(["error" => "Attendance not found"], 404);
        if (!$staff && (int)$row["user_id"] !== (int)$user["id"]) {
            respond(["error" => "Forbidden"], 403);
        }
        respond($row);
    }
    if (!$staff) respond(["error" => "Forbidden"], 403);   // GET /attendances
    respond(db()->query("$select ORDER BY a.checked_in_at DESC")->fetchAll());
}

if ($method === "POST") {                           // Check-in
    if (empty($input["class_id"])) respond(["error" => "class_id is required"], 400);
    $targetUser = $staff ? ($input["user_id"] ?? $user["id"]) : $user["id"];
    $status = $input["status"] ?? "present";
    if (!in_array($status, ["present", "late", "absent", "excused"], true)) {
        respond(["error" => "Invalid status"], 400);
    }
    $stmt = db()->prepare(
        "INSERT INTO attendances (class_id, user_id, status)
         VALUES (?, ?, ?) RETURNING *");
    $stmt->execute([$input["class_id"], $targetUser, $status]);
    respond($stmt->fetch(), 201);
}

if ($method === "PUT" || $method === "DELETE") {
    if (!$staff) respond(["error" => "Forbidden"], 403);
    if (!$a) respond(["error" => "ID required"], 400);

    if ($method === "PUT") {
        if (isset($input["status"]) &&
            !in_array($input["status"], ["present", "late", "absent", "excused"], true)) {
            respond(["error" => "Invalid status"], 400);
        }
        $stmt = db()->prepare(
            "UPDATE attendances SET status = COALESCE(?, status) WHERE id = ? RETURNING *");
        $stmt->execute([$input["status"] ?? null, $a]);
        $row = $stmt->fetch();
        $row ? respond($row) : respond(["error" => "Attendance not found"], 404);
    }
    $stmt = db()->prepare("DELETE FROM attendances WHERE id = ?");
    $stmt->execute([$a]);
    $stmt->rowCount() ? respond(["message" => "Deleted"])
                      : respond(["error" => "Attendance not found"], 404);
}

respond(["error" => "Method not allowed"], 405);
