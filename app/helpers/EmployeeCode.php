<?php
    namespace App\Helpers;

    function generateEmployeeCode(\PDO $pdo): string {
        $last = $pdo->query(
            "SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        $next = $last ? (int) filter_var($last, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }