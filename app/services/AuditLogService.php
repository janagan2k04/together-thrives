<?php
namespace Services;

use Core\Database;

/**
 * Reads `storage/logs/*.log` back for the System Logs screen, and turns the row
 * ids in each line into the names they stand for.
 *
 * `Logger::audit()` writes ids because that is all the writer has in hand and
 * an id is what stays true - a row can be renamed, and a log that rewrote
 * itself would stop being a record. But "Donation request 19 created by user 1"
 * tells an administrator nothing they can act on, so the NAMES are resolved
 * here, when the line is shown, rather than when it is written.
 *
 * Doing it at read time is also what lets lines already in the file be named,
 * including the login and logout lines - those come from `Core\Auth`, which is
 * frozen, so there is no call site to change even if we wanted to.
 *
 * A name that cannot be found is left exactly as it was written. An alert about
 * a row that has since been hard deleted must still be readable, and a log
 * entry that quietly loses half its text is worse than one showing an id.
 */
final class AuditLogService
{
    /**
     * Phrases `Logger::audit()` writes before a row id, longest first, mapped
     * to the table and column that name that row.
     *
     * Order matters: "donation request 19" has to be matched before "donation
     * 19" would match the same line, so the alternation is built in this order.
     * The five post types come from ContentVerificationService::TYPES - adding
     * a sixth post type there brings its log lines with it.
     */
    private const EXTRA = [
        'donation request' => ['donation_requests', 'title',     'Donation Request'],
        'reuse item'       => ['reuse_items',       'title',     'Reuse Listing'],
        'item'             => ['reuse_items',       'title',     'Reuse Listing'],
        'user'             => ['users',             'full_name', null],
        'admin'            => ['users',             'full_name', null],
        'account'          => ['users',             'full_name', null],
    ];

    /**
     * Tail of one log file, newest first, with ids named.
     *
     * Lines look like `[2026-08-12 01:36:02] AUDIT: GS letter 1 approved by
     * admin 1`. Anything that does not parse is still shown, with an empty
     * timestamp, rather than silently dropped - a log viewer that hides lines
     * it does not understand is worse than useless.
     */
    public function read(string $file, int $limit): array
    {
        $path = STORAGE_PATH . "/logs/{$file}.log";
        if (!is_file($path)) {
            return [];
        }

        $lines = array_slice(
            array_filter(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)),
            -$limit
        );

        $out = [];
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[([^\]]+)\]\s+([A-Z]+):\s*(.*)$/', $line, $m)) {
                $out[] = ['at' => $m[1], 'level' => $m[2], 'message' => $m[3]];
            } else {
                $out[] = ['at' => '', 'level' => strtoupper($file), 'message' => $line];
            }
        }

        return $this->name($out);
    }

    /**
     * Swap every `<phrase> <id>` for the name of the row it points at.
     *
     * The ids are collected across the whole page first and looked up one query
     * per table, not one per line - a 120-line page of login entries would
     * otherwise be 120 queries for the same handful of accounts.
     */
    private function name(array $entries): array
    {
        $map     = $this->phrases();
        $pattern = '/\b(' . implode('|', array_map(
            static fn(string $p): string => str_replace(' ', '\s+', preg_quote($p, '/')),
            array_keys($map)
        )) . ')\s+(\d+)\b/i';

        // Pass one - what has to be looked up.
        $wanted = [];
        foreach ($entries as $entry) {
            if (preg_match_all($pattern, $entry['message'], $found, PREG_SET_ORDER)) {
                foreach ($found as $hit) {
                    [$table] = $map[$this->key($hit[1])];
                    $wanted[$table][(int) $hit[2]] = true;
                }
            }
        }

        $names = [];
        foreach ($wanted as $table => $ids) {
            $names[$table] = $this->lookup($table, $map, array_keys($ids));
        }

        // Pass two - rewrite.
        foreach ($entries as $i => $entry) {
            $entries[$i]['message'] = preg_replace_callback(
                $pattern,
                function (array $hit) use ($map, $names): string {
                    [$table, , $label] = $map[$this->key($hit[1])];
                    $name = $names[$table][(int) $hit[2]] ?? null;

                    if ($name === null || $name === '') {
                        return $hit[0];          // deleted row - leave the id
                    }
                    return $label === null ? $name : $label . ' "' . $name . '"';
                },
                $entry['message']
            );
        }

        return $entries;
    }

    /** Every phrase this resolves, longest first so the regex prefers it. */
    private function phrases(): array
    {
        $map = [];
        foreach (ContentVerificationService::TYPES as $type => $spec) {
            $map[$type] = [$spec['table'], $spec['title'], $spec['label']];
        }
        $map = self::EXTRA + $map;

        uksort($map, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        return $map;
    }

    /** The phrase as it is keyed, from however the line happened to spell it. */
    private function key(string $matched): string
    {
        return strtolower(preg_replace('/\s+/', ' ', $matched));
    }

    /**
     * @param  int[] $ids
     * @return array<int, string> id => name
     */
    private function lookup(string $table, array $map, array $ids): array
    {
        $column = null;
        foreach ($map as [$t, $c]) {
            if ($t === $table) { $column = $c; break; }
        }
        if ($column === null || !$ids) {
            return [];
        }

        // $table and $column are keys of a constant map in this file, never
        // anything a request supplied; the ids are still bound.
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows  = Database::all(
            "SELECT id, {$column} AS name FROM {$table} WHERE id IN ({$marks})",
            $ids
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['id']] = (string) $row['name'];
        }
        return $out;
    }
}
