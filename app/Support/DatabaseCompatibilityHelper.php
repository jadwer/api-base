<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

/**
 * Database Compatibility Helper
 *
 * Provides database-agnostic SQL functions that work across:
 * - MySQL/MariaDB
 * - SQLite
 * - PostgreSQL
 * - Oracle (basic support)
 *
 * Usage:
 *   use App\Support\DatabaseCompatibilityHelper as DBHelper;
 *
 *   $query->orderByRaw(DBHelper::orderByPriority('priority', ['urgent', 'high', 'normal', 'low']));
 *   $query->selectRaw(DBHelper::formatDate('created_at', '%Y-%m') . ' as month');
 */
class DatabaseCompatibilityHelper
{
    /**
     * Get current database driver name
     */
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Check if using specific driver
     */
    public static function isMySQL(): bool
    {
        return in_array(static::getDriver(), ['mysql', 'mariadb']);
    }

    public static function isSQLite(): bool
    {
        return static::getDriver() === 'sqlite';
    }

    public static function isPostgreSQL(): bool
    {
        return in_array(static::getDriver(), ['pgsql', 'postgres']);
    }

    public static function isOracle(): bool
    {
        return static::getDriver() === 'oracle';
    }

    /**
     * ORDER BY custom priority (replaces MySQL FIELD())
     *
     * MySQL:      FIELD(column, 'a', 'b', 'c')
     * Standard:   CASE WHEN column = 'a' THEN 0 WHEN column = 'b' THEN 1 ... END
     *
     * @param string $column Column name
     * @param array $values Ordered values (first = highest priority)
     * @param string $direction ASC or DESC
     * @return string SQL expression
     */
    public static function orderByPriority(string $column, array $values, string $direction = 'ASC'): string
    {
        if (static::isMySQL()) {
            $escaped = array_map(fn($v) => "'" . addslashes($v) . "'", $values);
            return "FIELD({$column}, " . implode(', ', $escaped) . ") {$direction}";
        }

        // Standard SQL CASE WHEN (works on SQLite, PostgreSQL, Oracle)
        $cases = [];
        foreach ($values as $index => $value) {
            $escaped = addslashes($value);
            $cases[] = "WHEN {$column} = '{$escaped}' THEN {$index}";
        }

        return "CASE " . implode(' ', $cases) . " ELSE " . count($values) . " END {$direction}";
    }

