<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0f5132">

    <title>Create Account | KashHub</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">

    <style>
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif}
        body{background:#f4f7f6;color:#1f2937}
        .auth-page{min-height:100vh;display:flex}
        .auth-ad{width:54%;min-height:100vh;position:relative;overflow:hidden;background:linear-gradient(135deg,#063d2c 0%,#0f6b4a 55%,#149765 100%);color:#fff;padding:42px 55px;display:flex;flex-direction:column}
        .auth-ad:before,.auth-ad:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.06)}
        .auth-ad:before{width:430px;height:430px;right:-180px;top:-130px}
        .auth-ad:after{width:310px;height:310px;left:-130px;bottom:-100px}
        .brand{position:relative;z-index:2;display:flex;align-items:center;gap:12px}
        .brand-logo{width:48px;height:48px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden}
        .brand-logo img{max-width:100%;max-height:100%;object-fit:contain}
        .brand-fallback{font-size:24px;font-weight:800;color:#0f6b4a}
        .brand-text h2{font-size:22px;margin:0;font-weight:800}
        .brand-text small{display:block;color:rgba(255,255,255,.72);font-size:12px;margin-top:2px}
        .ad-content{position:relative;z-index:2;max-width:630px;margin:auto 0}
        .ad-tag{display:inline-block;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:7px 13px;border-radius:20px;font-size:12px;margin-bottom:20px}
        .ad-slide{display:none;animation:fadeSlide .5s ease}
        .ad-slide.active{display:block}
        .ad-slide h1{font-size:42px;line-height:1.13;font-weight:800;margin:0 0 17px}
        .ad-slide p{font-size:16px;line-height:1.7;color:rgba(255,255,255,.83);max-width:570px;margin:0 0 24px}
        .ad-features{display:flex;flex-wrap:wrap;gap:9px}
        .ad-feature{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.13);padding:9px 13px;border-radius:5px;font-size:13px}
        .ad-dots{position:relative;z-index:2;display:flex;gap:7px;margin-top:28px}
        .ad-dot{width:8px;height:8px;border-radius:50%;border:0;background:rgba(255,255,255,.35);padding:0;cursor:pointer;transition:.2s}
        .ad-dot.active{width:24px;border-radius:10px;background:#fff}
        .ad-footer{position:relative;z-index:2;font-size:12px;color:rgba(255,255,255,.6);margin-top:auto;padding-top:25px}
        .auth-register{width:46%;min-height:100vh;background:#fff;display:flex;align-items:center;justify-content:center;padding:35px 45px}
        .register-box{width:100%;max-width:440px}
        .mobile-brand{display:none;text-align:center;margin-bottom:20px}
        .register-header{margin-bottom:22px}
        .register-header .welcome{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#16835d;margin-bottom:7px}
        .register-header h3{font-size:28px;font-weight:800;margin:0 0 7px;color:#111827}
        .register-header p{margin:0;color:#6b7280;font-size:13px;line-height:1.6}
        .response-box{display:none;padding:11px 13px;margin-bottom:16px;border-radius:4px;font-size:13px;line-height:1.5}
        .response-success{background:#e9f8f1;color:#126342;border:1px solid #bfe9d4}
        .response-danger{background:#fff0f0;color:#a52a2a;border:1px solid #f1c5c5}
        .input_box{margin-bottom:14px}
        .input_box label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#374151}
        .input-wrap{position:relative}
        .input_box input{width:100%;height:47px;border:1px solid #d5dbe1;border-radius:4px;padding:0 45px 0 14px;font-size:14px;outline:none;background:#fff;transition:.2s}
        .input_box input:focus{border-color:#16835d;box-shadow:0 0 0 3px rgba(22,131,93,.09)}
        .input_box input.input-error{border-color:#dc3545}
        .input_box input.input-success{border-color:#16835d}
        .input-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:15px;pointer-events:none}
        .toggle-password{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:none;color:#6b7280;padding:5px;cursor:pointer;font-size:11px;font-weight:700}
        .field-note{display:block;margin-top:5px;font-size:11px;color:#8a939e;line-height:1.4}
        .field-error{display:none;margin-top:5px;font-size:11px;color:#c53030}
        .password-strength{margin-top:7px}
        .strength-bars{display:flex;gap:4px}
        .strength-bar{height:3px;flex:1;background:#e5e7eb;border-radius:3px;transition:.2s}
        .strength-bar.active{background:#16835d}
        .strength-text{font-size:10px;color:#8a939e;margin-top:4px}
        .register-btn{width:100%;height:48px;border:0;border-radius:4px;background:#126c4d;color:#fff;font-size:14px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:5px}
        .register-btn:hover{background:#0c573d}
        .register-btn:disabled{opacity:.7;cursor:not-allowed}
        .spinner{display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
        .register-btn.loading .spinner{display:inline-block}
        .login-link{text-align:center;margin-top:18px;font-size:13px;color:#6b7280}
        .login-link a{color:#126c4d;font-weight:700;text-decoration:none}
        .terms{text-align:center;margin-top:15px;font-size:10px;color:#9ca3af;line-height:1.5}
        .mobile-ad{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        @keyframes fadeSlide{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

        @media(max-width:991px){
            .auth-ad{width:46%;padding:35px}
            .auth-register{width:54%;padding:30px}
            .ad-slide h1{font-size:33px}
        }

        @media(max-width:767px){
            .auth-page{display:block;background:#fff}
            .auth-ad{display:none}
            .auth-register{width:100%;min-height:100vh;padding:20px;align-items:flex-start}
            .register-box{max-width:480px;margin:10px auto}
            .mobile-brand{display:block}
            .mobile-brand h2{font-size:25px;font-weight:800;color:#126c4d;margin:5px 0}
            .mobile-brand small{color:#6b7280}
            .mobile-ad{display:block;background:#edf8f3;border-left:4px solid #16835d;padding:11px 13px;margin-bottom:20px;border-radius:3px}
            .mobile-ad strong{display:block;color:#126c4d;font-size:13px;margin-bottom:3px}
            .mobile-ad span{font-size:11px;color:#637169;line-height:1.5}
            .register-header h3{font-size:24px}
        }

        @media(max-width:400px){
            .auth-register{padding:15px}
        }
    </style>
</head>

<body>

<div class="auth-page">

    <!-- MARKETING / ADVERTISEMENT AREA -->
    <section class="auth-ad">

        <div class="brand">

            <div class="brand-logo">
                <img src="../assets/images/kashhub/logo.png"
                     alt="KashHub"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block'">

                <span class="brand-fallback" style="display:none">K</span>
            </div>

            <div class="brand-text">
                <h2>KashHub</h2>
                <small>Business Manager</small>
            </div>

        </div>

        <div class="ad-content">

            <div class="ad-tag">START MANAGING SMARTER</div>

            <!-- SLIDE 1 -->
            <div class="ad-slide active">

                <h1>Give your business a clearer financial picture.</h1>

                <p>
                    Create your KashHub account and bring your cash,
                    expenses, stock and customer records together in
                    one organised business workspace.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Cash In & Out</span>
                    <span class="ad-feature">Expenses</span>
                    <span class="ad-feature">Business Balance</span>
                    <span class="ad-feature">Reports</span>
                </div>

            </div>

            <!-- SLIDE 2 -->
            <div class="ad-slide">

                <h1>Stop guessing what is happening in your business.</h1>

                <p>
                    Keep useful records and turn everyday transactions
                    into information that can help you understand your
                    business performance.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Daily Reports</span>
                    <span class="ad-feature">Monthly Trends</span>
                    <span class="ad-feature">Balances</span>
                    <span class="ad-feature">Business Insights</span>
                </div>

            </div>

            <!-- SLIDE 3 -->
            <div class="ad-slide">

                <h1>Manage more than just cash.</h1>

                <p>
                    Keep track of stock, customers, creditors and
                    business routes from the same KashHub account.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Stock Management</span>
                    <span class="ad-feature">Customers</span>
                    <span class="ad-feature">Creditors</span>
                    <span class="ad-feature">Routes</span>
                </div>

            </div>

            <!-- SLIDE 4 -->
            <div class="ad-slide">

                <h1>Built to grow with your business.</h1>

                <p>
                    Start with your first business book and build a
                    structured record of your operations as your
                    business grows.
                </p>

                <div class="ad-features">
                    <span class="ad-feature">Multiple Books</span>
                    <span class="ad-feature">Team Access</span>
                    <span class="ad-feature">Invoices</span>
                    <span class="ad-feature">Business Profile</span>
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
            KashHub • Powered by EDUCITECH SOLUTIONS
        </div>

    </section>

    <!-- REGISTRATION AREA -->
    <section class="auth-register">

        <div class="register-box">

            <div class="mobile-brand">
                <h2>KashHub</h2>
                <small>Business Manager</small>
            </div>

            <div class="mobile-ad">
                <strong>Start managing your business smarter.</strong>
                <span>
                    Track money, stock, customers and business performance
                    from one organised workspace.
                </span>
            </div>

            <div class="register-header">

                <div class="welcome">Create your account</div>

                <h3>Get started with KashHub</h3>

                <p>
                    Create your account to start organising and managing
                    your business records.
                </p>

            </div>

            <form id="managerRegisterForm" method="POST" autocomplete="on">

                <input type="hidden" name="action" value="RegisterUser">

                <!-- Keep role assignment server-side if possible -->
                <input type="hidden" name="role_id" value="1">

                <div class="response-box response-success"></div>
                <div class="response-box response-danger"></div>

                <!-- NAME -->
                <div class="input_box">

                    <label for="name">Full name</label>

                    <div class="input-wrap">

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            minlength="2"
                            required
                        >

                        <span class="input-icon">●</span>

                    </div>

                </div>

                <!-- EMAIL -->
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

                    <span class="field-note">
                        This email will be used to access your KashHub account.
                    </span>

                </div>

                <!-- PASSWORD -->
                <div class="input_box">

                    <label for="password1">Create password</label>

                    <div class="input-wrap">

                        <input
                            type="password"
                            id="password1"
                            name="password1"
                            placeholder="Create a strong password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password1"
                        >
                            SHOW
                        </button>

                    </div>

                    <div class="password-strength">

                        <div class="strength-bars">
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                            <span class="strength-bar"></span>
                        </div>

                        <div class="strength-text" id="strengthText">
                            Use at least 8 characters.
                        </div>

                    </div>

                </div>

                <!-- CONFIRM PASSWORD -->
                <div class="input_box">

                    <label for="password2">Confirm password</label>

                    <div class="input-wrap">

                        <input
                            type="password"
                            id="password2"
                            name="password2"
                            placeholder="Repeat your password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password2"
                        >
                            SHOW
                        </button>

                    </div>

                    <span class="field-error" id="passwordError">
                        Passwords do not match.
                    </span>

                </div>

                <button type="submit" class="register-btn" id="registerBtn">

                    <span class="spinner"></span>

                    <span class="btn-text">
                        Create Account
                    </span>

                </button>

                <div class="login-link">
                    Already have an account?
                    <a href="./">Log in</a>
                </div>

                <div class="terms">
                    By creating an account, you agree to use KashHub
                    responsibly and keep your account credentials secure.
                </div>

            </form>

        </div>

    </section>

</div>

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded",function(){

    const form=document.getElementById("managerRegisterForm");
    const password1=document.getElementById("password1");
    const password2=document.getElementById("password2");
    const passwordError=document.getElementById("passwordError");
    const registerBtn=document.getElementById("registerBtn");
    const btnText=registerBtn.querySelector(".btn-text");
    const successBox=document.querySelector(".response-success");
    const errorBox=document.querySelector(".response-danger");
    const strengthBars=document.querySelectorAll(".strength-bar");
    const strengthText=document.getElementById("strengthText");

    /*
    |----------------------------------------------------------
    | SHOW / HIDE PASSWORD
    |----------------------------------------------------------
    */

    document.querySelectorAll(".toggle-password").forEach(function(button){

        button.addEventListener("click",function(){

            const target=document.getElementById(
                this.dataset.target
            );

            const visible=target.type==="text";

            target.type=visible ? "password" : "text";

            this.textContent=visible ? "SHOW" : "HIDE";

        });

    });

    /*
    |----------------------------------------------------------
    | PASSWORD STRENGTH
    |----------------------------------------------------------
    */

    password1.addEventListener("input",function(){

        const password=this.value;

        let strength=0;

        if(password.length>=8) strength++;
        if(/[A-Z]/.test(password)&&/[a-z]/.test(password)) strength++;
        if(/[0-9]/.test(password)) strength++;
        if(/[^A-Za-z0-9]/.test(password)) strength++;

        strengthBars.forEach(function(bar,index){

            if(index<strength){
                bar.classList.add("active");
            }else{
                bar.classList.remove("active");
            }

        });

        const messages=[
            "Use at least 8 characters.",
            "Password is weak.",
            "Password is fair.",
            "Password is good.",
            "Strong password."
        ];

        strengthText.textContent=messages[strength];

        checkPasswords();

    });

    /*
    |----------------------------------------------------------
    | PASSWORD MATCHING
    |----------------------------------------------------------
    */

    function checkPasswords(){

        if(password2.value===""){

            password2.classList.remove(
                "input-error",
                "input-success"
            );

            passwordError.style.display="none";

            return true;
        }

        if(password1.value!==password2.value){

            password2.classList.add("input-error");
            password2.classList.remove("input-success");

            passwordError.style.display="block";

            return false;
        }

        password2.classList.remove("input-error");
        password2.classList.add("input-success");

        passwordError.style.display="none";

        return true;

    }

    password2.addEventListener(
        "input",
        checkPasswords
    );

    /*
    |----------------------------------------------------------
    | REGISTER USER
    |----------------------------------------------------------
    */

    form.addEventListener("submit",async function(e){

        e.preventDefault();

        successBox.style.display="none";
        errorBox.style.display="none";

        /*
        | Browser validation
        */

        if(!form.checkValidity()){

            form.reportValidity();

            return;

        }

        /*
        | Password matching
        */

        if(password1.value!==password2.value){

            passwordError.style.display="block";

            password2.classList.add("input-error");

            password2.focus();

            return;

        }

        /*
        | Minimum password length
        */

        if(password1.value.length<8){

            errorBox.textContent=
                "Your password must contain at least 8 characters.";

            errorBox.style.display="block";

            password1.focus();

            return;

        }

        registerBtn.disabled=true;
        registerBtn.classList.add("loading");

        btnText.textContent="Creating account...";

        try{

            const response=await fetch("save/index.php",{

                method:"POST",

                body:new FormData(form),

                headers:{
                    "X-Requested-With":"XMLHttpRequest"
                }

            });

            /*
            | Read text first because PHP warnings/errors can
            | otherwise cause response.json() to fail.
            */

            const raw=await response.text();

            let data;

            try{

                data=JSON.parse(raw);

            }catch(e){

                console.error(
                    "Invalid registration response:",
                    raw
                );

                throw new Error(
                    "Registration server returned invalid JSON."
                );

            }

            /*
            | SUCCESS
            */

            if(data.access==1){

                successBox.textContent=
                    data.message ||
                    "Your account has been created successfully.";

                successBox.style.display="block";

                btnText.textContent="Account created";

                /*
                | Redirect shortly after successful registration
                */

                setTimeout(function(){

                    if(data.link){

                        window.location.href=data.link;

                    }else{

                        window.location.href="./";

                    }

                },1200);

                return;

            }

            /*
            | SERVER REJECTED REGISTRATION
            */

            errorBox.textContent=
                data.message ||
                "Registration failed. Please check your details.";

            errorBox.style.display="block";

            registerBtn.disabled=false;
            registerBtn.classList.remove("loading");

            btnText.textContent="Create Account";

        }catch(error){

            console.error(error);

            errorBox.textContent=
                "We could not create your account. Please try again.";

            errorBox.style.display="block";

            registerBtn.disabled=false;
            registerBtn.classList.remove("loading");

            btnText.textContent="Create Account";

        }

    });

    /*
    |----------------------------------------------------------
    | ADVERTISEMENT SLIDER
    |----------------------------------------------------------
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