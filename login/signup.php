<!DOCTYPE html>
<!-- Source Codes By CodingNepal - www.codingnepalweb.com -->
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BUSINESS MANAGER</title>
  <link rel="stylesheet" href="../assets/css/logcss.css" />
</head>
<body>
  <div class="login_form">
    <!-- Login form container -->
    <form id='managerRegisterForm' method='POST'>
      <h3>REGISTER</h3>
      <!-- Login option separator -->
      <p class="separator">
        <span></span>
      </p>
      <!-- Email input box -->
       <input type="hidden" name="action" value="RegisterUser">
      <div class="input_box">
        <label for="name">NAME</label>
        <input type="text" id="name" name='name' placeholder="Enter name here" required />
        <input type="hidden" name="role_id" value='1'>
      </div>
      <div class="input_box">
        <label for="email">EMAIL</label>
        <input type="email" id="email" name='email' placeholder="Enter email address" required />
      </div>
      <div class="input_box">
        <label for="email">PASSWORD</label>
        <input type="password" id="password1" name='password1' placeholder="Enter password" required />
      </div>
      <div class="input_box">
        <label for="password2">REPEAT PASSWORD</label>
        <input type="password" id="password2" name='password2' placeholder="Repeat password" required />
      </div>
       <!-- Login button -->
      <button type="submit">Register</button>
      <p class="sign_up">Already have an account? <a href="./">Login</a></p>
    </form>
  </div>
    <script src="../assets/js/jquery-3.5.1.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        // submit register form
        document.addEventListener("DOMContentLoaded", function () {

            const form = document.getElementById("managerRegisterForm");
            if (!form) return;

            form.addEventListener("submit", function (e) {
                e.preventDefault(); // 🔥 stops page reload
                const formData = new FormData(form);
                fetch("save/index.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json()) // 🔥 expect JSON
                .then(response => {
                    console.log(response);

                    if (response.access === 1) {
                        window.location.href = response.link;
                    } else {
                        alert(response.message ?? "Registration failed");
                    }
                })
                .catch(err => console.error("AJAX error:", err));
            });

        });
    </script>
</body>
</html>