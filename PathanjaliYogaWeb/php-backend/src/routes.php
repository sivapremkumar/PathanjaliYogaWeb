<?php
// src/routes.php
use Slim\App;

return function (App $app) {
    // Auth routes
    $app->post('/api/auth/login', 'App\\Controllers\\AuthController:login');
    $app->post('/api/auth/logout', 'App\\Controllers\\AuthController:logout');

    // Trustees
    $app->get('/api/trustees', 'App\\Controllers\\TrusteeController:index');
    $app->post('/api/trustees', 'App\\Controllers\\TrusteeController:create');
    $app->delete('/api/trustees/{id}', 'App\\Controllers\\TrusteeController:delete');

    // Donations
    $app->get('/api/donations', 'App\\Controllers\\DonationController:index');
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
