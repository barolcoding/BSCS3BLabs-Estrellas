<?php
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $check = $conn->query(
        "SELECT * FROM users WHERE username = '$username'"
    );

    if ($check->num_rows > 0) {
        $message = "Username already exists!";
    } else {
        $conn->query(
            "INSERT INTO users (username, password)
             VALUES ('$username', '$hashedPassword')"
        );

        $message = "Registration successful!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
</head>

<body>

    <h2>Register</h2>

    <form method="POST">

        <label>Username:</label><br>
        <input type="text" name="username" required>
        <br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Register</button>

    </form>

    <p><?php echo $message; ?></p>

    <a href="index.php">Go to Login</a>

</body>

</html>