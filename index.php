<?php
require __DIR__ . "/config/cors.php";
require __DIR__ . "/helpers/response.php";
require __DIR__ . "/helpers/db.php";
require __DIR__ . "/helpers/auth.php";

$method = $_SERVER["REQUEST_METHOD"];
$path   = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
$parts  = $path === "" ? [] : explode("/", $path);
$input  = json_decode(file_get_contents("php://input"), true) ?? [];

if (!$parts) respond(["status" => "School Attendance API is running"]);
if ($parts[0] !== "api" || empty($parts[1])) respond(["error" => "Not found"], 404);

$resource = $parts[1];
$segments = array_slice($parts, 2);   // e.g. ["class", "5"] or ["7"]

$routes = [
    "auth"        => "auth.php",
    "users"       => "users.php",
    "subjects"    => "subjects.php",
    "rooms"       => "rooms.php",
    "classes"     => "classes.php",
    "attendances" => "attendances.php",
];

if (!isset($routes[$resource])) respond(["error" => "Not found"], 404);

try {
    require __DIR__ . "/routes/" . $routes[$resource];
} catch (PDOException $e) {
    error_log($e->getMessage());
    $state = $e->getCode();
    if ($state === "23503") respond(["error" => "Referenced record does not exist, or record is in use"], 400);
    if ($state === "23505") respond(["error" => "Duplicate value"], 409);
    if ($state === "22P02" || $state === "22007" || $state === "22008") respond(["error" => "Invalid value in request"], 400);
    if ($state === "23514") respond(["error" => "Value not allowed"], 400);
    respond(["error" => "Database error"], 500);
}
