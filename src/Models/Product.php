<?php
/**
 * Product Model
 * 
 * Represents a product in the vending machine.
 */

namespace App\Models;

use App\Core\Database;

class Product
{
    private Database $db;
    private string $table = 'products';

    public int $id;
    public string $name;
    public ?string $description;
    public float $price;
    public int $quantityAvailable;
    public ?string $imageUrl;
    public ?string $createdAt;
    public ?string $updatedAt;
    public bool $isActive;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Find product by ID
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
     * Get all products
     */
    public function all(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";

        $results = $this->db->fetchAll($sql);
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Get products with pagination and sorting
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10,
        string $sortBy = 'name',
        string $sortOrder = 'ASC',
        bool $activeOnly = true
    ): array {
        $offset = ($page - 1) * $perPage;
        
        // Validate sort column
        $allowedColumns = ['id', 'name', 'price', 'quantity_available', 'created_at'];
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'name';
        }
        
        // Validate sort order
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table}";
        if ($activeOnly) {
            $countSql .= " WHERE is_active = 1";
        }
        $total = (int) $this->db->fetchColumn($countSql);

        // Get paginated results
        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY {$sortBy} {$sortOrder} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $products = array_map(fn($data) => (new self($this->db))->hydrate($data), $results);

        return [
            'data' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * Create a new product
     */
    public function create(array $data): self
    {
        $id = $this->db->insert($this->table, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'quantity_available' => $data['quantity_available'] ?? 0,
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->find($id);
    }

    /**
     * Update the product
     */
    public function update(array $data): bool
    {
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['price'])) {
            $updateData['price'] = $data['price'];
        }
        if (isset($data['quantity_available'])) {
            $updateData['quantity_available'] = $data['quantity_available'];
        }
        if (isset($data['image_url'])) {
            $updateData['image_url'] = $data['image_url'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
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
     * Delete the product
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
     * Soft delete (deactivate) the product
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $change): bool
    {
        $newQuantity = $this->quantityAvailable + $change;
        
        if ($newQuantity < 0) {
            return false;
        }

        $result = $this->db->query(
            "UPDATE {$this->table} SET quantity_available = :qty WHERE id = :id",
            ['qty' => $newQuantity, 'id' => $this->id]
        )->rowCount() > 0;

        if ($result) {
            $this->quantityAvailable = $newQuantity;
        }

        return $result;
    }

    /**
     * Check if product is in stock
     */
    public function inStock(int $quantity = 1): bool
    {
        return $this->quantityAvailable >= $quantity;
    }

    /**
     * Search products
     */
    public function search(string $query, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE :name_query OR description LIKE :desc_query)";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY name ASC";

        $searchTerm = "%{$query}%";
        $results = $this->db->fetchAll($sql, [
            'name_query' => $searchTerm,
            'desc_query' => $searchTerm
        ]);
        
        return array_map(fn($data) => (new self($this->db))->hydrate($data), $results);
    }

    /**
     * Hydrate model from database row
     */
    private function hydrate(array $data): self
    {
        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->price = (float) $data['price'];
        $this->quantityAvailable = (int) $data['quantity_available'];
        $this->imageUrl = $data['image_url'];
        $this->createdAt = $data['created_at'];
        $this->updatedAt = $data['updated_at'];
        $this->isActive = (bool) $data['is_active'];

        return $this;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'quantity_available' => $this->quantityAvailable,
            'image_url' => $this->imageUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'is_active' => $this->isActive,
        ];
    }
}
