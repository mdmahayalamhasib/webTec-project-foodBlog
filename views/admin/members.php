<?php $title = 'Manage Members'; include __DIR__ . '/../layouts/header.php'; ?>
<h2>Manage Members</h2>
<div id="member-error" class="alert alert-error hidden"></div>
<table class="table">
  <thead>
    <tr><th>Name</th><th>Email</th><th>Joined</th><th>Action</th></tr>
  </thead>
  <tbody id="members-table">
    <?php foreach ($members as $m): ?>
      <tr id="member-row-<?= $m['id'] ?>">
        <td><?= h($m['name']) ?></td>
        <td><?= h($m['email']) ?></td>
        <td><?= h(date('M d, Y', strtotime($m['created_at']))) ?></td>
        <td>
          <button class="btn-danger btn-sm" data-name="<?= h($m['name']) ?>" onclick="deleteMember(<?= (int)$m['id'] ?>, this.dataset.name)">Remove</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($members)): ?><tr><td colspan="4">No members found.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
