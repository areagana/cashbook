<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BUSINESS MANAGER</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/custom.css" />
  <link rel="stylesheet" href="../assets/css/logcss.css" />
  
</head>
<body>
  <div class="login_form">
    <!-- Login form container -->
    <form id='managerLoginForm' type='POST'>
      <h3>LOGIN</h3>
      <!-- Login option separator -->
      <p class="separator">
        <span></span>
      </p>
      <!-- Email input box -->
       <input type="hidden" name="action" value="LoginUser">
       <div class="p-2 response-message text-center bg-success hidden"></div>
       <div class="p-2 response-message-danger text-center bg-danger hidden"></div>
      <div class="input_box">
        <label for="email">Email</label>
        <input type="email" id="email" name='email' placeholder="Enter email address" required />
      </div>
      <!-- Paswwrod input box -->
      <div class="input_box">
        <div class="password_title">
          <label for="password">Password</label>
          <a href="#">Forgot Password?</a>
        </div>
        <input type="password" id="password"  name='password' placeholder="Enter your password" required />
      </div>
       <!-- Login button -->
      <button type="submit" class='LoginFormBtn'>Log In</button>
      <p class="sign_up">Don't have an account? <a href="signup.php">Sign up</a></p>
    </form>
  </div>
    <script src="../assets/js/jquery-3.5.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        // submit register form
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("managerLoginForm");
            if (!form) return;
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const formData = new FormData(form);

                fetch("save/index.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json()) // 🔥 expect JSON
                .then(response => {
                    if (response.access === 1) {
                        $('.response-message').show();
                        $('.response-message').html(response.message);
                        // redirect user to the right page
                        setTimeout(function() {
                            $('.response-message').html("Redirecting...");
                        }, 1500); // Redirect after 1.5 seconds

                        setTimeout(function() {
                            window.location.replace(response.link);
                        }, 2000); // Redirect after 1.5 seconds
                    } else {
                       $('.response-message-danger').show();
                        $('.response-message-danger').html(response.message);
                    }
                })
                .catch(err => console.error(err));
            });

        });
    </script>
</body>
</html>