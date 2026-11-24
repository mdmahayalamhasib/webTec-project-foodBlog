<?php
$title = h($item['name']);
include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
  <h2><?= h($item['name']) ?></h2>

  <?php if (isAdmin()): ?>
    <div>
      <a href="<?= BASE_URL ?>/menu-items/<?= $item['id'] ?>/edit" class="btn-secondary">Edit</a>
      <!-- AJAX Delete button -->
      <button class="btn-danger"
              onclick="deleteMenuItem(<?= (int)$item['id'] ?>, this, '/restaurants/<?= (int)$item['restaurant_id'] ?>/show')">
        Delete
      </button>
    </div>
  <?php endif; ?>
</div>

<div class="item-detail card">
  <?php if (!empty($item['image_path'])): ?>
    <img
      src="<?= BASE_URL ?>/uploads/menu/<?= h($item['image_path']) ?>"
      alt="<?= h($item['name']) ?>"
      class="item-img">
  <?php endif; ?>

  <p class="price-large">
    ৳<?= number_format((float)$item['price'], 2) ?>
  </p>

  <p class="restaurant-link">
    From:
    <a href="<?= BASE_URL ?>/restaurants/<?= $item['restaurant_id'] ?>/show">
      <?= h($item['restaurant_name']) ?>
    </a>
  </p>

  <p><?= nl2br(h($item['description'] ?? '')) ?></p>
</div>


<!-- Reviews -->
<section class="reviews-section">
  <h3>Reviews (<span id="review-count"><?= count($reviews) ?></span>)</h3>

  <div id="reviews-list">
    <?php foreach ($reviews as $rv): ?>
      <div class="review-card" id="review-<?= (int)$rv['id'] ?>">
        <strong><?= h($rv['user_name']) ?></strong>
        <span class="review-date"><?= h($rv['created_at']) ?></span>
        <p><?= nl2br(h($rv['comment'])) ?></p>
        <?php if (isLoggedIn() && ((int)$rv['user_id'] === currentUserId() || isAdmin())): ?>
          <button class="btn-danger btn-sm" onclick="deleteReview(<?= (int)$rv['id'] ?>)">Delete</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?><p id="no-reviews">No reviews yet. Be the first!</p><?php endif; ?>
  </div>

  <?php if (isLoggedIn()): ?>
    <div class="review-form">
      <h4>Write a Review</h4>
      <div id="review-error" class="alert alert-error hidden"></div>
      <div class="form-group">
        <textarea id="review-text" class="input textarea" rows="3" maxlength="1000" placeholder="Share your thoughts about this dish..."></textarea>
      </div>
      <button type="button" class="btn-primary" onclick="postReview(<?= (int)$item['id'] ?>)">Post Review</button>
    </div>
  <?php else: ?>
    <p><a href="<?= BASE_URL ?>/auth/login">Log in</a> or <a href="<?= BASE_URL ?>/auth/register">register</a> to post a review.</p>
  <?php endif; ?>
</section>

<br>

<a href="<?= BASE_URL ?>/restaurants/<?= $item['restaurant_id'] ?>/show" class="btn-outline">
  ← Back to Restaurant
</a>

<?php include __DIR__ . '/../layouts/footer.php'; ?>