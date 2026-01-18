<?php
/**
 * API Transactions Controller
 * 
 * RESTful API for transaction management.
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Transaction;
use App\Models\User;

class TransactionsApiController extends Controller
{
    private Transaction $transactionModel;
    private User $userModel;
    private Request $request;

    public function __construct(
        Transaction $transactionModel,
        User $userModel,
        Request $request
    ) {
        $this->transactionModel = $transactionModel;
        $this->userModel = $userModel;
        $this->request = $request;

        header('Content-Type: application/json');
    }

    /**
     * Get transactions (users see own, admins see all)
     * GET /api/transactions
     */
    public function index(): void
    {
        $authUser = $this->request->user();
        if (!$authUser) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $page = (int) ($this->input('page') ?? 1);
        $perPage = (int) ($this->input('per_page') ?? 10);

        $userId = $authUser['id'];
        $isAdmin = $authUser['role'] === 'admin';

        $result = $this->transactionModel->paginate(
            $page,
            $perPage,
            $isAdmin ? null : $userId
        );

        $this->json([
            'success' => true,
            'data' => array_map(fn($t) => $t->toArray(), $result['data']),
            'meta' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ]
        ]);
    }

    /**
     * Get single transaction
     * GET /api/transactions/{id}
     */
    public function show(string $id): void
    {
        $id = (int) $id;
        $authUser = $this->request->user();
        if (!$authUser) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $transaction = $this->transactionModel->findWithRelations($id);

        if (!$transaction) {
            $this->json([
                'success' => false,
                'error' => 'Transaction not found'
            ], 404);
            return;
        }

        // Check authorization
        $isAdmin = $authUser['role'] === 'admin';
        if (!$isAdmin && $transaction->userId !== $authUser['id']) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
            return;
        }

        $this->json([
            'success' => true,
            'data' => $transaction->toArray()
        ]);
    }

    /**
     * Get user's balance
     * GET /api/balance
     */
    public function balance(): void
    {
        $authUser = $this->request->user();
        if (!$authUser) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $user = $this->userModel->find($authUser['id']);

        if (!$user) {
            $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
            return;
        }

        $this->json([
            'success' => true,
            'data' => [
                'balance' => $user->balance,
                'currency' => 'USD'
            ]
        ]);
    }
}
