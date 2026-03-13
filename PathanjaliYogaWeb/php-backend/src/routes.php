<?php
// src/routes.php
use Slim\App;

return function (App $app) {
    // Health check endpoint
    $app->get('/health', function ($request, $response) {
        try {
            // Test database connection
            $result = \Illuminate\Database\Capsule\Manager::connection()->select('SELECT 1');
            $response->getBody()->write(json_encode(['status' => 'ok', 'database' => 'connected']));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'database' => 'disconnected', 'error' => $e->getMessage()]));
            return $response->withStatus(500);
        }
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Database test endpoint
    $app->get('/test-db', function ($request, $response) {
        require __DIR__ . '/../test-db.php';
        return $response;
    });

    // Allow OPTIONS for API clients and preflight requests.
    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    // Auth routes
    $app->post('/api/auth/login', 'App\\Controllers\\AuthController:login');
    $app->post('/api/auth/logout', 'App\\Controllers\\AuthController:logout');
    $app->post('/auth/login', 'App\\Controllers\\AuthController:login');
    $app->post('/auth/logout', 'App\\Controllers\\AuthController:logout');

    // Helpful GET responses (avoids fatal 405 when opening login URL directly in browser)
    $app->get('/api/auth/login', function ($request, $response) {
        $response->getBody()->write(json_encode(['message' => 'Use POST /api/auth/login']));
        return $response->withHeader('Content-Type', 'application/json');
    });
    $app->get('/auth/login', function ($request, $response) {
        $response->getBody()->write(json_encode(['message' => 'Use POST /auth/login']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Admin Login Page (simple HTML for demonstration)
    $app->get('/admin/login', function ($request, $response, $args) {
        $html = '<!DOCTYPE html><html><head><title>Admin Login</title></head><body><h2>Admin Login</h2><form method="POST" action="/api/auth/login"><label>Username: <input type="text" name="username" /></label><br><label>Password: <input type="password" name="password" /></label><br><button type="submit">Login</button></form></body></html>';
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Trustees
    $app->get('/api/trustees', 'App\\Controllers\\TrusteeController:index');
    $app->post('/api/trustees/upload', 'App\\Controllers\\TrusteeController:upload');
    $app->post('/api/trustees', 'App\\Controllers\\TrusteeController:create');
    $app->put('/api/trustees/{id}', 'App\\Controllers\\TrusteeController:update');
    $app->patch('/api/trustees/{id}', 'App\\Controllers\\TrusteeController:update');
    $app->delete('/api/trustees/{id}', 'App\\Controllers\\TrusteeController:delete');
    $app->get('/trustees', 'App\\Controllers\\TrusteeController:index');
    $app->post('/trustees/upload', 'App\\Controllers\\TrusteeController:upload');
    $app->post('/trustees', 'App\\Controllers\\TrusteeController:create');
    $app->put('/trustees/{id}', 'App\\Controllers\\TrusteeController:update');
    $app->patch('/trustees/{id}', 'App\\Controllers\\TrusteeController:update');
    $app->delete('/trustees/{id}', 'App\\Controllers\\TrusteeController:delete');

    // Donations
    $app->get('/api/donations', 'App\\Controllers\\DonationController:index');
    $app->get('/api/donations/razorpay-key', 'App\\Controllers\\DonationController:getRazorpayKey');
    $app->post('/api/donations/order', 'App\\Controllers\\DonationController:createOrder');
    $app->post('/api/donations/verify', 'App\\Controllers\\DonationController:verify');
    $app->get('/donations', 'App\\Controllers\\DonationController:index');
    $app->get('/donations/razorpay-key', 'App\\Controllers\\DonationController:getRazorpayKey');
    $app->post('/donations/order', 'App\\Controllers\\DonationController:createOrder');
    $app->post('/donations/verify', 'App\\Controllers\\DonationController:verify');

    // News & Events
    $app->get('/api/news', 'App\\Controllers\\NewsEventController:index');
    $app->post('/api/news/upload', 'App\\Controllers\\NewsEventController:upload');
    $app->post('/api/news', 'App\\Controllers\\NewsEventController:create');
    $app->put('/api/news/{id}', 'App\\Controllers\\NewsEventController:update');
    $app->patch('/api/news/{id}', 'App\\Controllers\\NewsEventController:update');
    $app->delete('/api/news/{id}', 'App\\Controllers\\NewsEventController:delete');
    $app->get('/news', 'App\\Controllers\\NewsEventController:index');
    $app->post('/news/upload', 'App\\Controllers\\NewsEventController:upload');
    $app->post('/news', 'App\\Controllers\\NewsEventController:create');
    $app->put('/news/{id}', 'App\\Controllers\\NewsEventController:update');
    $app->patch('/news/{id}', 'App\\Controllers\\NewsEventController:update');
    $app->delete('/news/{id}', 'App\\Controllers\\NewsEventController:delete');

    // Gallery
    $app->get('/api/gallery', 'App\\Controllers\\GalleryController:index');
    $app->post('/api/gallery/upload', 'App\\Controllers\\GalleryController:upload');
    $app->post('/api/gallery', 'App\\Controllers\\GalleryController:create');
    $app->put('/api/gallery/{id}', 'App\\Controllers\\GalleryController:update');
    $app->patch('/api/gallery/{id}', 'App\\Controllers\\GalleryController:update');
    $app->delete('/api/gallery/{id}', 'App\\Controllers\\GalleryController:delete');
    $app->get('/gallery', 'App\\Controllers\\GalleryController:index');
    $app->post('/gallery/upload', 'App\\Controllers\\GalleryController:upload');
    $app->post('/gallery', 'App\\Controllers\\GalleryController:create');
    $app->put('/gallery/{id}', 'App\\Controllers\\GalleryController:update');
    $app->patch('/gallery/{id}', 'App\\Controllers\\GalleryController:update');
    $app->delete('/gallery/{id}', 'App\\Controllers\\GalleryController:delete');

    // Programs
    $app->get('/api/programs', 'App\\Controllers\\ProgramController:index');
    $app->post('/api/programs/upload', 'App\\Controllers\\ProgramController:upload');
    $app->post('/api/programs', 'App\\Controllers\\ProgramController:create');
    $app->put('/api/programs/{id}', 'App\\Controllers\\ProgramController:update');
    $app->patch('/api/programs/{id}', 'App\\Controllers\\ProgramController:update');
    $app->delete('/api/programs/{id}', 'App\\Controllers\\ProgramController:delete');
    $app->get('/programs', 'App\\Controllers\\ProgramController:index');
    $app->post('/programs/upload', 'App\\Controllers\\ProgramController:upload');
    $app->post('/programs', 'App\\Controllers\\ProgramController:create');
    $app->put('/programs/{id}', 'App\\Controllers\\ProgramController:update');
    $app->patch('/programs/{id}', 'App\\Controllers\\ProgramController:update');
    $app->delete('/programs/{id}', 'App\\Controllers\\ProgramController:delete');

    // Inquiries
    $app->post('/api/inquiries', 'App\\Controllers\\InquiryController:create');
    $app->get('/api/inquiries', 'App\\Controllers\\InquiryController:index');
    $app->patch('/api/inquiries/{id}/resolve', 'App\\Controllers\\InquiryController:resolve');
    $app->post('/inquiries', 'App\\Controllers\\InquiryController:create');
    $app->get('/inquiries', 'App\\Controllers\\InquiryController:index');
    $app->patch('/inquiries/{id}/resolve', 'App\\Controllers\\InquiryController:resolve');

    // Admin Dashboard
    $app->get('/api/admin/stats', 'App\\Controllers\\AdminDashboardController:stats');
    $app->get('/admin/stats', 'App\\Controllers\\AdminDashboardController:stats');
};
