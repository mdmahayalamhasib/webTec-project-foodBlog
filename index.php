<?php
require_once __DIR__ . '/config/app.php';      // starts the session, defines BASE_URL
require_once __DIR__ . '/config/database.php';

// ── Remember Me auto-login (cookie holds the raw token, DB holds its sha256)
if (!isLoggedIn() && !empty($_COOKIE['remember_token']) && is_string($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/models/UserModel.php';
    $user = (new UserModel())->findByRememberToken(hash('sha256', $_COOKIE['remember_token']));
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
    }
}

// ── Work out controller / action / id from the URL (relative to BASE_URL)
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uri  = rawurldecode($uri);
$path = (BASE_URL !== '' && strpos($uri, BASE_URL) === 0) ? substr($uri, strlen(BASE_URL)) : $uri;
$path = preg_replace('#^/?index\.php#', '', $path);
$path = trim($path, '/');
$parts = $path === '' ? [] : explode('/', $path);

$controller = $parts[0] ?? 'home';
$action     = $parts[1] ?? null;
$id         = $parts[2] ?? null;

// Clean URLs such as /restaurants/5/show  or just /restaurants/5
if (in_array($controller, ['restaurants', 'menu-items', 'food-experience'], true)
    && isset($parts[1]) && ctype_digit($parts[1])) {
    $id     = $parts[1];
    $action = $parts[2] ?? 'show';
}

// ── JSON / AJAX API:  /api/...
if ($controller === 'api') {
    require_once __DIR__ . '/controllers/ApiController.php';
    $api      = new ApiController();
    $resource = $parts[1] ?? '';
    $sub      = $parts[2] ?? '';
    $subId    = $parts[3] ?? '';
    $verb     = $_SERVER['REQUEST_METHOD'];

    if ($resource === 'search' && $verb === 'GET') {
        $api->search();
    }

    if (in_array($verb, ['POST', 'DELETE'], true)) {
        $api->checkCsrf();

        if ($resource === 'menu-items' && $sub === 'delete' && $verb === 'POST') {
            $api->deleteMenuItem((int)($_POST['id'] ?? 0));
        }
        if ($resource === 'restaurants' && $sub === 'delete' && $verb === 'POST') {
            $api->deleteRestaurant((int)($_POST['id'] ?? 0));
        }
        if ($resource === 'reviews') {
            if ($sub === 'add' && $verb === 'POST')                  $api->addReview();
            if (ctype_digit($sub) && $verb === 'DELETE')             $api->deleteReview((int)$sub);
        }
        if ($resource === 'food-exp') {
            if ($sub === 'comments' && $subId === 'add' && $verb === 'POST') $api->addComment();
            if ($sub === 'comments' && ctype_digit($subId) && $verb === 'DELETE') $api->deleteComment((int)$subId);
            if (ctype_digit($sub) && $verb === 'DELETE')             $api->deleteFoodExpPost((int)$sub);
        }
        if ($resource === 'admin' && $verb === 'DELETE' && ctype_digit($subId)) {
            match ($sub) {
                'delete-member'     => $api->deleteMember((int)$subId),
                'delete-review'     => $api->adminDeleteReview((int)$subId),
                'delete-fe-post'    => $api->adminDeleteFEPost((int)$subId),
                'delete-fe-comment' => $api->adminDeleteFEComment((int)$subId),
                default             => null,
            };
        }
    }

    jsonError('API route not found', 404);
}

// ── Page routes
switch ($controller) {
    case 'home':
        require_once __DIR__ . '/controllers/HomeController.php';
        (new HomeController())->index();
        break;

    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $c = new AuthController();
        match ($action) {
            'register' => $c->register(),
            'login'    => $c->login(),
            'logout'   => $c->logout(),
            'profile'  => $c->profile(),
            default    => $c->login(),
        };
        break;

    case 'restaurants':
        require_once __DIR__ . '/controllers/RestaurantController.php';
        $c = new RestaurantController();
        match ($action) {
            'show'   => $c->show((int)$id),
            'create' => $c->create(),
            'store'  => $c->store(),
            'edit'   => $c->edit((int)$id),
            'update' => $c->update((int)$id),
            'delete' => $c->delete((int)$id),
            default  => $c->index(),
        };
        break;

    case 'menu-items':
        require_once __DIR__ . '/controllers/MenuItemController.php';
        $c = new MenuItemController();
        match ($action) {
            'show'   => $c->show((int)$id),
            'create' => $c->create(),
            'store'  => $c->store(),
            'edit'   => $c->edit((int)$id),
            'update' => $c->update((int)$id),
            'delete' => $c->delete((int)$id),
            default  => redirect('/restaurants'),
        };
        break;

    case 'food-experience':
        require_once __DIR__ . '/controllers/FoodExperienceController.php';
        $c = new FoodExperienceController();
        match ($action) {
            'show'   => $c->show((int)$id),
            'create' => $c->create(),
            'store'  => $c->store(),
            'edit'   => $c->edit((int)$id),
            'update' => $c->update((int)$id),
            'delete' => $c->delete((int)$id),
            default  => $c->index(),
        };
        break;

    case 'admin':
        require_once __DIR__ . '/controllers/AdminController.php';
        $c = new AdminController();
        match ($action) {
            'members' => $c->members(),
            'reviews' => $c->reviews(),
            default   => $c->dashboard(),
        };
        break;

    default:
        http_response_code(404);
        $title = 'Page not found';
        include __DIR__ . '/views/layouts/header.php';
        echo '<div class="card"><h2>404 – Page not found</h2><p><a href="' . BASE_URL . '/home">Back to home</a></p></div>';
        include __DIR__ . '/views/layouts/footer.php';
}
