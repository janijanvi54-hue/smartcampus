<?php
require_once __DIR__ . '/includes/auth.php';

do_logout();
set_flash('success', 'You have been logged out securely.');
redirect('/login.php');
