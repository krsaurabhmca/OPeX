<?php
/**
 * API Entry Point
 * 
 * RESTful API for OPeX Platform
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load system
require_once __DIR__ . '/../system/bootstrap.php';
require_once __DIR__ . '/ApiController.php';

// Route the request
$api = new ApiController();
$api->handleRequest();
