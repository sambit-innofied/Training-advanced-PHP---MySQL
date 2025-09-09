<?php

require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthController
{
    protected $pdo;
    protected $userModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->userModel = new UserRepository($pdo);
    }

    // Show login form
    public function login()
    {
        // If user is already logged in, redirect to home
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $errors = $_SESSION['login_errors'] ?? [];
        unset($_SESSION['login_errors']);

        include __DIR__ . '/../views/auth/login.php';
    }

    // Process login
    public function authenticate()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            $_SESSION['login_errors'] = $errors;
            header('Location: /login');
            exit;
        }

        // Check user credentials via repository
        $user = $this->userModel->findByUsername($username);

        if ($user && password_verify($password, $user->getPasswordHash())) {
            // Set session variables using the entity getters
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['email'] = $user->getEmail();
            $_SESSION['role'] = $user->getRole(); // Store the role
            $_SESSION['logged_in'] = true;

            // Redirect to home page
            header('Location: /');
            exit;

        } else {
            $_SESSION['login_errors'] = ['general' => 'Invalid username or password.'];
            header('Location: /login');
            exit;
        }
    }

    // Logout user
    public function logout()
    {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();

        // Redirect to login page
        header('Location: /login');
        exit;
    }

    // Show registration form
    public function register()
    {
        // If user is already logged in, redirect to home
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $errors = $_SESSION['register_errors'] ?? [];
        $old = $_SESSION['register_old'] ?? [];

        unset($_SESSION['register_errors']);
        unset($_SESSION['register_old']);

        include __DIR__ . '/../views/auth/register.php';
    }

    // Process registration
    public function storeUser()
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        $errors = [];
        $old = ['username' => $username, 'email' => $email];

        // Validation
        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        // Check if username or email already exists via repository
        if (!$errors) {
            if ($this->userModel->existsByUsernameOrEmail($username, $email)) {
                $errors['general'] = 'Username or email already exists.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_old'] = $old;
            header('Location: /register');
            exit;
        }

        // Hash password and create user using repository (pass entity)
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $defaultRole = 'customer';

        try {
            // Create entity and pass to repository create()
            $newUserEntity = new UserModel(null, $username, $email, $password_hash, $defaultRole);
            $user_id = $this->userModel->create($newUserEntity);
        } catch (Exception $e) {
            // If DB error occurs, show generic message (preserve behavior)
            $_SESSION['register_errors'] = ['general' => 'Failed to create user: ' . $e->getMessage()];
            $_SESSION['register_old'] = $old;
            header('Location: /register');
            exit;
        }

        // Automatically log in the user after registration
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;
        $_SESSION['role'] = $defaultRole;

        header('Location: /');
        exit;
    }
}
