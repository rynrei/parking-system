<?php

function logActivity($conn, $user_id, $username, $role, $action)
{
    $sql = "INSERT INTO activitylogs
            (UserID, Username, Role, Action)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Activity log prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isss",
        $user_id,
        $username,
        $role,
        $action
    );

    if (!$stmt->execute()) {
        die("Activity log insert failed: " . $stmt->error);
    }
}
?>