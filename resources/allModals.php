<?php
require_once(__DIR__.'/../includes/server.php');
    // update subject registers tale
    $query = "ALTER TABLE subject_register ADD COLUMN IF NOT EXISTS subject_code varchar(100) null AFTER subject_name, 
        ADD COLUMN IF NOT EXISTS subject_section varchar(255) null AFTER subject_code, 
        ADD column IF NOT EXISTS subject_level varchar(255) null after subject_section,
        ADD COLUMN IF NOT EXISTS papers varchar(255) null after subject_level,
        ADD COLUMN IF NOT EXISTS paper_names varchar(255) null after papers";
    mysqli_query($server,$query);
?>

<!-- edit student exam data modal -->
<div class="modal fade" id="edit-student-data-modal"  role="dialog" data-backdrop='static'>
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title name_header" id="name_header"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <?php 
            $school_classes = $khs->select('class_register');
            $exam_names = $khs->select_distinct('exam_data','exam_name');
          ?>
        <div class="input-group mb-3">
            <input type="hidden" name="student_admin_no" id='student_admin_no'>
                <select name="exam_class" id="exam_class" class="form-control">
                </select>
                <select name="exam_stream" id="exam_stream" class="form-control">
                </select>
            <select name="exam_name" id="exam_name_selected" class="form-control" onchange="displayExamResults()">
                <option value="">Select</option>
                <?php foreach($exam_names as $exm):?>
                    <option value="<?php echo $exm['exam_name']; ?>"><?php echo $exm['exam_name']; ?></option>
                <?php endforeach;  ?>
            </select>
        </div>
      </div>
      <div class="p-2 border" id='myExamResults_displayed'>
        <!-- exams done wne their marks will be here -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- edit subject modal -->
<div class='modal fade' id='subject_edit' data-backdrop='static'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h3 class='modal-title subject_title'></h3>
                <button type='button' class='close' data-dismiss='modal'>&times;</button>
            </div>
            <div class='modal-body'>
                <form action="" method='POST' id='update_subject_form'>
                    <div class="form-group">
                    <input type="hidden" name="id" id ='subject_id'>
                        <label for="subject_name_edit">Subject Name</label>
                        <input type="text" name="subject_name" id="subject_name_edit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_code">Subject Code</label>
                        <input type="text" name="subject_code" id="subject_code_edit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_short_edit">Short Short</label>
                        <input type="text" name="subject_short" id="subject_short_edit" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="subject_level">Level</label>
                        <select name="subject_level" id="subject_level_edit" class="form-control level_select" onchange="checkLevel('subject_level_edit')"></select>
                    </div>
                    
                    <div class="form-row section hidden">
                        <div class="col p-0">
                            <h6>Section</h6>
                        </div>
                    </div>
                    <div class="form-row bg-light section hidden">
                        <div class="col p-2">
                            <input type="checkbox" name="subject_section[]" value='ARTS' id="Arts_edit">
                            <label for="Arts_edit" class='mx-2'>Arts</label>
                        </div>
                        <div class="col p-2">
                            <input type="checkbox" name="subject_section[]" value='SCIENCES' id="sciences_edit">
                            <label for="sciences_edit" class='mx-2'>Sciences</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject_papers">Papers (number)</label>
                        <input type="number" name="subject_papers" id="subject_papers_edit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_paper_names_edit">Paper Names</label>
                        <span id="subject_paper_names_edit"></span>
                    </div>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-light' data-dismiss='modal'>Close</button>
                <button type='submit' name='main_subject_update' class='btn btn-primary' style='float:left' form ='update_subject_form'>Update</button>
            </div>
        </div>
    </div>
</div>

