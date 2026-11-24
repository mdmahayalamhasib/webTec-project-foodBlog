/* FoodBlog - app.js
   Shared helpers, form validation (restaurant / menu item / post forms),
   AJAX search, AJAX deletes, reviews and comments. */

var BASE_URL   = document.querySelector('meta[name="base-url"]')?.content || '';
var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

function escHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function showErr(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.textContent = msg; el.classList.remove('hidden'); }
}
function clearErr(id) {
  const el = document.getElementById(id);
  if (el) { el.textContent = ''; el.classList.add('hidden'); }
}

/** fetch() wrapper that always sends the CSRF token and parses JSON. */
async function apiFetch(path, options = {}) {
  options.headers = Object.assign({ 'X-CSRF-Token': CSRF_TOKEN, 'Accept': 'application/json' }, options.headers || {});
  options.credentials = 'same-origin';
  const res = await fetch(BASE_URL + path, options);
  let data = {};
  try { data = await res.json(); } catch (e) { data = { success: false, error: 'Unexpected server response.' }; }
  if (!res.ok || data.success === false) {
    throw new Error(data.error || data.message || 'Request failed.');
  }
  return data;
}

/* ---- Auto-dismiss flash alerts ------------------------------------------ */
document.querySelectorAll('.alert').forEach(a => {
  setTimeout(() => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }, 6000);
});

/* ---- Restaurant form ----------------------------------------------------- */
const restaurantForm = document.getElementById('restaurantForm');
if (restaurantForm) {
  restaurantForm.addEventListener('submit', function (e) {
    const fields = ['name', 'location', 'area', 'short_background', 'goals'];
    if (fields.some(f => !this.querySelector('[name=' + f + ']').value.trim())) {
      alert('All fields are required.');
      e.preventDefault();
    }
  });
}

/* ---- Menu item form ------------------------------------------------------ */
const menuItemForm = document.getElementById('menuItemForm');
if (menuItemForm) {
  menuItemForm.addEventListener('submit', function (e) {
    const name  = this.querySelector('[name=name]').value.trim();
    const desc  = this.querySelector('[name=description]').value.trim();
    const price = parseFloat(this.querySelector('[name=price]').value);
    const image = this.querySelector('[name=image]').files[0];
    let msg = '';
    if (!name) msg = 'Item name is required.';
    else if (!desc) msg = 'Description is required.';
    else if (isNaN(price) || price <= 0) msg = 'Price must be greater than 0.';
    else if (image && !['image/jpeg', 'image/png'].includes(image.type)) msg = 'Only JPEG and PNG images are allowed.';
    else if (image && image.size > 2 * 1024 * 1024) msg = 'Image size must be less than 2MB.';
    if (msg) { alert(msg); e.preventDefault(); }
  });
}

/* ---- Food experience post form ------------------------------------------ */
const feForm = document.getElementById('feForm');
if (feForm) {
  feForm.addEventListener('submit', function (e) {
    let valid = true;
    ['err-title', 'err-content'].forEach(clearErr);
    if (!this.querySelector('[name=title]').value.trim())   { showErr('err-title', 'Title is required.'); valid = false; }
    if (!this.querySelector('[name=content]').value.trim()) { showErr('err-content', 'Content is required.'); valid = false; }
    if (!valid) e.preventDefault();
  });
}

/* ---- AJAX search (home page) -------------------------------------------- */
const searchBtn = document.getElementById('search-btn');
if (searchBtn) {
  let searchTimer;
  const val = id => document.getElementById(id).value.trim();

  function doSearch() {
    const q = val('search-q'), location = val('search-location'), area = val('search-area');
    const box = document.getElementById('search-results');
    if (!q && !location && !area) { box.classList.add('hidden'); return; }
    fetch(BASE_URL + '/api/search?' + new URLSearchParams({ q, location, area }), { credentials: 'same-origin' })
      .then(r => r.json())
      .then(renderSearchResults)
      .catch(() => {});
  }

  function renderSearchResults(data) {
    const box = document.getElementById('search-results');
    box.classList.remove('hidden');
    let html = '';
    if (data.restaurants?.length) {
      html += '<h4>Restaurants</h4>';
      data.restaurants.forEach(r => {
        html += `<div class="card" style="margin-bottom:.75rem">
          <h4><a href="${BASE_URL}/restaurants/${Number(r.id)}/show">${escHtml(r.name)}</a></h4>
          <p class="meta">📍 ${escHtml(r.location)} · ${escHtml(r.area)}</p></div>`;
      });
    }
    if (data.items?.length) {
      html += '<h4>Menu Items</h4>';
      data.items.forEach(i => {
        html += `<div class="card" style="margin-bottom:.75rem">
          <h4><a href="${BASE_URL}/menu-items/${Number(i.id)}/show">${escHtml(i.name)}</a></h4>
          <p class="meta">From: ${escHtml(i.restaurant_name)} · ৳${parseFloat(i.price).toFixed(2)}</p></div>`;
      });
    }
    box.innerHTML = html || '<p>No results found.</p>';
  }

  searchBtn.addEventListener('click', doSearch);
  ['search-q', 'search-location', 'search-area'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(doSearch, 400); });
    document.getElementById(id)?.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });
  });
}

