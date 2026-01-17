<?php
/**
 * API Transactions Controller
 * 
 * RESTful API for transaction management.
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Transaction;
use App\Models\User;

class TransactionsApiController extends Controller
{
    private Transaction $transactionModel;
    private User $userModel;

    public function __construct(
        ?Transaction $transactionModel = null,
        ?User $userModel = null
    ) {
        $this->transactionModel = $transactionModel ?? new Transaction();
        $this->userModel = $userModel ?? new User();

        header('Content-Type: application/json');
    }

    /**
     * Get transactions (users see own, admins see all)
     * GET /api/transactions
     */
    public function index(): void
    {
        if (!isset($GLOBALS['api_user'])) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $page = (int) ($this->input('page') ?? 1);
        $perPage = (int) ($this->input('per_page') ?? 10);

        $userId = $GLOBALS['api_user']['id'];
        $isAdmin = $GLOBALS['api_user']['role'] === 'admin';

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
        if (!isset($GLOBALS['api_user'])) {
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
        $isAdmin = $GLOBALS['api_user']['role'] === 'admin';
        if (!$isAdmin && $transaction->userId !== $GLOBALS['api_user']['id']) {
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
        if (!isset($GLOBALS['api_user'])) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $user = $this->userModel->find($GLOBALS['api_user']['id']);

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