<!-- create new subject modal -->
<div class='modal fade' id='create_new_subject' data-backdrop='static'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h3 class='modal-title subject_title'>New subject</h3>
                <button type='button' class='close' data-dismiss='modal'>&times;</button>
            </div>
            <div class='modal-body'>
                <form action="" method='POST' id='new_subject_form'>
                    <div class="form-group">
                        <label for="subject_name">Subject Name</label>
                        <input type="text" name="subject_name" id="subject_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_code">Subject Code</label>
                        <input type="text" name="subject_code" id="subject_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_short">Short Name</label>
                        <input type="text" name="subject_short" id="subject_short" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="subject_level">Level</label>
                        <select name="subject_level" id="subject_level_add" class="form-control" onchange="checkLevel('subject_level_add')">
                            <option value="OLEVEL">OLEVEL</option>
                            <option value="ALEVEL">ALEVEL</option>
                        </select>
                    </div>
                    <div class="form-row section hidden">
                        <div class="col p-0">
                            <h6>Section</h6>
                        </div>
                    </div>
                    <div class="form-group form-row bg-light section hidden">
                        <div class="col p-2">
                            <input type="checkbox" name="subject_section[]" value='ARTS' id="Arts">
                            <label for="Arts" class='mx-2'>Arts</label>
                        </div>
                        <div class="col p-2">
                            <input type="checkbox" name="subject_section[]" value='SCIENCES' id="sciences">
                            <label for="sciences" class='mx-2'>Sciences</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subject_papers">Papers</label>
                        <input type="text" name="subject_papers" id="subject_papers_add" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="subject_paper_names">Paper Names</label>
                        <span id="subject_paper_names_add"></span>
                    </div>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Close</button>
                <button type='submit' name='new_subject' class='btn btn-primary btn-sm' style='float:left' form ='new_subject_form'>Save</button>
            </div>
        </div>
    </div>
</div>
<!-- delete subject -->
<div class='modal fade' id='delete_message'>
    <div class='modal-dialog modal-success'>
        <div class='modal-content'>
        <div class='modal-body'>
            <h5> Your subject has been deleted successfully</h5>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Dismiss</button>
        </div>
        </div>
    </div>
</div>

<!-- grading scale modals -->
<div class='modal fade' id='delete_scale' data-backdrop='static'>
    <div class='modal-dialog modal-warning modal-dialog-centered'>
        <div class='modal-content'>
        <div class='modal-body'>
            <h5> Do you confirm to delete this scale?</h5>
            <i>(Remember,all data attached to this scale will nolonger be valid!)</i>
            <form action="save/index.php" method='POST' id='scale_delete_form'>
                <input type="hidden" name="scale_id" id="scale_id_delete">
            </form>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-light btn-sm' data-bs-dismiss='modal'>Cancel</button>
            <button type="submit" class='btn btn-primary btn-sm' name='delete_scale' form='scale_delete_form'>Confirm Delete</button>
        </div>
        </div>
    </div>
</div>

<div class='modal fade' id='grading_scale_edit' data-backdrop='static'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h3 class='modal-title scale_range'></h3>
                <button type='button' class='close' data-bs-dismiss='modal'>&times;</button>
            </div>
            <div class='modal-body'>
                <form action="save/index.php" method='POST' id='grading_scale_form'>
                    <input type="hidden" name="scale_id" id='scale_id'>
                    <div class="form-group">
                        <label for="min_value_edit">Min Value
                            <input type="number" name="min_value" id="min_value_edit" class="form-control">
                        </label>
                        <label for="max_value_edit">Max Value
                            <input type="number" name="max_value" id="max_value_edit" class="form-control">
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="grade_value">Grade Value</label>
                        <input type="text" name="grade_value" id="grade_value" class="form-control">
                    </div>
                    <div class="form-group">
                        <h6 class='border-bottom p-2'>Grade Comments
                            <span class="right">
                                <button type='button' class="btn btn-light btn-sm right" onclick='addComment()'><i class="fa fa-plus-circle"></i> Add Comment</button>
                            </span>
                        </h6>
                        <div class="p-2" id='grade_comments'>
                            <i>No comments</i>
                        </div>
                    </div>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-light btn-sm' data-bs-dismiss='modal'>Close</button>
                <button type='submit' name='grading_scale_update' class='btn btn-primary btn-sm' style='float:left' form ='grading_scale_form'>Save</button>
            </div>
        </div>
    </div>
