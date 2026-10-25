<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\View;

final class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            $role = Auth::role();
            if ($role === 'admin') {
                redirect('admin');
            }
            if ($role === 'lecturer') {
                redirect('lecturer');
            }
            if ($role === 'student') {
                redirect('student');
            }
        }
        View::render('home/welcome', [
            'title' => 'TSU-SAMS',
        ], 'layouts/guest');
    }

    public function help(): void
    {
        View::render('home/help', ['title' => 'Helpdesk'], 'layouts/guest');
    }
}
