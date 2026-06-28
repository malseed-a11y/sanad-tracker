<?php

namespace SanadTracker\Database;

if (!defined('ABSPATH')) {
    exit;
}

class RegionRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;

        $this->table = $wpdb->prefix . 'sanad_tracker_regions';
    }

    public function getAll(): array
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, name, slug FROM {$this->table} ORDER BY id DESC"
        ) ?: [];
    }

    public function getById(int $id): ?object
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, slug FROM {$this->table} WHERE id = %d",
                $id
            )
        ) ?: null;
    }

    public function getBySlug(string $slug): ?object
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, slug FROM {$this->table} WHERE slug = %s",
                $slug
            )
        ) ?: null;
    }

    public function create(string $name, string $slug): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            ['name' => $name, 'slug' => $slug],
            ['%s', '%s']
        );

        return $wpdb->insert_id;
    }

    public function update(int $id, string $name, string $slug): void
    {
        global $wpdb;

        $wpdb->update(
            $this->table,
            ['name' => $name, 'slug' => $slug],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function delete(int $id): void
    {
        global $wpdb;

        $wpdb->delete($this->table, ['id' => $id], ['%d']);
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        global $wpdb;

        if ($excludeId) {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$this->table} WHERE slug = %s AND id != %d",
                    $slug,
                    $excludeId
                )
            );
        } else {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$this->table} WHERE slug = %s",
                    $slug
                )
            );
        }

        return $found !== null;
    }

    public function getAllOrderedByName(): array
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, name, slug FROM {$this->table} ORDER BY name ASC"
        ) ?: [];
    }
}