</div>

<!-- session success modal -->
<div class='modal fade' id='session_success'>
    <div class='modal-dialog'>
        <div class='modal-content'>
        <div class="modal-header bg-success">
            <h4>Message</h4>
            <button type='button' class='close' data-dismiss='modal'>&times;</button>
        </div>
        <div class='modal-body'>
            <h5 class='session_message'></h5>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Dismiss</button>
        </div>
        </div>
    </div>
</div>

<!-- upload mEKS modal -->
<div class='modal fade' id='upload_marks' data-backdrop='static'>
    <div class='modal-dialog'>
        <div class='modal-content'>
        <div class="modal-header bg-success">
            <h4>Upload Marks</h4>
            <button type='button' class='close' data-dismiss='modal'>&times;</button>
        </div>
        <div class='modal-body'>
            <form action="../mark update/bulk_uploadMarksheet.php" method='POST' enctype='multipart/form-data' id="exam_upload_marks_form" onsubmit ='xdialog.startSpin()'>
                <div class="form-group">
                    <input type="hidden" name="myTerm" value='<?php echo $term_name; ?>'>
                    <label for="mark_upload_class" class="form-label">Class</label>
                    <select name="myClass" id="mark_upload_class_select" class="form-control" onchange="loadMySubjects($(this).val())" required>
                        <option value="">Select</option>
                        <?php foreach($school_classes as $key=> $value): if($value['class'] !=""): ?>
                            <option value="<?php echo $value['class']; ?>"><?php echo $value['class']; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="" class="form-label">Stream</label>
                    <select name="myStream" id="" class="form-control" required>
                        <option value="">Select</option>
                        <option value="General">General</option>
                        <?php foreach($school_classes as $key=> $value): if($value['stream'] !=""): ?>
                            <option value="<?php echo $value['stream']; ?>"><?php echo $value['stream']; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="" class="form-label">Subject
                        <select name="mySubject" id="mark_upload_subject" class="form-control" onchange='loadMySubjectPapers()'>
                            <option value="">Select</option>
                            <?php foreach($school_subjects as $key=> $value): if($value['subject_name'] !=""): ?>
                                <option value="<?php echo $value['subject_name']; ?>"><?php echo $value['subject_name']; ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </label>
                    <label for="" class="form-label">Paper
                        <select name="myPaper" id="mark_upload_paper" class="form-control">
                            <option value="">Select</option>
                            <option value="">General</option>
                            <?php for($i=1;$i<7;$i++):?>
                                <option value="PAPER <?php echo $i; ?>">PAPER <?php echo $i;?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <label for="" class="form-label">Exam name</label>
                    <select name="myExam" id="" class="form-control" required>
                        <option value="">Select</option>
                        <?php foreach($exam_names as $key=> $value): if($value['exam_name'] !=""): ?>
                            <option value="<?php echo $value['exam_name']; ?>"><?php echo $value['exam_name']; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file_select">Select CSV file</label>
                    <input type="file" name="file" id="file_select" class="form-control"required>
                </div>
            </form>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Dismiss</button>
            <button type='submit' class='btn btn-primary btn-sm' name ='exam_upload_marks' form='exam_upload_marks_form' >Upload Marks</button>
        </div>
        </div>
    </div>
</div>

