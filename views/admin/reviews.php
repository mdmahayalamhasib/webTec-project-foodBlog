<?php $title = 'Manage Reviews'; include __DIR__ . '/../layouts/header.php'; ?>
<h2>Manage All Reviews</h2>
<table class="table">
  <thead>
    <tr><th>User</th><th>Food Item</th><th>Comment</th><th>Date</th><th>Action</th></tr>
  </thead>
  <tbody id="reviews-table">
    <?php foreach ($reviews as $r): ?>
      <tr id="review-row-<?= $r['id'] ?>">
        <td><?= h($r['user_name']) ?></td>
        <td><?= h($r['item_name']) ?></td>
        <td><?= h(mb_strimwidth($r['comment'], 0, 80, '...')) ?></td>
        <td><?= h(date('M d, Y', strtotime($r['created_at']))) ?></td>
        <td><button class="btn-danger btn-sm" onclick="adminDeleteReview(<?= (int)$r['id'] ?>)">Delete</button></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?><tr><td colspan="5">No reviews.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
