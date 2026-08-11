<?php
namespace Core;

/**
 * Base model. Every module model extends this and sets $table + $fillable.
 * All queries use prepared statements — never concatenate user input.
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array  $fillable   = [];
    protected bool   $softDelete = true;

    /** Strip anything not declared fillable. Prevents mass-assignment bugs. */
    protected function filter(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function notDeleted(string $alias = ''): string
    {
        if (!$this->softDelete) return '1=1';
        $p = $alias ? "{$alias}." : '';
        return "{$p}deleted_at IS NULL";
    }

    // ---------- CREATE ----------
    public function create(array $data): int
    {
        $data = $this->filter($data);
        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ":{$c}", $cols);

        $sql = sprintf(
            'INSERT INTO %s (%s, created_at) VALUES (%s, NOW())',
            $this->table,
            implode(', ', $cols),
            implode(', ', $ph)
        );
        Database::run($sql, $data);
        return Database::insertId();
    }

    // ---------- READ ----------
    public function find(int|string $id): ?array
    {
        return Database::one(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND {$this->notDeleted()} LIMIT 1",
            [$id]
        );
    }

    public function findBy(string $column, mixed $value): ?array
    {
        $this->assertColumn($column);
        return Database::one(
            "SELECT * FROM {$this->table} WHERE {$column} = ? AND {$this->notDeleted()} LIMIT 1",
            [$value]
        );
    }

    /**
     * @param array $where  ['status' => 'approved', 'city_id' => 3]
     */
    public function all(array $where = [], string $orderBy = 'created_at DESC', int $limit = 0, int $offset = 0): array
    {
        [$clause, $params] = $this->buildWhere($where);
        $sql = "SELECT * FROM {$this->table} WHERE {$clause} ORDER BY {$orderBy}";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }
        return Database::all($sql, $params);
    }

    public function count(array $where = []): int
    {
        [$clause, $params] = $this->buildWhere($where);
        return (int) Database::value("SELECT COUNT(*) FROM {$this->table} WHERE {$clause}", $params);
    }

    // ---------- UPDATE ----------
    public function update(int|string $id, array $data): bool
    {
        $data = $this->filter($data);
        if (!$data) return false;

        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $data['__id'] = $id;

        $sql = "UPDATE {$this->table} SET {$sets}, updated_at = NOW() WHERE {$this->primaryKey} = :__id";
        return Database::run($sql, $data)->rowCount() >= 0;
    }

    // ---------- DELETE ----------
    /** Soft delete by default — records stay for audit and impact reports. */
    public function delete(int|string $id): bool
    {
        if ($this->softDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE {$this->primaryKey} = ?";
        } else {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        }
        return Database::run($sql, [$id])->rowCount() > 0;
    }

    public function restore(int|string $id): bool
    {
        return Database::run(
            "UPDATE {$this->table} SET deleted_at = NULL WHERE {$this->primaryKey} = ?",
            [$id]
        )->rowCount() > 0;
    }

    public function exists(string $column, mixed $value, ?int $exceptId = null): bool
    {
        $this->assertColumn($column);
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE {$column} = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $exceptId;
        }
        return (int) Database::value($sql, $params) > 0;
    }

    // ---------- helpers ----------
    private function buildWhere(array $where): array
    {
        $parts  = [$this->notDeleted()];
        $params = [];
        foreach ($where as $col => $val) {
            $this->assertColumn($col);
            if (is_array($val)) {
                $ph      = implode(',', array_fill(0, count($val), '?'));
                $parts[] = "{$col} IN ({$ph})";
                $params  = array_merge($params, array_values($val));
            } else {
                $parts[]  = "{$col} = ?";
                $params[] = $val;
            }
        }
        return [implode(' AND ', $parts), $params];
    }

    /** Column names come from code, not users — but guard anyway. */
    private function assertColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Illegal column name: {$column}");
        }
    }
}
