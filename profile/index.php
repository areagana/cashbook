<?php
require_once(__dir__.'/../assets/functions.php');
if(isVerified())
{
    // check if they have a business profile
    checkBusinessProfile();
    if(isset($_SESSION['hasbusiness']))
    {
        redirect('../');
    }
    pageHeader('Home');
?>
    <div class="container">
        <div class="row mx-1">
            <div class="col p-2">
                <h2 class="p-2">BUSINESS PROFILE</h2>
            </div>
        </div>
        <div class="row mx-1">
            <div class="col p-2">
                <div class="border rounded-3 p-3">
                    <form id='newBusinessForm' method="post" enctype='multipart/form-data'>
                        <div class="row mx-1">
                            <div class="col-md-3 p-3">
                                <label for="profile">
                                    <img src="../images/profile.jpg" alt="" id='profilePreview' srcset="" width='90%' class='border rounded-3' height="100%">
                                </label>
                                <input type="file" name="profile" id="profile" opacity='0%' class='hidden'>
                                <input type="hidden" name="action" value="businessProfile">
                            </div>
                            <div class="col p-2">
                                <div class="form-row">
                                    <div class="col-md-4 p-2">
                                        <label for="name">BUSINESS NAME:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="name" id="name" class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-4 p-2">
                                        <label for="email">EMAIL:</label>
                                    </div>
                                    <div class="col p-2">
                                        <input type="text" name="email" id="email" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-row">
                            <div class="col-md-3 p-2">
                                <label for="address">ADDRESS:</label>
                            </div>
                            <div class="col p-2">
                                <input type="text" name="address" id="address" class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-3 p-2">
                                <label for="contact1">CONTACT1:</label>
                            </div>
                            <div class="col p-2">
                                <input type="text" name="contact1" id="contact1" class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-3 p-2">
                                <label for="contact2">CONTACT2:</label>
                            </div>
                            <div class="col p-2">
                                <input type="text" name="contact2" id="contact2" class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-3 p-2">
                                <label for="reg_no">REGN NO:</label>
                            </div>
                            <div class="col p-2">
                                <input type="text" name="reg_no" id="reg_no" class="form-control">
                            </div>
                        </div>
                        <hr>
                        <div class="form-row">
                            <div class="col p-2">
                                <button type="submit" class="btn btn-flat btn-primary right saveNewBusiness">SUBMIT</button>
                            </div>
                        </div>
                    </form>
                </div> 
            </div>
        </div>
    <!-- business profile -->
          
    <script src="../assets/js/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>

    <script src="../assets/fontawesome-free-5.14.0-web/js/all.min.js"></script>
    <script src="../assets/js/xdialog.3.4.0.min.js"></script>
    <script src="../assets/js/custom.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("newBusinessForm");
            if (!form) return;
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const formData = new FormData(form);
                console.log(formData.keys());
                fetch("save/index.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(response => {
                    xdialog.alert("Business profile saved");
                    wondow.location.href='../';
                })
                .catch(err => console.error(err));
            });

        });
    </script>
</body>
</html>

<?php
}else{
    redirect('../');
}
?>