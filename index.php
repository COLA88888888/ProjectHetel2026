<?php
session_start();
if (isset($_SESSION['checked'])) {
    if (isset($_SESSION['status']) && $_SESSION['status'] == "ຜູ້ບໍລິຫານ") {
        header("Location: menu_admin.php");
    } else {
        header("Location: menu_user.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຂົ້າສູ່ລະບົບ - Hotel Management</title>
    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao+Looped:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #007bff;
            --blue-hover: #0069d9;
        }
        body {
            font-family: 'Noto Sans Lao Looped', sans-serif !important;
            margin: 0;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .hero-bg {
            background: linear-gradient(rgba(0, 123, 255, 0.7), rgba(0, 123, 255, 0.7)), url('assets/img/hotel_pool.jpg');
            background-size: cover;
            background-position: center;
            height: 45vh;
            width: 100%;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: -22vh; /* Moved up more */
            z-index: 10;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000; /* Ensure it's on top */
        }
        .lang-icon {
            position: absolute;
            top: 20px;
            right: 25px;
            color: #333;
            font-size: 1.2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 1.6rem;
            margin-bottom: 5px;
        }
        .login-header p {
            color: #888;
            font-size: 0.9rem;
        }
        .form-label {
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: #444;
        }
        .form-control {
            border-radius: 10px;
            padding: 14px 15px;
            border: 1px solid #dee2e6;
            font-size: 1.1rem;
            width: 100%;
            display: block;
            background-color: #f0f7ff;
            transition: all 0.3s;
            box-sizing: border-box;
            font-family: 'Noto Sans Lao Looped', sans-serif !important;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }
        .btn-login {
            background-color: var(--primary-blue);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background-color: var(--blue-hover);
            color: white;
        }
        .remember-me {
            margin-top: 15px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }
        .remember-me input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary-blue);
        }
        .footer-info {
            position: relative;
            padding: 20px 0;
            width: 100%;
            text-align: center;
            color: #999;
            font-size: 0.85rem;
            z-index: 1;
        }
    </style>
</head>
<body>

    <div class="hero-bg"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <!-- <div class="lang-icon">
                <i class="fas fa-globe"></i>
            </div> -->
            
            <div class="login-header">
                <h2>ຍິນດີຕ້ອນຮັບ!</h2>
                <p>ລະບົບບໍລິຫານ ໂຮງແຮມ Hotel Management</p>
            </div>

            <form id="loginForm">
                <div class="form-group mb-3">
                    <label class="form-label">ຊື່ຜູ້ນຳໃຊ້:</label>
                    <input type="text" id="username" class="form-control" placeholder="ປ້ອນຊື່ຜູ້ນຳໃຊ້..." required autofocus>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label">ລະຫັດຜ່ານ:</label>
                    <div class="password-container">
                        <input type="password" id="password" class="form-control" placeholder="...." required style="padding-right: 45px;">
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
<!-- 
                <div class="remember-me">
                    <input type="checkbox" id="remember">
                    <label for="remember" class="mb-0">ຈື່ຂ້ອຍໄວ້ໃນລະບົບ</label>
                </div> -->

                <button type="submit" class="btn-login" id="btnLogin">ເຂົ້າສູ່ລະບົບ</button>
            </form>
        </div>
    </div>

    <div class="footer-info">
        ລະບົບບໍລິຫານ ໂຮງແຮມ Hotel Management V 1.0.0 ພັດທະນາໂດຍ: SoneDev
    </div>

    <!-- Use CDN for maximum reliability -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="sweetalert/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            console.log("Login page ready"); // Debug log

            // Toggle Password
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $('#eyeIcon').toggleClass('fa-eye fa-eye-slash');
            });

            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                var username = $('#username').val();
                var password = $('#password').val();
                var btn = $('#btnLogin');

                if (!username || !password) {
                    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບ!' });
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ກຳລັງກວດສອບ...');

                $.ajax({
                    url: 'Check_user.php',
                    type: 'POST',
                    data: { username: username, password: password },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Login Response:", response); // Debug log
                        if (response.success) {
                            window.location.href = response.redirect;
                        } else {
                            btn.prop('disabled', false).text('ເຂົ້າສູ່ລະບົບ');
                            Swal.fire({
                                icon: 'error',
                                title: 'ຜິດພາດ',
                                text: response.message,
                                confirmButtonColor: '#007bff'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error); // Debug log
                        btn.prop('disabled', false).text('ເຂົ້າສູ່ລະບົບ');
                        Swal.fire({
                            icon: 'error',
                            title: 'ຜິດພາດ',
                            text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບ Server ໄດ້! (Error: ' + error + ')'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>