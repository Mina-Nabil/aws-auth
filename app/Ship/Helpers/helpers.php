<?php

/*
|--------------------------------------------------------------------------
| Ship Helpers
|--------------------------------------------------------------------------
|
| Here is where you can register all the global helpers.
| Write Container specific helpers in the respective Container.
| All the helpers added here and in the Containers will be loaded automatically.
|
*/

if (!function_exists('joined')) {
    /**
     * Check if a query is joined to a given table.
     *
     * @param Builder $query
     * @param string $table
     * @return bool
     */
    function joined($query, $table)
    {
        $joins = $query->getQuery()->joins;
        if ($joins == null) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('generate_uuid')) {
    /**
     * Generate a UUID (Universally Unique Identifier) v4.
     *
     * @return string
     */
    function generate_uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
