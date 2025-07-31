<?php
/**
 * Database configuration and connection helper
 *
 * This file defines a function to obtain a PDO connection to the MySQL database.
 * Update the credentials below to match your environment.
 */

function getDatabaseConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // Update these variables to match your MySQL credentials
        $host = 'localhost';
        $dbname = 'campaignconnect';
        $username = 'root';
        $password = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}