    /**
     * Format date to string (replaces MySQL DATE_FORMAT())
     *
     * MySQL:      DATE_FORMAT(column, '%Y-%m')
     * SQLite:     strftime('%Y-%m', column)
     * PostgreSQL: TO_CHAR(column, 'YYYY-MM')
     *
     * @param string $column Column name
     * @param string $format MySQL format string
     * @return string SQL expression
     */
    public static function formatDate(string $column, string $format): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "DATE_FORMAT({$column}, '{$format}')";
        }

        if ($driver === 'sqlite') {
            // Convert MySQL format to SQLite strftime format
            $sqliteFormat = static::convertToSqliteFormat($format);
            return "strftime('{$sqliteFormat}', {$column})";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            // Convert MySQL format to PostgreSQL TO_CHAR format
            $pgFormat = static::convertToPostgresFormat($format);
            return "TO_CHAR({$column}, '{$pgFormat}')";
        }

        if ($driver === 'oracle') {
            $oracleFormat = static::convertToOracleFormat($format);
            return "TO_CHAR({$column}, '{$oracleFormat}')";
        }

        // Fallback to MySQL format
        return "DATE_FORMAT({$column}, '{$format}')";
    }

    /**
     * Extract year from date
     */
    public static function year(string $column): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "YEAR({$column})";
        }

        if ($driver === 'sqlite') {
            return "CAST(strftime('%Y', {$column}) AS INTEGER)";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "EXTRACT(YEAR FROM {$column})";
        }

        return "YEAR({$column})";
    }

    /**
     * Extract month from date
     */
    public static function month(string $column): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "MONTH({$column})";
        }

        if ($driver === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "EXTRACT(MONTH FROM {$column})";
        }

        return "MONTH({$column})";
    }

    /**
     * Extract date part only (no time)
     */
    public static function dateOnly(string $column): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "DATE({$column})";
        }

        if ($driver === 'sqlite') {
            return "date({$column})";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "{$column}::date";
        }

        return "DATE({$column})";
    }

    /**
     * COALESCE with proper null handling for dates
     *
     * @param string $column Column to check
     * @param string $default Default value if null
     * @return string SQL expression
     */
    public static function coalesceDate(string $column, string $default = '9999-12-31'): string
    {
        $driver = static::getDriver();

        if ($driver === 'sqlite') {
            // SQLite needs date in quotes
            return "COALESCE({$column}, '{$default}')";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "COALESCE({$column}, DATE '{$default}')";
        }

        // MySQL and others
        return "COALESCE({$column}, '{$default}')";
    }

    /**
     * Get current timestamp
     */
    public static function now(): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return 'NOW()';
        }

        if ($driver === 'sqlite') {
            return "datetime('now')";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return 'NOW()';
        }

        if ($driver === 'oracle') {
            return 'SYSDATE';
        }

        return 'NOW()';
    }

    /**
     * Get current date (without time)
     */
    public static function currentDate(): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return 'CURDATE()';
        }

        if ($driver === 'sqlite') {
            return "date('now')";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return 'CURRENT_DATE';
        }

        if ($driver === 'oracle') {
            return 'TRUNC(SYSDATE)';
        }

        return 'CURDATE()';
    }

    /**
     * Concatenate strings
     */
    public static function concat(array $parts): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return 'CONCAT(' . implode(', ', $parts) . ')';
        }

        if ($driver === 'sqlite' || in_array($driver, ['pgsql', 'postgres'])) {
            return '(' . implode(' || ', $parts) . ')';
        }

        if ($driver === 'oracle') {
            return '(' . implode(' || ', $parts) . ')';
        }

        return 'CONCAT(' . implode(', ', $parts) . ')';
    }

    /**
     * Group concatenation
     */
    public static function groupConcat(string $column, string $separator = ','): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
        }

        if ($driver === 'sqlite') {
            return "GROUP_CONCAT({$column}, '{$separator}')";
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "STRING_AGG({$column}::text, '{$separator}')";
        }

        return "GROUP_CONCAT({$column} SEPARATOR '{$separator}')";
    }

    /**
     * If null replacement
     */
    public static function ifNull(string $column, string $default): string
    {
        $driver = static::getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            return "IFNULL({$column}, {$default})";
        }

        // COALESCE is standard SQL and works everywhere
        return "COALESCE({$column}, {$default})";
    }

    /**
     * Boolean to integer (for compatibility)
     */
    public static function boolToInt(string $column): string
    {
        $driver = static::getDriver();

        if ($driver === 'sqlite') {
            // SQLite stores booleans as 0/1 already
            return $column;
        }

        if (in_array($driver, ['pgsql', 'postgres'])) {
            return "CAST({$column} AS INTEGER)";
        }

        return $column;
    }

    /**
     * Convert MySQL date format to SQLite strftime format
     */
    protected static function convertToSqliteFormat(string $mysqlFormat): string
    {
        $replacements = [
            '%Y' => '%Y',  // 4-digit year
            '%y' => '%y',  // 2-digit year
            '%m' => '%m',  // Month (01-12)
            '%d' => '%d',  // Day (01-31)
            '%H' => '%H',  // Hour (00-23)
            '%i' => '%M',  // Minutes (MySQL %i -> SQLite %M)
            '%s' => '%S',  // Seconds
            '%M' => '%m',  // Month name -> just month number in SQLite
            '%D' => '%d',  // Day with suffix -> just day in SQLite
            '%W' => '%w',  // Weekday name -> weekday number
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $mysqlFormat);
    }

    /**
     * Convert MySQL date format to PostgreSQL TO_CHAR format
     */
    protected static function convertToPostgresFormat(string $mysqlFormat): string
    {
        $replacements = [
            '%Y' => 'YYYY',  // 4-digit year
            '%y' => 'YY',    // 2-digit year
            '%m' => 'MM',    // Month (01-12)
            '%d' => 'DD',    // Day (01-31)
            '%H' => 'HH24',  // Hour (00-23)
            '%i' => 'MI',    // Minutes
            '%s' => 'SS',    // Seconds
            '%M' => 'Month', // Month name
            '%W' => 'Day',   // Weekday name
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $mysqlFormat);
    }

    /**
     * Convert MySQL date format to Oracle TO_CHAR format
     */
    protected static function convertToOracleFormat(string $mysqlFormat): string
    {
        $replacements = [
            '%Y' => 'YYYY',  // 4-digit year
            '%y' => 'YY',    // 2-digit year
            '%m' => 'MM',    // Month (01-12)
            '%d' => 'DD',    // Day (01-31)
            '%H' => 'HH24',  // Hour (00-23)
            '%i' => 'MI',    // Minutes
            '%s' => 'SS',    // Seconds
            '%M' => 'Month', // Month name
            '%W' => 'Day',   // Weekday name
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $mysqlFormat);
    }

    /**
     * Raw expression that adapts to the current driver
     * Use for complex cases not covered by other methods
     */
    public static function raw(array $expressions): string
    {
        $driver = static::getDriver();

        if (isset($expressions[$driver])) {
            return $expressions[$driver];
        }

        if (isset($expressions['default'])) {
            return $expressions['default'];
        }

        // Fallback order: mysql -> pgsql -> sqlite -> first available
        foreach (['mysql', 'pgsql', 'sqlite'] as $fallback) {
            if (isset($expressions[$fallback])) {
                return $expressions[$fallback];
            }
        }

        return array_values($expressions)[0] ?? '';
    }
}
