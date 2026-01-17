<?php
/**
 * User Model
 * 
 * Represents a user in the vending machine system.
 */

namespace App\Models;

use App\Core\Database;

class User
{
    private Database $db;
    private string $table = 'users';

    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public string $role;
    public float $balance;
    public ?string $createdAt;
    public ?string $updatedAt;
    public ?string $lastLogin;
    public bool $isActive;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?self
    {
        $data = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?self
    {
        $data = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE username = :username",
            ['username' => $username]
        );

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?self
    {
        $data = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE email = :email",
            ['email' => $email]
        );

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Get all users
     */
    public function all(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY username ASC";

        $results = $this->db->fetchAll($sql);
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get users with pagination
     */
    public function paginate(int $page = 1, int $perPage = 10, bool $activeOnly = true): array
    {
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        if ($activeOnly) {
            $countSql .= " WHERE is_active = 1";
        }
        $total = (int) $this->db->fetchColumn($countSql);

        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY username ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $users = array_map(fn($data) => (new self($this->db))->hydrate($data), $results);

        return [
            'data' => $users,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Create a new user
     */
    public function create(array $data): self
    {
        $id = $this->db->insert($this->table, [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]),
            'role' => $data['role'] ?? 'user',
            'balance' => $data['balance'] ?? 0.00,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);

        return $this->find($id);
    }

    /**
     * Update user
     */
    public function update(array $data): bool
    {
        $updateData = [];
        
        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);
        }
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        if (isset($data['balance'])) {
            $updateData['balance'] = $data['balance'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (int) $data['is_active'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update(
            $this->table,
            $updateData,
            'id = :id',
            ['id' => $this->id]
        ) > 0;
    }

    /**
     * Delete user
     */
    public function delete(): bool
    {
        return $this->db->delete(
            $this->table,
            'id = :id',
            ['id' => $this->id]
        ) > 0;
    }

    /**
     * Update balance
     */
    public function updateBalance(float $amount): bool
    {
        $newBalance = $this->balance + $amount;
        
        if ($newBalance < 0) {
            return false;
        }

        $result = $this->db->update(
            $this->table,
            ['balance' => $newBalance],
            'id = :id',
            ['id' => $this->id]
        ) > 0;

        if ($result) {
            $this->balance = $newBalance;
        }

        return $result;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Hydrate model from database row
     */
    private function hydrate(array $data): self
    {
        $this->id = (int) $data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->role = $data['role'];
        $this->balance = (float) $data['balance'];
        $this->createdAt = $data['created_at'];
        $this->updatedAt = $data['updated_at'];
        $this->lastLogin = $data['last_login'];
        $this->isActive = (bool) $data['is_active'];

        return $this;
    }

    /**
     * Convert to array (excluding sensitive data)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'balance' => $this->balance,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'last_login' => $this->lastLogin,
            'is_active' => $this->isActive,
        ];
    }
}
