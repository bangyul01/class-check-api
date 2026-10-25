<?php
requireRole(["admin"]);
$id = $segments[0] ?? null;

switch ($method) {
    case "GET":
        if ($id) {
            $stmt = db()->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? respond($row) : respond(["error" => "Room not found"], 404);
        }
        respond(db()->query("SELECT * FROM rooms ORDER BY name")->fetchAll());

    case "POST":
        if (empty($input["name"])) respond(["error" => "name is required"], 400);
        $stmt = db()->prepare(
            "INSERT INTO rooms (name, capacity) VALUES (?, ?) RETURNING *");
        $stmt->execute([$input["name"], $input["capacity"] ?? 30]);
        respond($stmt->fetch(), 201);

    case "PUT":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare(
            "UPDATE rooms SET name = COALESCE(?, name), capacity = COALESCE(?, capacity)
             WHERE id = ? RETURNING *");
        $stmt->execute([$input["name"] ?? null, $input["capacity"] ?? null, $id]);
        $row = $stmt->fetch();
        $row ? respond($row) : respond(["error" => "Room not found"], 404);

    case "DELETE":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->rowCount() ? respond(["message" => "Deleted"])
                          : respond(["error" => "Room not found"], 404);

    default:
        respond(["error" => "Method not allowed"], 405);
}
