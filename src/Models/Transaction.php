<?php
/**
 * Transaction Model
 * 
 * Represents a purchase transaction in the vending machine system.
 */

namespace App\Models;

use App\Core\Database;

class Transaction
{
    private Database $db;
    private string $table = 'transactions';

    public int $id;
    public int $userId;
    public int $productId;
    public int $quantity;
    public float $unitPrice;
    public float $totalAmount;
    public string $transactionDate;
    public string $status;
    public ?string $paymentMethod;
    public ?string $notes;

    // Related models
    public ?User $user = null;
    public ?Product $product = null;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Find transaction by ID
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
     * Find transaction by ID with relations
     */
    public function findWithRelations(int $id): ?self
    {
        $data = $this->db->fetch(
            "SELECT t.*, u.username, u.email, p.name as product_name 
             FROM {$this->table} t
             JOIN users u ON t.user_id = u.id
             JOIN products p ON t.product_id = p.id
             WHERE t.id = :id",
            ['id' => $id]
        );

        if (!$data) {
            return null;
        }

        $transaction = $this->hydrate($data);
        $transaction->loadRelations();

        return $transaction;
    }

    /**
     * Get all transactions
     */
    public function all(): array
    {
        $results = $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY transaction_date DESC"
        );
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get transactions by user ID
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->db->fetchAll(
            "SELECT t.*, p.name as product_name 
             FROM {$this->table} t
             JOIN products p ON t.product_id = p.id
             WHERE t.user_id = :user_id 
             ORDER BY t.transaction_date DESC",
            ['user_id' => $userId]
        );
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get transactions with pagination
     */
    public function paginate(int $page = 1, int $perPage = 10, ?int $userId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];

        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        $sql = "SELECT t.*, u.username, p.name as product_name 
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                JOIN products p ON t.product_id = p.id";

        if ($userId !== null) {
            $countSql .= " WHERE user_id = :user_id";
            $sql .= " WHERE t.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $total = (int) $this->db->fetchColumn($countSql, $params);

        $sql .= " ORDER BY t.transaction_date DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $transactions = array_map(fn($data) => (new self($this->db))->hydrate($data), $results);

        return [
            'data' => $transactions,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Create a new transaction
     */
    public function create(array $data): self
    {
        $id = $this->db->insert($this->table, [
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_amount' => $data['total_amount'],
            'status' => $data['status'] ?? 'completed',
            'payment_method' => $data['payment_method'] ?? 'balance',
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->find($id);
    }

    /**
     * Update transaction status
     */
    public function updateStatus(string $status): bool
    {
        return $this->db->update(
            $this->table,
            ['status' => $status],
            'id = :id',
            ['id' => $this->id]
        ) > 0;
    }

    /**
     * Get transactions by status
     */
    public function findByStatus(string $status): array
    {
        $results = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE status = :status ORDER BY transaction_date DESC",
            ['status' => $status]
        );
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get transactions within date range
     */
    public function findByDateRange(string $startDate, string $endDate): array
    {
        $results = $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE transaction_date BETWEEN :start AND :end 
             ORDER BY transaction_date DESC",
            ['start' => $startDate, 'end' => $endDate]
        );
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get total sales amount
     */
    public function getTotalSales(?int $userId = null): float
    {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE status = 'completed'";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        return (float) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Load related models
     */
    public function loadRelations(): self
    {
        $this->user = (new User($this->db))->find($this->userId);
        $this->product = (new Product($this->db))->find($this->productId);

        return $this;
    }

    /**
     * Hydrate model from database row
     */
    private function hydrate(array $data): self
    {
        $this->id = (int) $data['id'];
        $this->userId = (int) $data['user_id'];
        $this->productId = (int) $data['product_id'];
        $this->quantity = (int) $data['quantity'];
        $this->unitPrice = (float) $data['unit_price'];
        $this->totalAmount = (float) $data['total_amount'];
        $this->transactionDate = $data['transaction_date'];
        $this->status = $data['status'];
        $this->paymentMethod = $data['payment_method'];
        $this->notes = $data['notes'];

        return $this;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->userId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_amount' => $this->totalAmount,
            'transaction_date' => $this->transactionDate,
            'status' => $this->status,
            'payment_method' => $this->paymentMethod,
            'notes' => $this->notes,
        ];

        if ($this->user) {
            $data['user'] = $this->user->toArray();
        }

        if ($this->product) {
            $data['product'] = $this->product->toArray();
        }

        return $data;
    }
}
