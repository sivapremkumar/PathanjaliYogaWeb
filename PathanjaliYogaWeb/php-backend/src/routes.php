<?php
// src/routes.php
use Slim\App;

return function (App $app) {
    // Auth routes
    $app->post('/api/auth/login', 'App\\Controllers\\AuthController:login');
    $app->post('/api/auth/logout', 'App\\Controllers\\AuthController:logout');

    // Admin Login Page (simple HTML for demonstration)
    $app->get('/admin/login', function ($request, $response, $args) {
        $html = '<!DOCTYPE html><html><head><title>Admin Login</title></head><body><h2>Admin Login</h2><form method="POST" action="/api/auth/login"><label>Username: <input type="text" name="username" /></label><br><label>Password: <input type="password" name="password" /></label><br><button type="submit">Login</button></form></body></html>';
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Trustees
    $app->get('/api/trustees', 'App\\Controllers\\TrusteeController:index');
    $app->post('/api/trustees', 'App\\Controllers\\TrusteeController:create');
    $app->delete('/api/trustees/{id}', 'App\\Controllers\\TrusteeController:delete');

    // Donations
    $app->get('/api/donations', 'App\\Controllers\\DonationController:index');
    $app->get('/api/donations/razorpay-key', 'App\\Controllers\\DonationController:getRazorpayKey');
    $app->post('/api/donations/order', 'App\\Controllers\\DonationController:createOrder');
    $app->post('/api/donations/verify', 'App\\Controllers\\DonationController:verify');

    // News & Events
    $app->get('/api/news', 'App\\Controllers\\NewsEventController:index');
    $app->post('/api/news', 'App\\Controllers\\NewsEventController:create');

    // Inquiries
    $app->post('/api/inquiries', 'App\\Controllers\\InquiryController:create');
    $app->get('/api/inquiries', 'App\\Controllers\\InquiryController:index');

    // Admin Dashboard
    $app->get('/api/admin/stats', 'App\\Controllers\\AdminDashboardController:stats');
};
