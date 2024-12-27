<?php
session_start();
require_once 'error_log.php';
require_once 'lang.php';

// Include all necessary functions and classes
include_once 'functions.php';

// Router
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$query = parse_url($request_uri, PHP_URL_QUERY);
parse_str($query, $query_params);

// Set language
$lang = $query_params['lang'] ?? 'en';

// Route handling
switch ($path) {
    case '/':
    case '/index.php':
        include 'views/index_view.php';
        break;
    case '/login_member.php':
        include 'views/login_member_view.php';
        break;
    case '/register.php':
        include 'views/register_view.php';
        break;
    case '/client_dashboard.php':
        include 'views/client_dashboard_view.php';
        break;
    case '/courses.php':
        include 'views/courses_view.php';
        break;
    case '/course_content.php':
        include 'views/course_content_view.php';
        break;
    case '/lesson.php':
        include 'views/lesson_view.php';
        break;
    case '/edit_profile.php':
        include 'views/edit_profile_view.php';
        break;
    case '/completed_courses.php':
        include 'views/completed_courses_view.php';
        break;
    case '/pricing.php':
        include 'views/pricing_view.php';
        break;
    case '/my_tickets.php':
        include 'views/my_tickets_view.php';
        break;
    case '/create_ticket.php':
        include 'views/create_ticket_view.php';
        break;
    case '/view_ticket.php':
        include 'views/view_ticket_view.php';
        break;
    case '/admin_dashboard.php':
        include 'views/admin_dashboard_view.php';
        break;
    case '/admin_manage_clients.php':
        include 'views/admin_manage_clients_view.php';
        break;
    case '/admin_edit_client.php':
        include 'views/admin_edit_client_view.php';
        break;
    case '/admin_tickets.php':
        include 'views/admin_tickets_view.php';
        break;
    case '/logout.php':
        session_destroy();
        header('Location: index.php');
        exit;
    default:
        http_response_code(404);
        include 'views/404_view.php';
        break;
}