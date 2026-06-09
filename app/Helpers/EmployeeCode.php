<?php
    namespace App\Helpers;

    function generateEmployeeCode(\PDO $pdo): string {
       $max = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) FROM employees")->fetchColumn();
       return "EMP-" . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }