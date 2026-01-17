<?php
/**
 * Home Controller
 * 
 * Handles the home page.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Services\AuthService;

class HomeController extends Controller
{
    private Product $productModel;
    private AuthService $authService;

    public function __construct(
        ?Product $productModel = null,
        ?AuthService $authService = null
    ) {
        $this->productModel = $productModel ?? new Product();
        $this->authService = $authService ?? new AuthService();
    }

    /**
     * Display the home page
     * Route: GET /
     */
    public function index(): void
    {
        // Redirect admins to products page
        if ($this->authService->isAdmin()) {
            $this->redirect('/products');
            return;
        }

        // Get featured products (first 6 active products)
        $result = $this->productModel->paginate(1, 6, 'name', 'ASC', true);

        $this->view('home.index', [
            'products' => $result['data'],
            'title' => 'Welcome to Vending Machine',
            'user' => $this->authService->user(),
        ]);
    }
}
