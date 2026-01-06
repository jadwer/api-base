<?php

namespace Modules\Contacts\Services;

use Modules\Contacts\Models\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CO-M001: Duplicate Contact Detection Service
 *
 * Detects potential duplicate contacts based on:
 * - Exact match: tax_id (RFC), email
 * - Fuzzy match: name similarity
 *
 * Business Rules:
 * - tax_id match is considered a definite duplicate
 * - email match is considered a probable duplicate
 * - name similarity > 80% is considered a possible duplicate
 */
class DuplicateDetectionService
{
    /**
     * Similarity threshold for fuzzy name matching (0-100)
     */
    private const NAME_SIMILARITY_THRESHOLD = 80;

    /**
     * Find exact duplicate by tax_id (RFC)
     *
     * @param string $taxId
     * @param int|null $excludeId Contact ID to exclude from search
     * @return Contact|null
     */
    public function findByTaxId(string $taxId, ?int $excludeId = null): ?Contact
    {
        if (empty($taxId)) {
            return null;
        }

        $query = Contact::where('tax_id', $taxId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Find contacts with same email
     *
     * @param string $email
     * @param int|null $excludeId Contact ID to exclude from search
     * @return Collection
     */
    public function findByEmail(string $email, ?int $excludeId = null): Collection
    {
        if (empty($email)) {
            return collect();
        }

        $query = Contact::where('email', strtolower($email));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * Find contacts with similar names using fuzzy matching
     *
     * @param string $name
     * @param int|null $excludeId Contact ID to exclude from search
     * @param int $limit Maximum number of results
     * @return Collection
     */
    public function findSimilarByName(string $name, ?int $excludeId = null, int $limit = 10): Collection
    {
        if (empty($name) || strlen($name) < 3) {
            return collect();
        }

        $normalizedName = $this->normalizeName($name);

        $query = Contact::query();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Get candidates using LIKE for performance
        $candidates = $query
            ->where(function ($q) use ($normalizedName) {
                // Search in both name and legal_name
                $q->where('name', 'LIKE', '%' . substr($normalizedName, 0, 3) . '%')
                  ->orWhere('legal_name', 'LIKE', '%' . substr($normalizedName, 0, 3) . '%');
            })
            ->limit(100)
            ->get();

        // Apply fuzzy matching
        return $candidates
            ->map(function ($contact) use ($normalizedName) {
                $contactName = $this->normalizeName($contact->name);
                $similarity = $this->calculateSimilarity($normalizedName, $contactName);

                // Also check legal_name if different
                if ($contact->legal_name && $contact->legal_name !== $contact->name) {
                    $legalSimilarity = $this->calculateSimilarity(
                        $normalizedName,
                        $this->normalizeName($contact->legal_name)
                    );
                    $similarity = max($similarity, $legalSimilarity);
                }

                $contact->similarity_score = $similarity;
                return $contact;
            })
            ->filter(fn ($contact) => $contact->similarity_score >= self::NAME_SIMILARITY_THRESHOLD)
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values();
    }

    /**
     * Detect all potential duplicates for a contact
     *
     * @param Contact $contact
     * @return array Array with 'definite', 'probable', 'possible' keys
     */
    public function detectDuplicates(Contact $contact): array
    {
        $result = [
            'definite' => [],   // Same tax_id
            'probable' => [],   // Same email
            'possible' => [],   // Similar name
        ];

        // Check tax_id duplicates (definite)
        if ($contact->tax_id) {
            $taxIdDuplicate = $this->findByTaxId($contact->tax_id, $contact->id);
            if ($taxIdDuplicate) {
                $result['definite'][] = [
                    'contact' => $taxIdDuplicate,
                    'match_type' => 'tax_id',
                    'match_value' => $contact->tax_id,
                ];
            }
        }

        // Check email duplicates (probable)
        if ($contact->email) {
            $emailDuplicates = $this->findByEmail($contact->email, $contact->id);
            foreach ($emailDuplicates as $duplicate) {
                $result['probable'][] = [
                    'contact' => $duplicate,
                    'match_type' => 'email',
                    'match_value' => $contact->email,
                ];
            }
        }

        // Check name similarity (possible)
        $nameDuplicates = $this->findSimilarByName($contact->name, $contact->id, 5);
        foreach ($nameDuplicates as $duplicate) {
            // Skip if already in definite or probable
            if ($this->isAlreadyMatched($duplicate->id, $result)) {
                continue;
            }

            $result['possible'][] = [
                'contact' => $duplicate,
                'match_type' => 'name_similarity',
                'match_value' => $duplicate->name,
                'similarity_score' => $duplicate->similarity_score,
            ];
        }

        return $result;
    }

    /**
     * Check if contact has any duplicates
     *
     * @param Contact $contact
     * @return bool
     */
    public function hasDuplicates(Contact $contact): bool
    {
        $duplicates = $this->detectDuplicates($contact);

        return !empty($duplicates['definite'])
            || !empty($duplicates['probable'])
            || !empty($duplicates['possible']);
    }

    /**
     * Check if a tax_id already exists (for validation)
     *
     * @param string $taxId
     * @param int|null $excludeId
     * @return bool
     */
    public function taxIdExists(string $taxId, ?int $excludeId = null): bool
    {
        return $this->findByTaxId($taxId, $excludeId) !== null;
    }

    /**
     * Normalize name for comparison
     *
     * @param string $name
     * @return string
     */
    private function normalizeName(string $name): string
    {
        // Lowercase
        $name = mb_strtolower($name);

        // Remove common legal suffixes
        $suffixes = [
            ' s.a. de c.v.', ' sa de cv', ' s.a.', ' sa',
            ' s. de r.l.', ' s de rl', ' srl',
            ' s.c.', ' sc', ' a.c.', ' ac',
            ' inc.', ' inc', ' corp.', ' corp',
            ' ltd.', ' ltd', ' llc', ' gmbh',
        ];
        $name = str_replace($suffixes, '', $name);

        // Remove accents
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        // Remove special characters
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);

        // Normalize whitespace
        $name = preg_replace('/\s+/', ' ', trim($name));

        return $name;
    }

    /**
     * Calculate similarity between two strings (0-100)
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 100.0;
        }

        // Use similar_text for basic similarity
        similar_text($str1, $str2, $percent);

        // Boost score if one contains the other
        if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
            $percent = min(100, $percent + 10);
        }

        return round($percent, 2);
    }

    /**
     * Check if contact ID is already in matched results
     *
     * @param int $contactId
     * @param array $results
     * @return bool
     */
    private function isAlreadyMatched(int $contactId, array $results): bool
    {
        foreach (['definite', 'probable'] as $category) {
            foreach ($results[$category] as $match) {
                if ($match['contact']->id === $contactId) {
                    return true;
                }
            }
        }
        return false;
    }
}
