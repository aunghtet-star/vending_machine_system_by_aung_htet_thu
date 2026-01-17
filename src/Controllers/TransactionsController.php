<?php
/**
 * Transactions Controller
 * 
 * Handles viewing transaction history.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Transaction;
use App\Services\AuthService;

class TransactionsController extends Controller
{
    private Transaction $transactionModel;
    private AuthService $authService;

    public function __construct(
        ?Transaction $transactionModel = null,
        ?AuthService $authService = null
    ) {
        $this->transactionModel = $transactionModel ?? new Transaction();
        $this->authService = $authService ?? new AuthService();
    }

    /**
     * Display user's transaction history
     * Route: GET /transactions
     */
    public function index(): void
    {
        $page = (int) ($this->input('page') ?? 1);
        $perPage = (int) ($this->input('per_page') ?? 10);

        $userId = $this->authService->id();
        $isAdmin = $this->authService->isAdmin();

        // Admins see all transactions, users see only their own
        $result = $this->transactionModel->paginate(
            $page, 
            $perPage, 
            $isAdmin ? null : $userId
        );

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'data' => array_map(fn($t) => $t->toArray(), $result['data']),
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                ]
            ]);
            return;
        }

        $this->view('transactions.index', [
            'transactions' => $result['data'],
            'pagination' => $result,
            'title' => 'Transaction History',
        ]);
    }

    /**
     * Display single transaction details
     * Route: GET /transactions/{id}
     */
    public function show(string $id): void
    {
        $id = (int) $id;
        $transaction = $this->transactionModel->findWithRelations($id);

        if (!$transaction) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Transaction not found'], 404);
                return;
            }
            
            Session::flash('error', 'Transaction not found');
            $this->redirect('/transactions');
            return;
        }

        // Check authorization (users can only view their own)
        if (!$this->authService->isAdmin() && $transaction->userId !== $this->authService->id()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
                return;
            }
            
            Session::flash('error', 'Unauthorized');
            $this->redirect('/transactions');
            return;
        }

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $transaction->toArray()]);
            return;
        }

        $this->view('transactions.show', [
            'transaction' => $transaction,
            'title' => 'Transaction #' . $transaction->id,
        ]);
    }
}
