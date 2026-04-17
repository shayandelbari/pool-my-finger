<?php

if ($apiPath === "users" || str_starts_with($apiPath, 'users/')) {
    jsonResponse(["message" => "Users endpoint - not implemented yet"]);
    return;
}

jsonResponse(["error" => "API endpoint not found"], 404);
