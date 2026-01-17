<?php
/**
 * API Products Controller
 * 
 * RESTful API for product management.
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;

class ProductsApiController extends Controller
{
    private Product $productModel;
    private Transaction $transactionModel;
    private User $userModel;
    private Database $db;

    public function __construct(
        ?Product $productModel = null,
        ?Transaction $transactionModel = null,
        ?User $userModel = null,
        ?Database $db = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->productModel = $productModel ?? new Product($this->db);
        $this->transactionModel = $transactionModel ?? new Transaction($this->db);
        $this->userModel = $userModel ?? new User($this->db);

        // Set JSON content type
        header('Content-Type: application/json');
    }

    /**
     * Get all products
     * GET /api/products
     */
    public function index(): void
    {
        $page = (int) ($this->input('page') ?? 1);
        $perPage = (int) ($this->input('per_page') ?? 10);
        $sortBy = $this->input('sort_by') ?? 'name';
        $sortOrder = $this->input('sort_order') ?? 'ASC';
        $search = $this->input('search');

        if ($search) {
            $products = $this->productModel->search($search);
            $this->json([
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $products),
                'meta' => [
                    'total' => count($products),
                ]
            ]);
            return;
        }

        $result = $this->productModel->paginate($page, $perPage, $sortBy, $sortOrder);

        $this->json([
            'success' => true,
            'data' => array_map(fn($p) => $p->toArray(), $result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'from' => $result['from'],
                'to' => $result['to'],
            ],
            'links' => [
                'first' => "/api/products?page=1&per_page={$perPage}",
                'last' => "/api/products?page={$result['last_page']}&per_page={$perPage}",
                'prev' => $result['current_page'] > 1 ? "/api/products?page=" . ($result['current_page'] - 1) . "&per_page={$perPage}" : null,
                'next' => $result['current_page'] < $result['last_page'] ? "/api/products?page=" . ($result['current_page'] + 1) . "&per_page={$perPage}" : null,
            ]
        ]);
    }

    /**
     * Get single product
     * GET /api/products/{id}
     */
    public function show(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
            return;
        }

        $this->json([
            'success' => true,
            'data' => $product->toArray()
        ]);
    }

    /**
     * Create a new product (Admin only)
     * POST /api/products
     */
    public function store(): void
    {
        $data = $this->input();
        
        // Validate input
        $errors = $this->validateProductInput($data);
        
        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
            return;
        }

        try {
            $product = $this->productModel->create([
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'price' => (float) $data['price'],
                'quantity_available' => (int) $data['quantity_available'],
                'image_url' => $data['image_url'] ?? null,
            ]);

            $this->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to create product'
            ], 500);
        }
    }

    /**
     * Update a product (Admin only)
     * PUT /api/products/{id}
     */
    public function update(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
            return;
        }

        $data = $this->input();
        
        // Validate input
        $errors = $this->validateProductInput($data, true);
        
        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
            return;
        }

        try {
            $updateData = [];
            
            if (isset($data['name'])) {
                $updateData['name'] = trim($data['name']);
            }
            if (isset($data['description'])) {
                $updateData['description'] = trim($data['description']);
            }
            if (isset($data['price'])) {
                $updateData['price'] = (float) $data['price'];
            }
            if (isset($data['quantity_available'])) {
                $updateData['quantity_available'] = (int) $data['quantity_available'];
            }
            if (isset($data['image_url'])) {
                $updateData['image_url'] = $data['image_url'];
            }
            if (isset($data['is_active'])) {
                $updateData['is_active'] = (bool) $data['is_active'];
            }

            $product->update($updateData);

            $this->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $this->productModel->find($id)->toArray()
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to update product'
            ], 500);
        }
    }

    /**
     * Delete a product (Admin only)
     * DELETE /api/products/{id}
     */
    public function destroy(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
            return;
        }

        try {
            $product->deactivate();

            $this->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Failed to delete product'
            ], 500);
        }
    }

    /**
     * Purchase a product
     * POST /api/products/{id}/purchase
     */
    public function purchase(string $id): void
    {
        $id = (int) $id;
        // Get authenticated user from API middleware
        if (!isset($GLOBALS['api_user'])) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $userId = $GLOBALS['api_user']['id'];
        $product = $this->productModel->find($id);

        if (!$product) {
            $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
            return;
        }

        $quantity = (int) ($this->input('quantity') ?? 1);

        // Validate quantity
        if ($quantity < 1) {
            $this->json([
                'success' => false,
                'error' => 'Invalid quantity'
            ], 422);
            return;
        }

        // Check if product is active
        if (!$product->isActive) {
            $this->json([
                'success' => false,
                'error' => 'Product is not available'
            ], 400);
            return;
        }

        // Check stock
        if (!$product->inStock($quantity)) {
            $this->json([
                'success' => false,
                'error' => "Insufficient stock. Only {$product->quantityAvailable} available."
            ], 400);
            return;
        }

        // Calculate total
        $totalAmount = $product->price * $quantity;

        // Get user and check balance
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
            return;
        }

        if ($user->balance < $totalAmount) {
            $this->json([
                'success' => false,
                'error' => "Insufficient balance. You have \${$user->balance}, but need \${$totalAmount}."
            ], 400);
            return;
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Update product quantity
            if (!$product->updateQuantity(-$quantity)) {
                throw new \Exception('Failed to update product quantity');
            }

            // Update user balance
            if (!$user->updateBalance(-$totalAmount)) {
                throw new \Exception('Failed to update user balance');
            }

            // Log the transaction
            $transaction = $this->transactionModel->create([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'payment_method' => 'balance',
            ]);

            // Commit transaction
            $this->db->commit();

            $this->json([
                'success' => true,
                'message' => 'Purchase successful',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                    ],
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_amount' => $totalAmount,
                    'new_balance' => $user->balance,
                ]
            ]);

        } catch (\Exception $e) {
            $this->db->rollback();

            $this->json([
                'success' => false,
                'error' => 'Purchase failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate product input
     */
    private function validateProductInput(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Product name is required';
            } elseif (strlen($data['name']) > 100) {
                $errors['name'] = 'Product name must not exceed 100 characters';
            }
        }

        if (!$isUpdate || isset($data['price'])) {
            if (!isset($data['price']) || $data['price'] === '') {
                $errors['price'] = 'Price is required';
            } elseif (!is_numeric($data['price'])) {
                $errors['price'] = 'Price must be a number';
            } elseif ((float) $data['price'] <= 0) {
                $errors['price'] = 'Price must be positive';
            }
        }

        if (!$isUpdate || isset($data['quantity_available'])) {
            if (!isset($data['quantity_available']) || $data['quantity_available'] === '') {
                $errors['quantity_available'] = 'Quantity is required';
            } elseif (!is_numeric($data['quantity_available'])) {
                $errors['quantity_available'] = 'Quantity must be a number';
            } elseif ((int) $data['quantity_available'] < 0) {
                $errors['quantity_available'] = 'Quantity must be non-negative';
            }
        }

        return $errors;
    }
}
