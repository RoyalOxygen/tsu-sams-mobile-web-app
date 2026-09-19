<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Security;
use App\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        $role = $_GET['role'] ?? 'student';
        if (!in_array($role, ['student', 'lecturer', 'admin'], true)) {
            $role = 'student';
        }
        View::render('auth/login', [
            'title' => 'Sign in',
            'role' => $role,
        ], 'layouts/guest');
    }

    public function login(): void
    {
        $role = $_POST['role'] ?? 'student';
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Enter your credentials.');
            redirect('login?role=' . urlencode($role));
        }
        if (Security::tooManyAttempts($username)) {
            flash('error', 'Too many failed attempts. Try again in 15 minutes.');
            redirect('login?role=' . urlencode($role));
        }

        $user = Database::one(
            'SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ?',
            [$username]
        );
        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            Security::recordAttempt($username, false);
            flash('error', 'Invalid credentials.');
            redirect('login?role=' . urlencode($role));
        }
        if ($user['status'] !== 'active') {
            flash('error', $user['status'] === 'pending'
                ? 'Your account is pending approval.'
                : 'Your account is not active.');
            redirect('login?role=' . urlencode($role));
        }
        if ($role !== 'student' && $user['role_slug'] !== $role) {
            flash('error', 'Use the correct portal for your role.');
            redirect('login?role=' . urlencode($role));
        }
        Security::recordAttempt($username, true);
        Auth::login((int)$user['id']);
        if ($user['role_slug'] === 'admin') {
            redirect('admin');
        }
        if ($user['role_slug'] === 'lecturer') {
            redirect('lecturer');
        }
        redirect('student');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('');
    }
}
