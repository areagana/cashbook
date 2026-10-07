<?php
require_once(__dir__.'/../assets/functions.php');

if(isVerified())
{
    if(checkBusinessProfile())
    {
        redirect('../');
    }
    else
    {
        pageHeader('Set Up Business');
?>
<style>
.business-setup{
    --primary:#2563eb;
    --primary-dark:#1d4ed8;
    --text:#172033;
    --muted:#6b7280;
    --border:#e5e7eb;
    --surface:#fff;
    --soft:#f8fafc;
    --success:#16a34a;
    min-height:calc(100vh - 60px);
    padding:30px 15px 50px;
    background:#f5f7fb;
}
.business-setup *{box-sizing:border-box}
.setup-container{max-width:1050px;margin:auto}
.setup-header{text-align:center;margin-bottom:25px}
.setup-brand{
    display:inline-flex;
    align-items:center;
    gap:10px;
    margin-bottom:12px;
    color:var(--primary);
    font-size:14px;
    font-weight:700;
    letter-spacing:.5px;
    text-transform:uppercase;
}
.setup-brand img{width:32px;height:32px;object-fit:contain}
.setup-header h1{
    color:var(--text);
    font-size:28px;
    font-weight:700;
    margin:0 0 8px;
}
.setup-header p{
    max-width:580px;
    margin:auto;
    color:var(--muted);
    font-size:14px;
    line-height:1.6;
}
.setup-progress{
    display:flex;
    justify-content:center;
    align-items:center;
    margin:22px auto 0;
    max-width:460px;
}
.progress-item{
    display:flex;
    align-items:center;
    color:#9ca3af;
    font-size:12px;
    font-weight:600;
}
.progress-item.active{color:var(--primary)}
.progress-number{
    width:28px;
    height:28px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:2px solid #d1d5db;
    background:#fff;
    margin-right:7px;
    font-size:12px;
    font-weight:700;
}
.progress-item.active .progress-number{
    border-color:var(--primary);
    background:var(--primary);
    color:#fff;
}
.progress-line{
    height:2px;
    width:50px;
    background:#dfe3e8;
    margin:0 12px;
}
.setup-card{
    background:var(--surface);
    border:1px solid var(--border);
    box-shadow:0 8px 30px rgba(15,23,42,.06);
}
.setup-grid{
    display:grid;
    grid-template-columns:300px 1fr;
}
.logo-panel{
    padding:35px 30px;
    border-right:1px solid var(--border);
    background:var(--soft);
}
.logo-panel h4{
    font-size:16px;
    font-weight:700;
    color:var(--text);
    margin:0 0 7px;
}
.logo-panel .hint{
    color:var(--muted);
    font-size:12px;
    line-height:1.5;
    margin-bottom:22px;
}
.logo-upload{
    position:relative;
    width:190px;
    height:190px;
    margin:0 auto 18px;
    background:#fff;
    border:2px dashed #cbd5e1;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    cursor:pointer;
    transition:.2s ease;
}
.logo-upload:hover{
    border-color:var(--primary);
    background:#f8fbff;
}
.logo-upload img{
    width:100%;
    height:100%;
    object-fit:contain;
    padding:10px;
}
.logo-overlay{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.68);
    color:#fff;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    opacity:0;
    transition:.2s ease;
    text-align:center;
    font-size:12px;
}
.logo-upload:hover .logo-overlay{opacity:1}
.logo-overlay i{font-size:24px;margin-bottom:7px}
#profile{display:none}
.change-logo{
    width:100%;
    background:#fff;
    border:1px solid #d7dce3;
    color:#374151;
    padding:9px 12px;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
}
.change-logo:hover{
    border-color:var(--primary);
    color:var(--primary);
}
.logo-note{
    margin-top:12px;
    font-size:11px;
    line-height:1.5;
    color:#9ca3af;
    text-align:center;
}
.form-panel{padding:35px}
.section-heading{
    display:flex;
    align-items:center;
    gap:10px;
    padding-bottom:12px;
    margin-bottom:20px;
    border-bottom:1px solid #edf0f3;
}
.section-icon{
    width:34px;
    height:34px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#eff6ff;
    color:var(--primary);
}
.section-heading h3{
    margin:0;
    color:var(--text);
    font-size:16px;
    font-weight:700;
}
.section-heading small{
    display:block;
    color:var(--muted);
    margin-top:2px;
    font-size:11px;
}
.form-section{margin-bottom:30px}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}
.form-group{margin:0}
.form-group.full{grid-column:1/-1}
.form-group label{
    display:block;
    color:#374151;
    font-size:12px;
    font-weight:700;
    margin-bottom:7px;
}
.required{color:#dc2626}
.input-wrap{position:relative}
.input-icon{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#9ca3af;
    font-size:13px;
    pointer-events:none;
}
.business-setup .form-control{
    width:100%;
    height:44px;
    border:1px solid #d8dde5;
    border-radius:0;
    padding:9px 12px 9px 38px;
    font-size:14px;
    color:var(--text);
    background:#fff;
    box-shadow:none;
    transition:.15s ease;
}
.business-setup .form-control:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(37,99,235,.08);
    outline:none;
}
.business-setup .form-control::placeholder{color:#b0b7c3}
.field-help{
    color:#9ca3af;
    font-size:11px;
    margin-top:5px;
}
.form-actions{
    border-top:1px solid var(--border);
    padding-top:22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
}
.form-note{
    color:var(--muted);
    font-size:11px;
    line-height:1.5;
}
.saveNewBusiness{
    border-radius:0!important;
    min-width:180px;
    min-height:44px;
    border:none;
    background:var(--primary)!important;
    font-weight:700;
    font-size:13px;
    padding:10px 20px;
}
.saveNewBusiness:hover{background:var(--primary-dark)!important}
.saveNewBusiness:disabled{opacity:.7;cursor:not-allowed}
.setup-message{
    display:none;
    margin-bottom:20px;
    padding:12px 15px;
    border-left:3px solid;
    font-size:13px;
}
.setup-message.error{
    display:block;
    color:#991b1b;
    background:#fef2f2;
    border-color:#dc2626;
}
.setup-message.success{
    display:block;
    color:#166534;
    background:#f0fdf4;
    border-color:#16a34a;
}
.spinner{
    display:none;
    width:15px;
    height:15px;
    margin-right:7px;
    vertical-align:-2px;
    border:2px solid rgba(255,255,255,.4);
    border-top-color:#fff;
    border-radius:50%;
    animation:spin .7s linear infinite;
}
.is-loading .spinner{display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:767.98px){
    .business-setup{padding:18px 10px 35px}
    .setup-header{margin-bottom:18px}
    .setup-header h1{font-size:23px}
    .setup-header p{font-size:13px}
    .setup-progress{margin-top:18px}
    .progress-line{width:25px;margin:0 7px}
    .progress-item span:last-child{display:none}
    .progress-number{margin:0}
    .setup-grid{grid-template-columns:1fr}
    .logo-panel{
        padding:25px 20px;
        border-right:none;
        border-bottom:1px solid var(--border);
        text-align:center;
    }
    .logo-upload{width:140px;height:140px}
    .change-logo{max-width:220px}
    .form-panel{padding:25px 18px}
    .form-grid{grid-template-columns:1fr;gap:15px}
    .form-group.full{grid-column:auto}
    .form-actions{
        flex-direction:column-reverse;
        align-items:stretch;
    }
    .form-note{text-align:center}
    .saveNewBusiness{width:100%}
}
</style>

<div class="business-setup">
    <div class="setup-container">

        <div class="setup-header">

            <div class="setup-brand">
                <img src="../assets/images/kashhub/logo.png" alt="KashHub" onerror="this.style.display='none'">
                <span>KashHub Business Manager</span>
            </div>

            <h1>Set up your business</h1>

            <p>
                Tell us a little about your business. These details will help
                personalise your KashHub workspace, reports and business documents.
            </p>

            <div class="setup-progress">

                <div class="progress-item active">
                    <div class="progress-number">1</div>
                    <span>Business Profile</span>
                </div>

                <div class="progress-line"></div>

                <div class="progress-item">
                    <div class="progress-number">2</div>
                    <span>Create Cashbook</span>
                </div>

                <div class="progress-line"></div>

                <div class="progress-item">
                    <div class="progress-number">3</div>
                    <span>Start Managing</span>
                </div>

            </div>

        </div>

        <div class="setup-card">

            <form id="newBusinessForm" method="post" enctype="multipart/form-data">

                <input type="hidden" name="action" value="businessProfile">

                <div class="setup-grid">

                    <!-- =====================================================
                         BUSINESS LOGO
                    ====================================================== -->

                    <div class="logo-panel">

                        <h4>Business Logo</h4>

                        <div class="hint">
                            Add your business logo. It can later appear on
                            your KashHub workspace and business documents.
                        </div>

                        <label for="profile" class="logo-upload">

                            <img
                                src="../images/profile.jpg"
                                alt="Business logo"
                                id="profilePreview"
                            >

                            <div class="logo-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Choose business logo</span>
                            </div>

                        </label>

                        <input
                            type="file"
                            name="profile"
                            id="profile"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <button
                            type="button"
                            class="change-logo"
                            id="chooseLogo"
                        >
                            <i class="fas fa-upload mr-1"></i>
                            Choose Logo
                        </button>

                        <div class="logo-note">
                            JPG, PNG or WEBP recommended.<br>
                            A square or transparent logo works best.
                        </div>

                    </div>

                    <!-- =====================================================
                         FORM
                    ====================================================== -->

                    <div class="form-panel">

                        <div id="formMessage" class="setup-message"></div>

                        <!-- BASIC INFORMATION -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-icon">
                                    <i class="fas fa-building"></i>
                                </div>

                                <div>
                                    <h3>Business Information</h3>
                                    <small>Basic details about your business</small>
                                </div>

                            </div>

                            <div class="form-grid">

                                <div class="form-group full">

                                    <label for="name">
                                        Business Name
                                        <span class="required">*</span>
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-store input-icon"></i>

                                        <input
                                            type="text"
                                            name="name"
                                            id="name"
                                            class="form-control"
                                            placeholder="Enter business name"
                                            required
                                            autocomplete="organization"
                                        >

                                    </div>

                                </div>

                                <div class="form-group">

                                    <label for="email">
                                        Business Email
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-envelope input-icon"></i>

                                        <input
                                            type="email"
                                            name="email"
                                            id="email"
                                            class="form-control"
                                            placeholder="business@example.com"
                                            autocomplete="email"
                                        >

                                    </div>

                                </div>

                                <div class="form-group">

                                    <label for="reg_no">
                                        Registration Number
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-file-alt input-icon"></i>

                                        <input
                                            type="text"
                                            name="reg_no"
                                            id="reg_no"
                                            class="form-control"
                                            placeholder="Optional"
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- CONTACT INFORMATION -->

                        <div class="form-section">

                            <div class="section-heading">

                                <div class="section-icon">
                                    <i class="fas fa-address-card"></i>
                                </div>

                                <div>
                                    <h3>Contact Information</h3>
                                    <small>How customers and documents identify your business</small>
                                </div>

                            </div>

                            <div class="form-grid">

                                <div class="form-group full">

                                    <label for="address">
                                        Business Address
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-map-marker-alt input-icon"></i>

                                        <input
                                            type="text"
                                            name="address"
                                            id="address"
                                            class="form-control"
                                            placeholder="e.g. Namulanda, Entebbe Road"
                                            autocomplete="street-address"
                                        >

                                    </div>

                                </div>

                                <div class="form-group">

                                    <label for="contact1">
                                        Primary Contact
                                        <span class="required">*</span>
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-phone-alt input-icon"></i>

                                        <input
                                            type="tel"
                                            name="contact1"
                                            id="contact1"
                                            class="form-control"
                                            placeholder="e.g. 0700 000 000"
                                            required
                                            autocomplete="tel"
                                        >

                                    </div>

                                    <div class="field-help">
                                        Main business telephone number
                                    </div>

                                </div>

                                <div class="form-group">

                                    <label for="contact2">
                                        Alternative Contact
                                    </label>

                                    <div class="input-wrap">

                                        <i class="fas fa-phone input-icon"></i>

                                        <input
                                            type="tel"
                                            name="contact2"
                                            id="contact2"
                                            class="form-control"
                                            placeholder="Optional"
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- ACTIONS -->

                        <div class="form-actions">

                            <div class="form-note">
                                <i class="fas fa-lock mr-1"></i>
                                Your business information can be updated later from Business Profile.
                            </div>

                            <button
                                type="submit"
                                class="btn btn-flat btn-primary saveNewBusiness"
                                id="saveBusinessBtn"
                            >
                                <span class="spinner"></span>

                                <span class="buttonText">
                                    Save & Continue
                                </span>

                                <i class="fas fa-arrow-right ml-2 buttonArrow"></i>
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>
</div>

<script src="../assets/js/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>
<script src="../assets/fontawesome-free-5.14.0-web/js/all.min.js"></script>
<script src="../assets/js/xdialog.3.4.0.min.js"></script>
<script src="../assets/js/custom.js"></script>

<script>
document.addEventListener("DOMContentLoaded",function(){

    const form=document.getElementById("newBusinessForm");
    const profile=document.getElementById("profile");
    const preview=document.getElementById("profilePreview");
    const chooseLogo=document.getElementById("chooseLogo");
    const btn=document.getElementById("saveBusinessBtn");
    const buttonText=btn.querySelector(".buttonText");
    const buttonArrow=btn.querySelector(".buttonArrow");
    const message=document.getElementById("formMessage");

    /*
    |--------------------------------------------------------------------------
    | CHOOSE LOGO
    |--------------------------------------------------------------------------
    */

    chooseLogo.addEventListener("click",function(){
        profile.click();
    });

    /*
    |--------------------------------------------------------------------------
    | LOGO PREVIEW
    |--------------------------------------------------------------------------
    */

    profile.addEventListener("change",function(){

        const file=this.files[0];

        if(!file)return;

        const allowed=[
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if(!allowed.includes(file.type)){

            showMessage(
                "Please select a JPG, PNG or WEBP image.",
                "error"
            );

            this.value="";
            return;
        }

        /*
        | Maximum 5MB
        */

        if(file.size>5*1024*1024){

            showMessage(
                "The selected image is too large. Please choose an image below 5MB.",
                "error"
            );

            this.value="";
            return;
        }

        const reader=new FileReader();

        reader.onload=function(e){
            preview.src=e.target.result;
        };

        reader.readAsDataURL(file);

        clearMessage();
    });

    /*
    |--------------------------------------------------------------------------
    | SUBMIT
    |--------------------------------------------------------------------------
    */

    form.addEventListener("submit",async function(e){

        e.preventDefault();

        clearMessage();

        /*
        |--------------------------------------------------------------------------
        | HTML VALIDATION
        |--------------------------------------------------------------------------
        */

        if(!form.checkValidity()){

            form.reportValidity();
            return;

        }

        const name=document.getElementById("name").value.trim();
        const contact1=document.getElementById("contact1").value.trim();

        if(name===""){

            showMessage(
                "Please enter your business name.",
                "error"
            );

            document.getElementById("name").focus();
            return;
        }

        if(contact1===""){

            showMessage(
                "Please enter your primary business contact.",
                "error"
            );

            document.getElementById("contact1").focus();
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        btn.disabled=true;
        btn.classList.add("is-loading");

        buttonText.textContent="Saving Business...";
        buttonArrow.style.display="none";

        const formData=new FormData(form);

        try{

            const response=await fetch(
                "save/index.php",
                {
                    method:"POST",
                    body:formData,
                    headers:{
                        "X-Requested-With":"XMLHttpRequest"
                    }
                }
            );

            const raw=await response.text();

            /*
            |--------------------------------------------------------------------------
            | HTTP ERROR
            |--------------------------------------------------------------------------
            */

            if(!response.ok){

                console.error(raw);

                throw new Error(
                    "Server returned HTTP "+response.status
                );
            }

            /*
            |--------------------------------------------------------------------------
            | TRY JSON RESPONSE
            |--------------------------------------------------------------------------
            */

            let data=null;

            try{
                data=JSON.parse(raw);
            }catch(e){
                data=null;
            }

            /*
            |--------------------------------------------------------------------------
            | JSON ERROR RESPONSE
            |--------------------------------------------------------------------------
            */

            if(
                data &&
                (
                    data.status==="error" ||
                    data.success===false ||
                    data.error
                )
            ){

                const errorMessage=
                    data.message ||
                    data.error ||
                    "Unable to save business profile.";

                throw new Error(errorMessage);
            }

            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            |
            | Your old endpoint appears to return text, so a successful HTTP
            | response is still accepted when JSON is not returned.
            |
            */

            showMessage(
                "Business profile saved successfully. Preparing your workspace...",
                "success"
            );

            buttonText.textContent="Business Created";

            if(typeof xdialog!=="undefined"){

                xdialog.alert(
                    "Business profile saved successfully.",
                    function(){
                        window.location.href="../";
                    }
                );

                /*
                | Fallback redirect in case the dialog library does not invoke
                | the callback in the version being used.
                */

                setTimeout(function(){
                    window.location.href="../";
                },1200);

            }else{

                setTimeout(function(){
                    window.location.href="../";
                },700);

            }

        }catch(error){

            console.error(error);

            showMessage(
                error.message ||
                "We could not save the business profile. Please try again.",
                "error"
            );

            btn.disabled=false;
            btn.classList.remove("is-loading");

            buttonText.textContent="Save & Continue";
            buttonArrow.style.display="";

        }

    });

    /*
    |--------------------------------------------------------------------------
    | MESSAGE FUNCTIONS
    |--------------------------------------------------------------------------
    */

    function showMessage(text,type){

        message.className="setup-message "+type;
        message.textContent=text;

        message.scrollIntoView({
            behavior:"smooth",
            block:"nearest"
        });

    }

    function clearMessage(){

        message.className="setup-message";
        message.textContent="";

    }

});
</script>

</body>
</html>

<?php
    }
}
else
{
    redirect('../');
}
?>