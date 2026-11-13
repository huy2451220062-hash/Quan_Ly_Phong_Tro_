<?php
session_start();
require_once 'connect.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $username === '' || $password === '' || $confirm === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    } elseif (!preg_match('/^[A-Za-z0-9_]{4,50}$/', $username)) {
        $error = 'Tên đăng nhập 4-50 ký tự, chỉ gồm chữ, số và dấu _.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $phoneExists = false;
        if ($phone !== '') {
            $phoneCheck = $conn->prepare(
                'SELECT id FROM users WHERE phone = ?
                 UNION
                 SELECT id FROM tenants WHERE phone = ?
                 LIMIT 1'
            );
            $phoneCheck->bind_param('ss', $phone, $phone);
            $phoneCheck->execute();
            $phoneExists = (bool)$phoneCheck->get_result()->fetch_assoc();
        }

        if ($phoneExists) {
            $error = 'Số điện thoại đã được sử dụng.';
        } else {
            $h = password_hash($password, PASSWORD_DEFAULT);
            $role = 'tenant';

            try {
                $conn->begin_transaction();

                $s = $conn->prepare(
                    'INSERT INTO users(username, password, full_name, phone, role)
                     VALUES(?, ?, ?, ?, ?)'
                );
                $phoneForDb = $phone === '' ? null : $phone;
                $s->bind_param('sssss', $username, $h, $name, $phoneForDb, $role);
                $s->execute();
                $uid = $s->insert_id;

                $t = $conn->prepare(
                    'INSERT INTO tenants(user_id, full_name, phone)
                     VALUES(?, ?, ?)'
                );
                $t->bind_param('iss', $uid, $name, $phoneForDb);
                $t->execute();

                $conn->commit();
                $success = 'Đăng ký tài khoản Người thuê thành công. Chủ trọ sẽ gán phòng/hợp đồng cho bạn.';
                $_POST = [];
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                if ($e->getCode() === 1062) {
                    $error = 'Số điện thoại đã được sử dụng.';
                } else {
                    $error = 'Không thể đăng ký tài khoản.';
                }
            }

            if ($error === '' && $success === '') {
                $error = 'Không thể đăng ký tài khoản.';
            }
        }
    }
}
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Đăng ký - Trọ Manager</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>
        .reg {
            width: min(460px, 94%);
            background: #fff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 20px 50px #0003;
        }

        .reg h1 {
            text-align: center;
            font-size: 24px;
        }

        .reg label {
            display: block;
            font-weight: bold;
            margin: 12px 0 6px;
        }

        .reg input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .auth-link {
            text-align: center;
            margin-top: 18px;
        }

        .auth-link a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .hint {
            font-size: 13px;
            color: #64748b;
            margin-top: 10px;
        }
    </style>
</head>

<body class="login-page register-page">
    <main class="auth-layout register-layout">
        <section class="auth-form-wrap">
            <div class="auth-card auth-card-wide reg">
                <h1>Đăng ký tài khoản</h1>
                <p class="subtitle">Điền thông tin để tạo tài khoản mới</p>

                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert" style="background:#dcfce7;color:#166534">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form register-form">
                    <div class="register-field full-field">
                        <label>Họ và tên *</label>
                        <input name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="register-field">
                        <label>Số điện thoại</label>
                        <input name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="register-field">
                        <label>Tên đăng nhập *</label>
                        <input name="username" required>
                    </div>
                    <div class="register-field">
                        <label>Mật khẩu *</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <div class="register-field">
                        <label>Nhập lại mật khẩu *</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    <button class="btn primary full" type="submit" style="grid-column:1/-1">ĐĂNG KÝ</button>
                </form>
                <div class="auth-link">Đã có tài khoản? <a href="login.php">Đăng nhập</a></div>
            </div>
        </section>
    </main>
</body>
</html>
