<?php
require_once __DIR__ . '/../config/database.php';

class FoodExperienceModel {
    private const SELECT = "SELECT fep.*, u.name AS author_name,
             r.name AS restaurant_name, mi.name AS item_name
             FROM food_experience_posts fep
             JOIN users u ON u.id = fep.user_id
             LEFT JOIN restaurants r ON r.id = fep.restaurant_id
             LEFT JOIN menu_items mi ON mi.id = fep.menu_item_id";

    public function getAll(): array {
        return dbAll(self::SELECT . " ORDER BY fep.created_at DESC, fep.id DESC");
    }

    public function findById(int $id): ?array {
        return dbOne(self::SELECT . " WHERE fep.id = ? LIMIT 1", 'i', [$id]);
    }

    public function create(array $d): int {
        $rid = $d['restaurant_id'] ?: null;
        $mid = $d['menu_item_id'] ?: null;
        return dbExec(
            "INSERT INTO food_experience_posts (user_id,title,content,post_type,restaurant_id,menu_item_id) VALUES (?,?,?,?,?,?)",
            'isssii', [$d['user_id'], $d['title'], $d['content'], $d['post_type'], $rid, $mid]
        );
    }

    public function update(int $id, array $d): void {
        $rid = $d['restaurant_id'] ?: null;
        $mid = $d['menu_item_id'] ?: null;
        dbExec(
            "UPDATE food_experience_posts SET title=?, content=?, post_type=?, restaurant_id=?, menu_item_id=? WHERE id=?",
            'sssiii', [$d['title'], $d['content'], $d['post_type'], $rid, $mid, $id]
        );
    }

    public function delete(int $id): void {
        dbExec("DELETE FROM food_experience_posts WHERE id = ?", 'i', [$id]);
    }

    public function count(): int {
        return dbScalar("SELECT COUNT(*) FROM food_experience_posts");
    }

    // ── Comments
    public function getComments(int $postId): array {
        return dbAll(
            "SELECT fec.*, u.name AS user_name FROM food_experience_comments fec
             JOIN users u ON u.id = fec.user_id WHERE fec.post_id = ? ORDER BY fec.created_at ASC, fec.id ASC",
            'i', [$postId]
        );
    }

    public function addComment(int $postId, int $userId, string $comment): int {
        return dbExec("INSERT INTO food_experience_comments (post_id,user_id,comment) VALUES (?,?,?)", 'iis', [$postId, $userId, $comment]);
    }

    public function findComment(int $id): ?array {
        return dbOne("SELECT * FROM food_experience_comments WHERE id = ? LIMIT 1", 'i', [$id]);
    }

    public function deleteComment(int $id): void {
        dbExec("DELETE FROM food_experience_comments WHERE id = ?", 'i', [$id]);
    }
}