<!-- session success modal -->
<div class='modal fade' id='template_marks' data-backdrop='static'>
    <div class='modal-dialog'>
        <div class='modal-content'>
        <div class="modal-header bg-success">
            <h4>Class template</h4>
            <button type='button' class='close' data-dismiss='modal'>&times;</button>
        </div>
        <?php

        ?>  
        <div class='modal-body'>
            <form action="exam_register/marksheetEmpty.php" method='POST' id="exam_class_template" onsubmit ='xdialog.startSpin()'>
                <div class="form-group">
                    <input type="hidden" name="myTerm" value='<?php echo $term_name; ?>'>
                    <label for="" class="form-label">Class</label>
                    <select name="myClass" id="" class="form-control" required>
                        <option value="">Select</option>
                        <?php foreach($school_classes as $key=> $value): if($value['class'] !=""): ?>
                            <option value="<?php echo $value['class']; ?>"><?php echo $value['class']; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="" class="form-label" required>Stream</label>
                    <select name="myStream" id="" class="form-control">
                        <option value="">Select</option>
                        <?php foreach($school_classes as $key=> $value): if($value['stream'] !=""): ?>
                            <option value="<?php echo $value['stream']; ?>"><?php echo $value['stream']; ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class='modal-footer'>
            <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Dismiss</button>
            <button type='submit' class='btn btn-primary btn-sm' form='exam_class_template' >Download</button>
        </div>
        </div>
    </div>
</div>
<!-- add class teachers model -->
<div class='modal fade' id='add_class_teachers' data-backdrop='startic'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-info'>
                    <h3 class='modal-title'>Add class teachers</h3>
                    <button type='button' class='close' data-dismiss='modal'>&times;</button>
                </div>
                <div class='modal-body'>
                  <form action="" method='POST' ID='add_class_tr'>
                    <div class="form-row">
                      <div class="col-md-3 p-2">
                        <label for="class_name" class="form-label">Class</label>
                      </div>
                      <div class="col p-2">
                        <select name="class_name" id="class_name" class="form-control" required>
                          <option value="">Select Class</option>
                          <?php foreach($school_classes as $key=> $class):
                              if($class['class'] !=''):
                            ?>
                              <option value="<?php echo $class['class'];?>"><?php echo $class['class'];?></option>
                          <?php
                              endif;
                            endforeach;
                          ?>
                        </select>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="col-md-3 p-2">
                        <label for="stream_name" class="form-label">Stream</label>
                      </div>
                      <div class="col p-2">
                        <select name="stream_name" id="stream_name" class="form-control" required>
                          <option value="">Select stream</option>
                          <?php foreach($school_classes as $key=> $stream):
                              if($stream['stream'] !=''):
                            ?>
                              <option value="<?php echo $stream['stream'];?>"><?php echo $stream['stream'];?></option>
                          <?php
                              endif;
                            endforeach;
                          ?>
                        </select>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="col-md-3 p-2">
                        <label for="teacher_name" class="form-label">Tr's Name</label>
                      </div>
                      <div class="col p-2">
                        <input type="text" name="teacher_name" id="teacher_name" class="form-control" required>
                      </div>
                    </div>
                  </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-light btn-sm' data-dismiss='modal'>Close</button>
                    <button type='submit' name='add_class_tr' class='btn btn-primary btn-sm' style='float:left' form ='add_class_tr'>Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- upload students and teachers -->
    <div class="modal fade" id="upload_content_m" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info">
            <h5 class="modal-title">Upload Data</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <!--upload content to the database-->
            <form action="../dashboard/upload/index.php" id='data_upload_formS' method='POST' enctype='multipart/form-data'>
                <div class="form-group m-2">
                  <input type="file" class="form-control"  name='file' id="file">
                </div>
                <div class="form-group m-2">
                  <select name="section" id="section" class="form-control">
                    <option value="">Select</option>
                    <option value="Students">Students</option>
                    <option value="Teachers">Teachers</option>
                  </select>
                </div>
                <div class="form-check m-2">
                  <input type="checkbox" name="update_existing" id="updateExist" value='true' class="form-check-input">
                  <label for="updateExist">Update Existing Data</label>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary"  name='upload_data_selected' form='data_upload_formS' onclick="xdialog.startSpin()"> <i class="fa fa-upload"></i> Upload</button>
          </div>
        </div>
      </div>
    </div>

