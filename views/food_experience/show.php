<?php $title = h($post['title']); include __DIR__ . '/../layouts/header.php'; ?>
<article class="fe-detail">
  <div class="fe-detail-header">
    <h2><?= h($post['title']) ?></h2>
    <span class="badge badge-<?= h($post['post_type']) ?>"><?= ucfirst(h($post['post_type'])) ?></span>
  </div>
  <p class="meta">By <strong><?= h($post['author_name']) ?></strong> &middot; <?= h(date('M d, Y', strtotime($post['created_at']))) ?>
    <?php if ($post['updated_at'] !== $post['created_at']): ?>
      &middot; <em>edited <?= h(date('M d, Y', strtotime($post['updated_at']))) ?></em>
    <?php endif; ?>
  </p>
  <?php if ($post['restaurant_name']): ?>
    <p>🍴 Restaurant: <a href="<?= BASE_URL ?>/restaurants/<?= $post['restaurant_id'] ?>/show"><?= h($post['restaurant_name']) ?></a></p>
  <?php endif; ?>
  <?php if ($post['item_name']): ?>
    <p>🍜 Food Item: <a href="<?= BASE_URL ?>/menu-items/<?= $post['menu_item_id'] ?>/show"><?= h($post['item_name']) ?></a></p>
  <?php endif; ?>
  <div class="fe-content"><?= nl2br(h($post['content'])) ?></div>
  <?php if (isLoggedIn() && ((int)$post['user_id'] === currentUserId() || isAdmin())): ?>
    <div class="post-actions">
      <a href="<?= BASE_URL ?>/food-experience/<?= $post['id'] ?>/edit" class="btn-secondary">Edit</a>
      <form method="POST" action="<?= BASE_URL ?>/food-experience/<?= $post['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Delete this post?')">
        <?= csrfField() ?>
        <button class="btn-danger">Delete Post</button>
      </form>
    </div>
  <?php endif; ?>
</article>

<!-- Comments Section (Task 4) -->
<section class="comments-section">
  <h3>Comments</h3>
  <div id="comments-list">
    <?php foreach ($comments as $c): ?>
      <div class="comment-card" id="comment-<?= $c['id'] ?>">
        <strong><?= h($c['user_name']) ?></strong>
        <span class="review-date"><?= h($c['created_at']) ?></span>
        <p><?= nl2br(h($c['comment'])) ?></p>
        <?php if (isLoggedIn() && ((int)$c['user_id'] === currentUserId() || isAdmin())): ?>
          <button class="btn-danger btn-sm" onclick="deleteComment(<?= $c['id'] ?>)">Delete</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (empty($comments)): ?><p id="no-comments">No comments yet.</p><?php endif; ?>
  </div>

  <?php if (isLoggedIn()): ?>
    <div class="comment-form">
      <h4>Add a Comment</h4>
      <div id="comment-error" class="alert alert-error hidden"></div>
      <div class="form-group">
        <textarea id="comment-text" class="input textarea" rows="3" placeholder="Write a comment..." maxlength="1000"></textarea>
      </div>
      <button class="btn-primary" onclick="postComment(<?= $post['id'] ?>)">Post Comment</button>
    </div>
  <?php else: ?>
    <p><a href="<?= BASE_URL ?>/auth/login">Login</a> to comment.</p>
  <?php endif; ?>
</section>

<br><a href="<?= BASE_URL ?>/food-experience" class="btn-outline">← Back to Food Experience</a>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
