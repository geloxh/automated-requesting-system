<!-- this is the original approval model -->

<?php

class Approval
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );
    }

    public function create($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO approvals (title, status, level)
            VALUES (?, 'pending', 1)
        ");
        return $stmt->execute([$data['title']]);
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->pdo->prepare("
            UPDATE approvals SET status=? WHERE id=?
        ");
        return $stmt->execute([$status, $id]);
    }

    public function moveLevel($id)
    {
        $stmt = $this->pdo->prepare("
            UPDATE approvals SET level = level + 1 WHERE id=?
        ");
        return $stmt->execute([$id]);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM approvals WHERE id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}