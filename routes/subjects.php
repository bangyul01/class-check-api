<?php
requireRole(["admin"]);
$id = $segments[0] ?? null;

switch ($method) {
    case "GET":
        if ($id) {
            $stmt = db()->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? respond($row) : respond(["error" => "Subject not found"], 404);
        }
        respond(db()->query("SELECT * FROM subjects ORDER BY name")->fetchAll());

    case "POST":
        if (empty($input["name"]) || empty($input["code"])) {
            respond(["error" => "name and code are required"], 400);
        }
        $stmt = db()->prepare(
            "INSERT INTO subjects (name, code) VALUES (?, ?) RETURNING *");
        $stmt->execute([$input["name"], $input["code"]]);
        respond($stmt->fetch(), 201);

    case "PUT":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare(
            "UPDATE subjects SET name = COALESCE(?, name), code = COALESCE(?, code)
             WHERE id = ? RETURNING *");
        $stmt->execute([$input["name"] ?? null, $input["code"] ?? null, $id]);
        $row = $stmt->fetch();
        $row ? respond($row) : respond(["error" => "Subject not found"], 404);

    case "DELETE":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->rowCount() ? respond(["message" => "Deleted"])
                          : respond(["error" => "Subject not found"], 404);

    default:
        respond(["error" => "Method not allowed"], 405);
}