/* ---- Admin: delete menu item / restaurant (AJAX) ------------------------- */
function ajaxDeleteCard(path, id, button, question, redirectTo) {
  if (!confirm(question)) return;
  const body = new FormData();
  body.append('id', id);
  apiFetch(path, { method: 'POST', body })
    .then(data => {
      if (redirectTo) { window.location.href = BASE_URL + redirectTo; return; }
      button.closest('.card')?.remove();
      alert(data.message);
    })
    .catch(err => alert(err.message));
}
function deleteMenuItem(id, button, redirectTo) {
  ajaxDeleteCard('/api/menu-items/delete', id, button, 'Delete this menu item?', redirectTo);
}
function deleteRestaurant(id, button) {
  ajaxDeleteCard('/api/restaurants/delete', id, button, 'Delete this restaurant and all its menu items?');
}

/* ---- Menu item reviews ---------------------------------------------------- */
function reviewHtml(r) {
  return `<div class="review-card" id="review-${Number(r.id)}">
    <strong>${escHtml(r.user_name)}</strong>
    <span class="review-date">${escHtml(r.created_at)}</span>
    <p>${escHtml(r.comment).replace(/\n/g, '<br>')}</p>
    <button class="btn-danger btn-sm" onclick="deleteReview(${Number(r.id)})">Delete</button></div>`;
}
function postReview(itemId) {
  const box = document.getElementById('review-text'), err = document.getElementById('review-error');
  err.classList.add('hidden');
  const text = box.value.trim();
  if (!text) { err.textContent = 'Review cannot be empty.'; err.classList.remove('hidden'); return; }
  apiFetch('/api/reviews/add', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ menu_item_id: itemId, comment: text })
  }).then(data => {
    document.getElementById('no-reviews')?.remove();
    document.getElementById('reviews-list').insertAdjacentHTML('afterbegin', reviewHtml(data));
    box.value = '';
  }).catch(e => { err.textContent = e.message; err.classList.remove('hidden'); });
}
function deleteReview(id) {
  if (!confirm('Delete this review?')) return;
  apiFetch('/api/reviews/' + id, { method: 'DELETE' })
    .then(() => document.getElementById('review-' + id)?.remove())
    .catch(e => alert(e.message));
}

/* ---- Food experience comments -------------------------------------------- */
function commentHtml(c) {
  return `<div class="comment-card" id="comment-${Number(c.id)}">
    <strong>${escHtml(c.user_name)}</strong>
    <span class="review-date">${escHtml(c.created_at)}</span>
    <p>${escHtml(c.comment).replace(/\n/g, '<br>')}</p>
    <button class="btn-danger btn-sm" onclick="deleteComment(${Number(c.id)})">Delete</button></div>`;
}
function postComment(postId) {
  const box = document.getElementById('comment-text'), err = document.getElementById('comment-error');
  err.classList.add('hidden');
  const text = box.value.trim();
  if (!text) { err.textContent = 'Comment cannot be empty.'; err.classList.remove('hidden'); return; }
  apiFetch('/api/food-exp/comments/add', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ post_id: postId, comment: text })
  }).then(data => {
    document.getElementById('no-comments')?.remove();
    document.getElementById('comments-list').insertAdjacentHTML('beforeend', commentHtml(data));
    box.value = '';
  }).catch(e => { err.textContent = e.message; err.classList.remove('hidden'); });
}
function deleteComment(id) {
  if (!confirm('Delete this comment?')) return;
  apiFetch('/api/food-exp/comments/' + id, { method: 'DELETE' })
    .then(() => document.getElementById('comment-' + id)?.remove())
    .catch(e => alert(e.message));
}

/* ---- Admin moderation ----------------------------------------------------- */
function deleteMember(id, name) {
  if (!confirm('Remove member "' + name + '"? This will delete all their reviews, posts and comments.')) return;
  const box = document.getElementById('member-error');
  apiFetch('/api/admin/delete-member/' + id, { method: 'DELETE' })
    .then(() => document.getElementById('member-row-' + id)?.remove())
    .catch(e => { box.textContent = e.message; box.classList.remove('hidden'); });
}
function adminDeleteReview(id) {
  if (!confirm('Delete this review?')) return;
  apiFetch('/api/admin/delete-review/' + id, { method: 'DELETE' })
    .then(() => document.getElementById('review-row-' + id)?.remove())
    .catch(e => alert(e.message));
}
