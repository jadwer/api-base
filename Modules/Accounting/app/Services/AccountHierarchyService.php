<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\Account;

/**
 * AccountHierarchyService
 *
 * AC-007: Validates account hierarchy to prevent circular references
 * and enforce maximum depth constraints.
 */
class AccountHierarchyService
{
    /**
     * Validate no circular reference in account hierarchy
     *
     * @param int $accountId Account being modified
     * @param int|null $newParentId New parent account ID
     * @throws \Exception If circular reference detected
     */
    public function validateNoCircularReference(int $accountId, ?int $newParentId): void
    {
        if (!$newParentId) {
            return; // No parent, no issue
        }

        if ($accountId === $newParentId) {
            throw new \Exception('Account cannot be its own parent');
        }

        // Traverse up the parent chain
        $currentParentId = $newParentId;
        $visited = [$accountId];
        $maxIterations = 100; // Safety limit to prevent infinite loops
        $iterations = 0;

        while ($currentParentId && $iterations < $maxIterations) {
            if (in_array($currentParentId, $visited)) {
                throw new \Exception('Circular reference detected in account hierarchy');
            }

            $visited[] = $currentParentId;
            $parent = Account::find($currentParentId);

            if (!$parent) {
                throw new \Exception("Parent account #{$currentParentId} not found");
            }

            $currentParentId = $parent->parent_id;
            $iterations++;
        }

        if ($iterations >= $maxIterations) {
            throw new \Exception('Account hierarchy is too deep or has circular reference');
        }
    }

    /**
     * Validate hierarchy doesn't exceed max depth
     *
     * @param int $accountId Account ID to validate
     * @param int $maxDepth Maximum allowed depth (default: 5)
     * @throws \Exception If max depth exceeded
     */
    public function validateMaxDepth(int $accountId, int $maxDepth = 5): void
    {
        $depth = $this->calculateDepth($accountId);

        if ($depth > $maxDepth) {
            throw new \Exception("Account hierarchy exceeds maximum depth of {$maxDepth} levels (current: {$depth})");
        }
    }

    /**
     * Calculate depth of account in hierarchy
     *
     * @param int $accountId Account ID
     * @return int Depth level (1 = root)
     */
    public function calculateDepth(int $accountId): int
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        $depth = 1;
        $currentParentId = $account->parent_id;
        $maxIterations = 100; // Safety limit
        $iterations = 0;

        while ($currentParentId && $iterations < $maxIterations) {
            $depth++;
            $parent = Account::find($currentParentId);

            if (!$parent) {
                break;
            }

            $currentParentId = $parent->parent_id;
            $iterations++;
        }

        return $depth;
    }

    /**
     * Get full hierarchy path for account
     *
     * @param int $accountId Account ID
     * @return array Array of account details from root to target
     */
    public function getHierarchyPath(int $accountId): array
    {
        $path = [];
        $account = Account::find($accountId);

        if (!$account) {
            return $path;
        }

        $maxIterations = 100; // Safety limit
        $iterations = 0;

        while ($account && $iterations < $maxIterations) {
            $path[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'level' => $account->level,
                'parent_id' => $account->parent_id,
            ];

            if (!$account->parent_id) {
                break;
            }

            $account = Account::find($account->parent_id);
            $iterations++;
        }

        return array_reverse($path);
    }

    /**
     * Get all child accounts (recursive)
     *
     * @param int $accountId Parent account ID
     * @return array Array of child account IDs
     */
    public function getAllChildIds(int $accountId): array
    {
        $children = [];
        $directChildren = Account::where('parent_id', $accountId)->pluck('id')->toArray();

        foreach ($directChildren as $childId) {
            $children[] = $childId;
            $children = array_merge($children, $this->getAllChildIds($childId));
        }

        return $children;
    }

    /**
     * Check if account has any children
     *
     * @param int $accountId Account ID
     * @return bool
     */
    public function hasChildren(int $accountId): bool
    {
        return Account::where('parent_id', $accountId)->exists();
    }

    /**
     * Validate account can be deleted
     *
     * @param int $accountId Account ID
     * @throws \Exception If account has children or transactions
     */
    public function validateCanDelete(int $accountId): void
    {
        $account = Account::find($accountId);

        if (!$account) {
            throw new \Exception("Account #{$accountId} not found");
        }

        // Check if has children
        if ($this->hasChildren($accountId)) {
            $childCount = Account::where('parent_id', $accountId)->count();
            throw new \Exception(
                "Cannot delete account with {$childCount} child account(s). Delete or reassign children first."
            );
        }

        // Check if has transactions (journal entry lines)
        if ($account->journalEntryLines()->exists()) {
            $transactionCount = $account->journalEntryLines()->count();
            throw new \Exception(
                "Cannot delete account with {$transactionCount} transaction(s). Account has been used in journal entries."
            );
        }
    }
}
