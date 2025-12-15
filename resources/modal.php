					<div class='modal fade' id='form'>
						<div class='modal-dialog'>
							<div class='modal-content'>
								<div class='modal-header'>
									<h3 class='modal-title'>TITLE</h3>
									<button type='button' class='close' data-dismiss='modal'>&times;</button>
								</div>
								<div class='modal-body'>


								</div>
								<div class='modal-footer'>
									<button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Close</button>
									<button type='submit' name='reg_course' class='btn btn-primary btn-sm' style='float:left' form ='form'>Save</button>

								</div>
							</div>
						</div>
					</div>

						<div class='modal fade' id='reg_new_member'>
						<div class='modal-dialog modal-lg'>
							<div class='modal-content'>
								<div class='modal-header'>
									<h3 class='modal-title'>REGISTER NEW MEMBER</h3>
									<button type='button' class='close' data-dismiss='modal'>&times;</button>
								</div>
								<div class='modal-body'>
									<form method = 'POST' id='reg-new-members'>
										<div class='form-group'>
											<label>USER ID:</label>
											<input type ='text' name ='user_id'  placeholder ='user_id' class='form-control' readonly>

											<label>NAME:</label>
											<input type ='text' name ='mname'  placeholder ='enter name' class='form-control'required>

											<label>GENDER</label>
												<select name='gender' class='form-control'><option>Select</option>
														<option value='Male'>Male</option>
														<option value='Female'>Female</option>
												</select>

											<label>ADDRESS:</label>
											<input type ='text' name ='address'  placeholder ='address' class='form-control'>
										</div>
										<div class='form-group'>
											<label>CONTACT:</label>
											<input type ='INT' name ='contact'  placeholder ='contact' class='form-control'required>

											<label>NEXT OF KIN</label>
											<input type ='text' name ='nextofkin'  placeholder ='next of kin' class='form-control'>

											<label>RELATIONSHIP:</label>
											<input type ='text' name ='relationship' placeholder ='relationship' class='form-control'>
										</div>
										<div class='form-group'>
											<label>NEXT OF KIN ADDRESS:</label>
											<input type ='text' name ='nokaddress' class='form-control' placeholder ='next of kin address' width='30px' height='20px'>

											<label>NEXT OF KIN CONTACT </label>
											<input type ='INT' name ='nokcontact' class='form-control' placeholder ='next of kin contact'>

											<label>STATUS:</label>
											<select name='status' class='form-control'><option value=''>Select</option>
											<?php // select status from the availabe ones
												$query = "SELECT DISTINCT status FROM biodata";
												$one = mysqli_query($conn,$query);

												while($row = mysqli_fetch_array($one))
												{
											?>
											<option name ='status' value ='<?php $row['status'];?>' select='selected'><?php  echo $row['status'];?></option>

											<?php
												}
											?>

											</select>
										</div>
										<div class='form-group'>
											<label>YEAR JOINED:</label>
											<input type ='text' name ='year1' class='form-control' autocomplete='off'>
										</div>
									</form>
								</div>
								<div class='modal-footer'>
									<button type='button' class='btn btn-danger' data-dismiss='modal'>Close</button>
									<button type='submit' name='submit' class='btn btn-primary' style='float:left' form ='reg-new-members'>Save</button>
								</div>
							</div>
						</div>
					</div>

					<!-- edit personal data form-->
					<div class='modal fade' id='edit_user_data'>
						<div class='modal-dialog modal-lg'>
							<div class='modal-content'>
								<div class='modal-header'>
									<h3 class='modal-title'><?php echo $name_a['Name'];?></h3>
									<button type='button' class='close' data-dismiss='modal'>&times;</button>
								</div>
								<div class='modal-body'>
									<form method ='POST' id='update-user-data'>
										<table class='table'>
											<tr>
												<td><strong>USER ID:</strong></td>
												<td><input name ='userid' value ='<?php echo $name_a['userid'];?>' class='form-control' readonly></td>
											</tr>
											<tr>
												<td><strong>NAME:</strong></td>
												<td><input name ='NAME' value ='<?php echo $name_a['Name'];?>'class='form-control'></td>
											</tr>
											<tr>
												<td><strong>GENDER:</strong></td>
												<td>
													<select name='gender' class='form-control'><option value='<?php echo $name_a['gender'];?>'><?php echo $name_a['gender'];?></option>
														<option value='Male'>Male</option>
														<option value='Female'>Female</option>
													</select>
												</td>
											</tr>
											<tr>
												<td><strong>ADDRESS:</strong></td>
												<td><textarea name ='ADDRESS' value ='<?php echo $name_a['address'];?>'class='form-control'><?php echo $name_a['address'];?></textarea></td>
											</tr>
											<tr>
												<td><strong>CONTACT:</strong></td>
												<td><input name ='CONTACT' value ='<?php echo $name_a['contact'];?>'class='form-control'></td>
											</tr>
											<tr>
												<td><strong>NEXT OF KIN:</strong></td>
												<td><input name ='NEXT_OF_KIN' value ='<?php echo $name_a['nok'];?>'class='form-control'></td>
											</tr>
											<tr>
												<td><strong>NOK RELATIONSHIP:</strong></td>
												<td><input name ='Relationship' value ='<?php echo $name_a['relate'];?>'class='form-control'></td>
											</tr>
											<tr>
												<td><strong>NOK CONTACT:</strong></td>
												<td><input name ='NOK_CONTACT' value ='<?php echo $name_a['nok_contact'];?>'class='form-control'></td>
											</tr>
											<tr>
												<td><strong>STATUS:</strong></td>
												<td><input name ='status' value ='<?php echo $name_a['status'];?>'class='form-control'></td>
											</tr>

										</table>
									</form>
								</div>
								<div class='modal-footer'>
									<button type='button' class='btn btn-danger' data-dismiss='modal'>Close</button>
									<button type='submit' name='save2' class='btn btn-primary' style='float:left' form ='update-user-data'>Save</button>
								</div>
							</div>
						</div>
					</div>

					<script>
						$(document).ready(function(){
							$("#searchtodelete").on("keyup", function() {
							var value = $(this).val().toLowerCase();
							$("#class_del li").filter(function() {
								$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
							});
							});
						});

						$('#tickalldel').click(function () {
							 $('input:checkbox').prop('checked', this.checked);
						});
					</script>
					<script>
						$(document).ready(function(){
								$("#searchtodelete").on("keyup", function() {
								var value = $(this).val().toLowerCase();
								$("#class_del li").filter(function() {
									$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
								});
							});
						});
					</script>
