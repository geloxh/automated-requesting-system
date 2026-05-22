<?php
    echo password_hash('Keepreinventing7*', PASSWORD_BCRYPT, ['cost' => 12]);
    echo password_hash('3Ehitech*', PASSWORD_BCRYPT, ['cost' => 12]);
?>

"C:\xampp\php\php.exe" -r "echo password_hash('3Ehitech*', PASSWORD_BCRYPT, ['cost' => 12]);"
