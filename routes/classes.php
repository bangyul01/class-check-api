<?php
// Anyone logged in can read; only admins can change classes.
$user = requireAuth();
if ($method !== "GET") requireRole(["admin"]);
$id = $segments[0] ?? null;

$select = "SELECT c.*, s.name AS subject_name, r.name AS room_name, t.name AS teacher_name
           FROM classes c
           JOIN subjects s ON s.id = c.subject_id
           JOIN rooms r ON r.id = c.room_id
           JOIN users t ON t.id = c.teacher_id";

switch ($method) {
    case "GET":
        if ($id) {
            $stmt = db()->prepare("$select WHERE c.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? respond($row) : respond(["error" => "Class not found"], 404);
        }
        respond(db()->query("$select ORDER BY c.day_of_week, c.start_time")->fetchAll());

    case "POST":
        foreach (["subject_id", "room_id", "teacher_id", "day_of_week", "start_time", "end_time"] as $f) {
            if (empty($input[$f])) respond(["error" => "$f is required"], 400);
        }
        $stmt = db()->prepare(
            "INSERT INTO classes (subject_id, room_id, teacher_id, day_of_week, start_time, end_time)
             VALUES (?, ?, ?, ?, ?, ?) RETURNING *");
        $stmt->execute([$input["subject_id"], $input["room_id"], $input["teacher_id"],
                        $input["day_of_week"], $input["start_time"], $input["end_time"]]);
        respond($stmt->fetch(), 201);

    case "PUT":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare(
            "UPDATE classes SET
               subject_id = COALESCE(?, subject_id),
               room_id = COALESCE(?, room_id),
               teacher_id = COALESCE(?, teacher_id),
               day_of_week = COALESCE(?, day_of_week),
               start_time = COALESCE(?, start_time),
               end_time = COALESCE(?, end_time)
             WHERE id = ? RETURNING *");
        $stmt->execute([$input["subject_id"] ?? null, $input["room_id"] ?? null,
                        $input["teacher_id"] ?? null, $input["day_of_week"] ?? null,
                        $input["start_time"] ?? null, $input["end_time"] ?? null, $id]);
        $row = $stmt->fetch();
        $row ? respond($row) : respond(["error" => "Class not found"], 404);

    case "DELETE":
        if (!$id) respond(["error" => "ID required"], 400);
        $stmt = db()->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        $stmt->rowCount() ? respond(["message" => "Deleted"])
                          : respond(["error" => "Class not found"], 404);

    default:
        respond(["error" => "Method not allowed"], 405);
}
