<?php
/**
 * Products Controller
 * 
 * Handles CRUD operations for products and the purchasing process.
 * Supports both web views and AJAX requests.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuthService;

class ProductsController extends Controller
{
    private Product $productModel;
    private Transaction $transactionModel;
    private User $userModel;
    private AuthService $authService;
    private Database $db;

    public function __construct(
        ?Product $productModel = null,
        ?Transaction $transactionModel = null,
        ?User $userModel = null,
        ?AuthService $authService = null,
        ?Database $db = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->productModel = $productModel ?? new Product($this->db);
        $this->transactionModel = $transactionModel ?? new Transaction($this->db);
        $this->userModel = $userModel ?? new User($this->db);
        $this->authService = $authService ?? new AuthService($this->db);
    }

    /**
     * Display a listing of products with pagination and sorting
     * Route: GET /products
     */
    public function index(): void
    {
        $page = (int) ($this->input('page') ?? 1);
        $perPage = (int) ($this->input('per_page') ?? 10);
        $sortBy = $this->input('sort_by') ?? 'name';
        $sortOrder = $this->input('sort_order') ?? 'ASC';
        $search = $this->input('search');
        
        // Show inactive products for admins
        $activeOnly = !$this->authService->isAdmin();

        // Validate pagination
        $page = max(1, $page);
        $perPage = min(max(1, $perPage), 100);

        if ($search) {
            $products = $this->productModel->search($search, $activeOnly);
            $result = [
                'data' => $products,
                'total' => count($products),
                'per_page' => $perPage,
                'current_page' => 1,
                'last_page' => 1,
            ];
        } else {
            $result = $this->productModel->paginate($page, $perPage, $sortBy, $sortOrder, $activeOnly);
        }

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $result['data']),
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                ]
            ]);
            return;
        }

        $this->view('products.index', [
            'products' => $result['data'],
            'pagination' => $result,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'search' => $search,
            'title' => 'Products',
        ]);
    }

    /**
     * Show the form for creating a new product (Admin only)
     * Route: GET /products/create
     */
    public function create(): void
    {
        $this->view('products.create', [
            'title' => 'Create Product',
        ]);
    }

    /**
     * Store a newly created product (Admin only)
     * Route: POST /products
     */
    public function store(): void
    {
        $data = $this->input();
        
        // Validate input
        $errors = $this->validateProductInput($data);
        
        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/products/create');
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

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data' => $product->toArray()
                ], 201);
                return;
            }

            Session::flash('success', 'Product created successfully!');
            $this->redirect('/products');
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Failed to create product'], 500);
                return;
            }
            
            Session::flash('error', 'Failed to create product');
            $this->redirect('/products/create');
        }
    }

    /**
     * Display the specified product
     * Route: GET /products/{id}
     */
    public function show(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Product not found'], 404);
                return;
            }
            
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $product->toArray()]);
            return;
        }

        $this->view('products.show', [
            'product' => $product,
            'title' => $product->name,
        ]);
    }

    /**
     * Show the form for editing a product (Admin only)
     * Route: GET /products/{id}/edit
     */
    public function edit(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        $this->view('products.edit', [
            'product' => $product,
            'title' => 'Edit ' . $product->name,
        ]);
    }

    /**
     * Update the specified product (Admin only)
     * Route: PUT /products/{id}
     */
    public function update(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Product not found'], 404);
                return;
            }
            
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        $data = $this->input();
        
        // Validate input
        $errors = $this->validateProductInput($data, true);
        
        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect("/products/{$id}/edit");
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
                $updateData['is_active'] = (int) $data['is_active'];
            }

            $product->update($updateData);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'data' => $this->productModel->find($id)->toArray()
                ]);
                return;
            }

            Session::flash('success', 'Product updated successfully!');
            $this->redirect('/products');
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Failed to update product'], 500);
                return;
            }
            
            Session::flash('error', 'Failed to update product');
            $this->redirect("/products/{$id}/edit");
        }
    }

    /**
     * Remove the specified product (Admin only)
     * Route: DELETE /products/{id}
     */
    public function destroy(string $id): void
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Product not found'], 404);
                return;
            }
            
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        try {
            // Soft delete (deactivate) instead of hard delete
            $product->deactivate();

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Product deleted successfully']);
                return;
            }

            Session::flash('success', 'Product deleted successfully!');
            $this->redirect('/products');
        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Failed to delete product'], 500);
                return;
            }
            
            Session::flash('error', 'Failed to delete product');
            $this->redirect('/products');
        }
    }

    /**
     * Show the purchase form for a product
     * Route: GET /products/{id}/purchase
     */
    public function purchaseForm(string $id): void
    {
        // Prevent admins from purchasing
        if ($this->authService->isAdmin()) {
            Session::flash('error', 'Administrators cannot purchase products.');
            $this->redirect('/products');
            return;
        }

        $id = (int) $id;
        $product = $this->productModel->find($id);

        if (!$product) {
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        if (!$product->isActive || !$product->inStock()) {
            Session::flash('error', 'Product is not available');
            $this->redirect('/products');
            return;
        }

        $user = $this->authService->user();
        $balance = $this->authService->getBalance();

        $this->view('products.purchase', [
            'product' => $product,
            'balance' => $balance,
            'title' => 'Purchase ' . $product->name,
        ]);
    }

    /**
     * Process the purchase of a product
     * Route: POST /products/{id}/purchase
     * 
     * This is the main purchasing function that:
     * 1. Validates the purchase request
     * 2. Checks product availability
     * 3. Verifies user balance
     * 4. Updates product quantity
     * 5. Logs the transaction
     * 6. Updates user balance
     */
    public function purchase(string $id): void
    {
        // Prevent admins from purchasing
        if ($this->authService->isAdmin()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Administrators cannot purchase products.'], 403);
                return;
            }
            Session::flash('error', 'Administrators cannot purchase products.');
            $this->redirect('/products');
            return;
        }

        $id = (int) $id;
        // Get current user
        $userId = $this->authService->id();
        
        if (!$userId) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Please log in to make a purchase'], 401);
                return;
            }
            
            $this->redirect('/login');
            return;
        }

        // Get product
        $product = $this->productModel->find($id);

        if (!$product) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Product not found'], 404);
                return;
            }
            
            Session::flash('error', 'Product not found');
            $this->redirect('/products');
            return;
        }

        // Get quantity from input
        $quantity = (int) ($this->input('quantity') ?? 1);
        
        // Validate quantity
        if ($quantity < 1) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Invalid quantity'], 422);
                return;
            }
            
            Session::flash('error', 'Invalid quantity');
            $this->redirect("/products/{$id}/purchase");
            return;
        }

        // Check if product is active
        if (!$product->isActive) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Product is not available'], 400);
                return;
            }
            
            Session::flash('error', 'Product is not available');
            $this->redirect('/products');
            return;
        }

        // Check stock availability
        if (!$product->inStock($quantity)) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false, 
                    'error' => "Insufficient stock. Only {$product->quantityAvailable} available."
                ], 400);
                return;
            }
            
            Session::flash('error', "Insufficient stock. Only {$product->quantityAvailable} available.");
            $this->redirect("/products/{$id}/purchase");
            return;
        }

        // Calculate total
        $totalAmount = $product->price * $quantity;

        // Get user and check balance
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'User not found'], 404);
                return;
            }
            
            Session::flash('error', 'User not found');
            $this->redirect('/products');
            return;
        }

        if ($user->balance < $totalAmount) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false, 
                    'error' => "Insufficient balance. You have \${$user->balance}, but need \${$totalAmount}."
                ], 400);
                return;
            }
            
            Session::flash('error', "Insufficient balance. You have \${$user->balance}, but need \${$totalAmount}.");
            $this->redirect("/products/{$id}/purchase");
            return;
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // 1. Update product quantity
            if (!$product->updateQuantity(-$quantity)) {
                throw new \Exception('Failed to update product quantity');
            }

            // 2. Update user balance
            if (!$user->updateBalance(-$totalAmount)) {
                throw new \Exception('Failed to update user balance');
            }

            // 3. Log the transaction
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

            // Update session balance
            Session::set('balance', $user->balance);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Purchase successful!',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'product' => $product->name,
                        'quantity' => $quantity,
                        'total_amount' => $totalAmount,
                        'new_balance' => $user->balance,
                    ]
                ]);
                return;
            }

            Session::flash('success', "Purchase successful! You bought {$quantity} x {$product->name} for \${$totalAmount}");
            $this->redirect('/transactions');

        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();

            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Purchase failed: ' . $e->getMessage()], 500);
                return;
            }
            
            Session::flash('error', 'Purchase failed. Please try again.');
            $this->redirect("/products/{$id}/purchase");
        }
    }

    /**
     * Validate product input data
     */
    private function validateProductInput(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Name validation
        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Product name is required';
            } elseif (strlen($data['name']) > 100) {
                $errors['name'] = 'Product name must not exceed 100 characters';
            }
        }

        // Price validation
        if (!$isUpdate || isset($data['price'])) {
            if (!isset($data['price']) || $data['price'] === '') {
                $errors['price'] = 'Price is required';
            } elseif (!is_numeric($data['price'])) {
                $errors['price'] = 'Price must be a number';
            } elseif ((float) $data['price'] <= 0) {
                $errors['price'] = 'Price must be positive';
            }
        }

        // Quantity validation
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
