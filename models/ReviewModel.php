<?php
require_once __DIR__ . '/../config/database.php';

class ReviewModel {
    public function getByMenuItem(int $menuItemId): array {
        return dbAll(
            "SELECT r.*, u.name AS user_name FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.menu_item_id = ? ORDER BY r.created_at DESC, r.id DESC",
            'i', [$menuItemId]
        );
    }

    public function findById(int $id): ?array {
        return dbOne("SELECT * FROM reviews WHERE id = ? LIMIT 1", 'i', [$id]);
    }

    public function create(int $menuItemId, int $userId, string $comment): int {
        return dbExec("INSERT INTO reviews (menu_item_id,user_id,comment) VALUES (?,?,?)", 'iis', [$menuItemId, $userId, $comment]);
    }

    public function delete(int $id): void {
        dbExec("DELETE FROM reviews WHERE id = ?", 'i', [$id]);
    }

    public function count(): int {
        return dbScalar("SELECT COUNT(*) FROM reviews");
    }

    public function getAll(): array {
        return dbAll(
            "SELECT r.*, u.name AS user_name, mi.name AS item_name FROM reviews r
             JOIN users u ON u.id = r.user_id
             JOIN menu_items mi ON mi.id = r.menu_item_id
             ORDER BY r.created_at DESC, r.id DESC"
        );
    }
}
