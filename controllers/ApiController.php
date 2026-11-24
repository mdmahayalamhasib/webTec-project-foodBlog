<?php
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/RestaurantModel.php';
require_once __DIR__ . '/../models/MenuItemModel.php';
require_once __DIR__ . '/../models/ReviewModel.php';
require_once __DIR__ . '/../models/FoodExperienceModel.php';
require_once __DIR__ . '/../config/app.php';

/**
 * JSON endpoints used by the AJAX features. Routing lives in index.php.
 * Every state-changing call must be logged-in and carry the CSRF token
 * (sent by js/app.js as the X-CSRF-Token header).
 */
class ApiController {

    // ── helpers ─────────────────────────────────────────────
    private function requireAuthJson(bool $admin = false): void {
        if (!isLoggedIn()) jsonError('Please log in first.', 401);
        if ($admin && !isAdmin()) jsonError('Admin access required.', 403);
    }

    public function checkCsrf(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!verifyCsrfToken((string)$token)) jsonError('Invalid or missing security token. Reload the page.', 419);
    }

    /** Body of a JSON request, falling back to normal form fields. */
    private function input(): array {
        $raw = file_get_contents('php://input');
        $data = $raw ? json_decode($raw, true) : null;
        return is_array($data) ? $data : $_POST;
    }

    private function cleanComment($value): string {
        $text = trim((string)$value);
        if ($text === '') jsonError('Comment cannot be empty.', 422);
        if (mb_strlen($text) > 1000) jsonError('Comment must be 1000 characters or fewer.', 422);
        return $text;
    }

    // ── Search (public) ─────────────────────────────────────
    public function search(): void {
        $q        = trim($_GET['q'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $area     = trim($_GET['area'] ?? '');
        jsonResponse([
            'success'     => true,
            'restaurants' => (new RestaurantModel())->search($q, $location, $area),
            'items'       => (new MenuItemModel())->search($q, $location, $area),
        ]);
    }

    // ── Restaurants / menu items (admin) ────────────────────
    public function deleteMenuItem(int $id): void {
        $this->requireAuthJson(true);
        $model = new MenuItemModel();
        if (!$model->findById($id)) jsonError('Menu item not found', 404);
        $model->delete($id);
        jsonResponse(['success' => true, 'message' => 'Menu item deleted successfully']);
    }

    public function deleteRestaurant(int $id): void {
        $this->requireAuthJson(true);
        $restaurants = new RestaurantModel();
        if (!$restaurants->findById($id)) jsonError('Restaurant not found', 404);
        $items = new MenuItemModel();
        foreach ($items->getByRestaurant($id) as $item) {
            $items->delete((int)$item['id']);
        }
        $restaurants->delete($id);
        jsonResponse(['success' => true, 'message' => 'Restaurant and all its menu items deleted successfully']);
    }

    // ── Menu item reviews (members + admin) ─────────────────
    public function addReview(): void {
        $this->requireAuthJson();
        $in     = $this->input();
        $itemId = (int)($in['menu_item_id'] ?? 0);
        if (!(new MenuItemModel())->findById($itemId)) jsonError('Menu item not found.', 404);
        $comment = $this->cleanComment($in['comment'] ?? '');
        $id = (new ReviewModel())->create($itemId, currentUserId(), $comment);
        jsonResponse([
            'success'    => true,
            'id'         => $id,
            'user_name'  => $_SESSION['name'],
            'comment'    => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ], 201);
    }

    public function deleteReview(int $id): void {
        $this->requireAuthJson();
        $model  = new ReviewModel();
        $review = $model->findById($id);
        if (!$review) jsonError('Review not found.', 404);
        if ((int)$review['user_id'] !== currentUserId() && !isAdmin()) jsonError('You can only delete your own reviews.', 403);
        $model->delete($id);
        jsonResponse(['success' => true, 'message' => 'Review deleted.']);
    }

    // ── Food experience posts & comments ────────────────────
    public function addComment(): void {
        $this->requireAuthJson();
        $in     = $this->input();
        $postId = (int)($in['post_id'] ?? 0);
        $model  = new FoodExperienceModel();
        if (!$model->findById($postId)) jsonError('Post not found.', 404);
        $comment = $this->cleanComment($in['comment'] ?? '');
        $id = $model->addComment($postId, currentUserId(), $comment);
        jsonResponse([
            'success'    => true,
            'id'         => $id,
            'user_name'  => $_SESSION['name'],
            'comment'    => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ], 201);
    }

    public function deleteComment(int $id): void {
        $this->requireAuthJson();
        $model   = new FoodExperienceModel();
        $comment = $model->findComment($id);
        if (!$comment) jsonError('Comment not found.', 404);
        if ((int)$comment['user_id'] !== currentUserId() && !isAdmin()) jsonError('You can only delete your own comments.', 403);
        $model->deleteComment($id);
        jsonResponse(['success' => true, 'message' => 'Comment deleted.']);
    }

    public function deleteFoodExpPost(int $id): void {
        $this->requireAuthJson();
        $model = new FoodExperienceModel();
        $post  = $model->findById($id);
        if (!$post) jsonError('Post not found.', 404);
        if ((int)$post['user_id'] !== currentUserId() && !isAdmin()) jsonError('You can only delete your own posts.', 403);
        $model->delete($id);
        jsonResponse(['success' => true, 'message' => 'Post deleted.']);
    }

    // ── Admin moderation ────────────────────────────────────
    public function deleteMember(int $id): void {
        $this->requireAuthJson(true);
        $users = new UserModel();
        $user  = $users->findById($id);
        if (!$user) jsonError('Member not found.', 404);
        if ($user['role'] !== 'member') jsonError('Only members can be removed here.', 403);
        if (!empty($user['profile_picture'])) deleteUploadedFile($user['profile_picture'], PROFILE_UPLOAD_DIR);
        $users->delete($id); // reviews, posts and comments cascade
        jsonResponse(['success' => true, 'message' => 'Member removed.']);
    }

    public function adminDeleteReview(int $id): void {
        $this->requireAuthJson(true);
        $model = new ReviewModel();
        if (!$model->findById($id)) jsonError('Review not found.', 404);
        $model->delete($id);
        jsonResponse(['success' => true, 'message' => 'Review deleted.']);
    }

    public function adminDeleteFEPost(int $id): void { $this->deleteFoodExpPost($id); }
    public function adminDeleteFEComment(int $id): void { $this->deleteComment($id); }
}
