<?php
session_start();
require_once 'config.php';

if (isset($_POST['register'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $dob   = $_POST['dob'];
    $address   = $_POST['address'];
    $phone   = $_POST['phone'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $checkEmail = $conn->query("SELECT email FROM member WHERE email = '$email'");
    if ($checkEmail->num_rows > 0){
        $_SESSION['register_error'] = 'Email is already registered!';
    } else {

        $sql = "INSERT INTO member (fname, lname, dob, address, phone, email, password_hash, role)
        VALUES ('$fname', '$lname', '$dob', '$address', '$phone', '$email', '$password', '$role')";


        if ($conn->query($sql) === TRUE) {
            $_SESSION['register_success'] = 'Registration successful!';
        } else {
            $_SESSION['register_error'] = 'Error: ' . $conn->error;
        }
    }
    header("Location: index.php");
    exit();
}
?>