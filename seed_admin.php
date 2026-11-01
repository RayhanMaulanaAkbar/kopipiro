<?php
include 'config.php';

$username = "owner1";
$password = "Kopipiro123!";
$role = "owner";

$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE username = ?"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "Akun Owner sudah tersedia.";
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO users (username, password, role)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $insert,
        "sss",
        $username,
        $hash,
        $role
    );

    if (mysqli_stmt_execute($insert)) {
        echo "Akun Owner berhasil dibuat.";
    } else {
        echo "Gagal membuat akun Owner.";
    }

    mysqli_stmt_close($insert);
}

mysqli_stmt_close($stmt);
?>