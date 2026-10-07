<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0f5132">
    <title>KashHub | Business Manager</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">

    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif}
        body{background:#f4f7f6;color:#1f2937}
        .auth-page{min-height:100vh;display:flex}
        .auth-ad{width:58%;min-height:100vh;position:relative;overflow:hidden;background:linear-gradient(135deg,#063d2c 0%,#0f6b4a 55%,#149765 100%);color:#fff;padding:45px 60px;display:flex;flex-direction:column}
        .auth-ad:before,.auth-ad:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.06)}
        .auth-ad:before{width:420px;height:420px;right:-170px;top:-120px}
        .auth-ad:after{width:300px;height:300px;left:-120px;bottom:-100px}
        .brand{position:relative;z-index:2;display:flex;align-items:center;gap:12px}
        .brand-logo{width:48px;height:48px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden}
        .brand-logo img{max-width:100%;max-height:100%;object-fit:contain}
        .brand-fallback{font-size:24px;font-weight:800;color:#0f6b4a}
        .brand-text h2{font-size:22px;margin:0;font-weight:800}
        .brand-text small{display:block;color:rgba(255,255,255,.72);font-size:12px;margin-top:2px}
        .ad-content{position:relative;z-index:2;max-width:650px;margin:auto 0}
        .ad-tag{display:inline-block;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:7px 13px;border-radius:20px;font-size:12px;margin-bottom:20px}
        .ad-slide{display:none;animation:fadeSlide .5s ease}
        .ad-slide.active{display:block}
        .ad-slide h1{font-size:44px;line-height:1.12;font-weight:800;margin:0 0 18px}
        .ad-slide p{font-size:17px;line-height:1.7;color:rgba(255,255,255,.83);max-width:580px;margin:0 0 25px}
        .ad-features{display:flex;flex-wrap:wrap;gap:10px}
        .ad-feature{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.13);padding:9px 13px;border-radius:5px;font-size:13px}
        .ad-dots{position:relative;z-index:2;display:flex;gap:7px;margin-top:30px}
        .ad-dot{width:8px;height:8px;border-radius:50%;border:0;background:rgba(255,255,255,.35);padding:0;cursor:pointer}
        .ad-dot.active{width:24px;border-radius:10px;background:#fff}
        .ad-footer{position:relative;z-index:2;font-size:12px;color:rgba(255,255,255,.6);margin-top:auto;padding-top:30px}
        .auth-login{width:42%;min-height:100vh;background:#fff;display:flex;align-items:center;justify-content:center;padding:45px}
        .login-box{width:100%;max-width:420px}
        .mobile-brand{display:none;text-align:center;margin-bottom:30px}
        .login-header{margin-bottom:30px}
        .login-header .welcome{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#16835d;margin-bottom:8px}
        .login-header h3{font-size:29px;font-weight:800;margin:0 0 8px;color:#111827}
        .login-header p{margin:0;color:#6b7280;font-size:14px;line-height:1.6}
        .response-box{display:none;padding:11px 13px;margin-bottom:18px;border-radius:4px;font-size:13px;line-height:1.5}
        .response-success{background:#e9f8f1;color:#126342;border:1px solid #bfe9d4}
        .response-danger{background:#fff0f0;color:#a52a2a;border:1px solid #f1c5c5}
        .input_box{margin-bottom:18px}
        .input_box label{display:block;font-size:13px;font-weight:700;margin-bottom:7px;color:#374151}
        .input-wrap{position:relative}
        .input_box input{width:100%;height:49px;border:1px solid #d5dbe1;border-radius:4px;padding:0 44px 0 14px;font-size:14px;outline:none;background:#fff;transition:.2s}
        .input_box input:focus{border-color:#16835d;box-shadow:0 0 0 3px rgba(22,131,93,.09)}
        .input-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:17px;pointer-events:none}
        .toggle-password{position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:none;color:#6b7280;padding:5px;cursor:pointer;font-size:12px}
        .password-title{display:flex;justify-content:space-between;align-items:center}
        .password-title a{font-size:12px;color:#16835d;text-decoration:none}
        .login-btn{width:100%;height:49px;border:0;border-radius:4px;background:#126c4d;color:#fff;font-size:14px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px}
        .login-btn:hover{background:#0c573d}
        .login-btn:disabled{opacity:.7;cursor:not-allowed}
        .spinner{display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
        .login-btn.loading .spinner{display:inline-block}
        .login-links{text-align:center;margin-top:22px;font-size:13px;color:#6b7280}
        .login-links a{color:#126c4d;font-weight:700;text-decoration:none}
        .security-note{margin-top:28px;padding-top:18px;border-top:1px solid #eee;text-align:center;font-size:11px;color:#9ca3af;line-height:1.6}
        .mobile-ad{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        @keyframes fadeSlide{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

        @media(max-width:991px){
            .auth-ad{width:48%;padding:35px}
            .auth-login{width:52%;padding:35px}
            .ad-slide h1{font-size:34px}
        }

        @media(max-width:767px){
            .auth-page{display:block;background:#fff}
            .auth-ad{display:none}
            .auth-login{width:100%;min-height:100vh;padding:25px 20px;align-items:flex-start}
            .login-box{max-width:480px;margin:20px auto}
            .mobile-brand{display:block}
            .mobile-brand h2{font-size:25px;font-weight:800;color:#126c4d;margin:5px 0}
            .mobile-brand small{color:#6b7280}
            .mobile-ad{display:block;background:#edf8f3;border-left:4px solid #16835d;padding:12px 14px;margin-bottom:25px;border-radius:3px}
            .mobile-ad strong{display:block;color:#126c4d;font-size:13px;margin-bottom:3px}
            .mobile-ad span{font-size:12px;color:#637169;line-height:1.5}
            .login-header h3{font-size:25px}
        }

        @media(max-width:400px){
            .auth-login{padding:15px}
            .login-box{margin:10px auto}
        }
    </style>
</head>

<body>

<div class="auth-page">

    <!-- ADVERT / BRANDING SIDE -->
    <section class="auth-ad">

        <div class="brand">
            <div class="brand-logo">
                <img src="../assets/images/kashhub/logo.png" alt="KashHub" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <span class="brand-fallback" style="display:none">K</span>
            </div>

            <div class="brand-text">
                <h2>KashHub</h2>
                <small>Business Manager</small>
            </div>
        </div>

        <div class="ad-content">

            <div class="ad-tag">SMARTER BUSINESS MANAGEMENT</div>

            <div class="ad-slide active">
                <h1>Know where your money goes.</h1>

                <p>
                    Record cash in, cash out and business expenses while
                    maintaining a clear view of your financial position.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Cashbook</span>
                    <span class="ad-feature">Daily Reports</span>
                    <span class="ad-feature">Payment Modes</span>
                    <span class="ad-feature">Financial Tracking</span>
                </div>
            </div>

            <div class="ad-slide">
                <h1>Keep your stock under control.</h1>

                <p>
                    Track stock movement, quantities, products and business
                    inventory from one organised workspace.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Stock In</span>
                    <span class="ad-feature">Stock Out</span>
                    <span class="ad-feature">Low Stock</span>
                    <span class="ad-feature">Product Tracking</span>
                </div>
            </div>

            <div class="ad-slide">
                <h1>Understand your customers better.</h1>

                <p>
                    Manage customers, credit transactions, balances, routes
                    and collections without losing track of important records.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Customers</span>
                    <span class="ad-feature">Creditors</span>
                    <span class="ad-feature">Routes</span>
                    <span class="ad-feature">Balances</span>
                </div>
            </div>

            <div class="ad-slide">
                <h1>Your business. One clear dashboard.</h1>

                <p>
                    Turn everyday business records into useful information
                    that helps you understand performance and make better decisions.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Sales</span>
                    <span class="ad-feature">Expenses</span>
                    <span class="ad-feature">Reports</span>
                    <span class="ad-feature">Business Insights</span>
                </div>
            </div>

            <div class="ad-dots">
                <button type="button" class="ad-dot active" data-slide="0" aria-label="Advert 1"></button>
                <button type="button" class="ad-dot" data-slide="1" aria-label="Advert 2"></button>
                <button type="button" class="ad-dot" data-slide="2" aria-label="Advert 3"></button>
                <button type="button" class="ad-dot" data-slide="3" aria-label="Advert 4"></button>
            </div>

        </div>

        <div class="ad-footer">
            Powered by EDUCITECH SOLUTIONS
        </div>

    </section>

    <!-- LOGIN SIDE -->
    <section class="auth-login">

        <div class="login-box">

            <div class="mobile-brand">
                <h2>KashHub</h2>
                <small>Business Manager</small>
            </div>

            <div class="mobile-ad">
                <strong>Manage your business smarter.</strong>
                <span>Cashbook, stock, customers, creditors and business reports in one place.</span>
            </div>

            <div class="login-header">
                <div class="welcome">Welcome back</div>

                <h3>Sign in to your account</h3>

                <p>
                    Enter your account details to access your business workspace.
                </p>
            </div>

            <form id="managerLoginForm" method="POST" autocomplete="on">

                <input type="hidden" name="action" value="LoginUser">

                <div class="response-box response-success"></div>
                <div class="response-box response-danger"></div>

                <div class="input_box">
                    <label for="email">Email address</label>

                    <div class="input-wrap">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >

                        <span class="input-icon">@</span>
                    </div>
                </div>

                <div class="input_box">

                    <div class="password-title">
                        <label for="password">Password</label>
                    </div>

                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            SHOW
                        </button>
                    </div>

                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Log In</span>
                </button>

                <div class="login-links">
                    Don't have an account?
                    <a href="signup.php">Create account</a>
                </div>

                <div class="security-note">
                    Your account credentials are securely transmitted to KashHub.
                    Never share your password with anyone.
                </div>

            </form>

        </div>

    </section>

</div>

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded",function(){

    const form=document.getElementById("managerLoginForm");
    const loginBtn=document.getElementById("loginBtn");
    const btnText=loginBtn.querySelector(".btn-text");
    const password=document.getElementById("password");
    const togglePassword=document.getElementById("togglePassword");

    const successBox=document.querySelector(".response-success");
    const errorBox=document.querySelector(".response-danger");

    /*
    |------------------------------------------------------------
    | PASSWORD VISIBILITY
    |------------------------------------------------------------
    */

    togglePassword.addEventListener("click",function(){

        const visible=password.type==="text";

        password.type=visible ? "password" : "text";

        this.textContent=visible ? "SHOW" : "HIDE";

        this.setAttribute(
            "aria-label",
            visible ? "Show password" : "Hide password"
        );

    });

    /*
    |------------------------------------------------------------
    | LOGIN
    |------------------------------------------------------------
    */

    form.addEventListener("submit",async function(e){

        e.preventDefault();

        successBox.style.display="none";
        errorBox.style.display="none";

        loginBtn.disabled=true;
        loginBtn.classList.add("loading");
        btnText.textContent="Signing in...";

        try{

            const response=await fetch("save/index.php",{
                method:"POST",
                body:new FormData(form),
                headers:{
                    "X-Requested-With":"XMLHttpRequest"
                }
            });

            /*
            | Read as text first.
            | This prevents the entire script from failing when PHP
            | accidentally returns HTML/warnings instead of JSON.
            */

            const raw=await response.text();

            let data;

            try{
                data=JSON.parse(raw);
            }catch(e){

                console.error("Invalid server response:",raw);

                throw new Error(
                    "Login server returned an invalid response."
                );
            }

            if(data.access==1){

                successBox.textContent=
                    data.message || "Login successful.";

                successBox.style.display="block";

                btnText.textContent="Login successful";

                setTimeout(function(){

                    successBox.textContent="Redirecting...";

                    if(data.link){
                        window.location.replace(data.link);
                    }else{
                        window.location.reload();
                    }

                },1000);

                return;
            }

            errorBox.textContent=
                data.message || "Incorrect email or password.";

            errorBox.style.display="block";

            loginBtn.disabled=false;
            loginBtn.classList.remove("loading");
            btnText.textContent="Log In";

        }catch(error){

            console.error(error);

            errorBox.textContent=
                "We could not complete your login. Please try again.";

            errorBox.style.display="block";

            loginBtn.disabled=false;
            loginBtn.classList.remove("loading");
            btnText.textContent="Log In";

        }

    });

    /*
    |------------------------------------------------------------
    | ADVERTISEMENT SLIDER
    |------------------------------------------------------------
    */

    const slides=document.querySelectorAll(".ad-slide");
    const dots=document.querySelectorAll(".ad-dot");

    let currentSlide=0;
    let advertTimer=null;

    function showSlide(index){

        slides.forEach(function(slide){
            slide.classList.remove("active");
        });

        dots.forEach(function(dot){
            dot.classList.remove("active");
        });

        slides[index].classList.add("active");
        dots[index].classList.add("active");

        currentSlide=index;

    }

    function startAdvert(){

        advertTimer=setInterval(function(){

            let next=currentSlide+1;

            if(next>=slides.length){
                next=0;
            }

            showSlide(next);

        },6000);

    }

    dots.forEach(function(dot){

        dot.addEventListener("click",function(){

            clearInterval(advertTimer);

            showSlide(
                parseInt(this.dataset.slide)
            );

            startAdvert();

        });

    });

    startAdvert();

});
</script>

</body>
</html>