
  
  $('.dropdown').click(function(){
    $('.dropdown-menu').toggleClass('show');
  });
 
  $(document).on('show.bs.modal', '.modal',function(){
    $(this).appendTo('body');
  });
  
  // page loader function
  function pageloaderfunction() {
    myVar = setTimeout(showPages, 1000);
  }
  
  // hide the success message automatically
  function hideMe(){
    $('.success-message').hide(500);
  }
  setTimeout(hideMe,4000);

  function showPages() {
   document.getElementById("spinners-div").style.display = "none";
  }

    function openNav(id) {
      var sect = document.getElementById(id);
      // sect.style.display='block';
      
      sect.style.width = '100%';
    }
  
    function closeNav(id) {
      document.getElementById(id).style.width = "10%";
    }
    
    function openNotice(id) {
      document.getElementById(id).style.width = "40%";
    }
  
    function closeNav(id) {
      document.getElementById(id).style.width = "0%";
    }
  
    function closeNotice(id) {
      document.getElementById(id).style.width = "0%";
    }
  
    function closemsg(id) {
      document.getElementById(id).style.display = "none";
    }
    
    function printPage(id){
      var html="<html>";
      html+= document.getElementById(id).innerHTML;
      html+="</html>";
      var printWin = window.open('1','','left=1,top=0,width=0,height=0,toolbar=0,scrollbars=1,status =0');
      printWin.document.write(html);
      printWin.document.close();
      printWin.focus();
      printWin.print();
      printWin.close();
  
    }

      function printMe(divId) 
      {
        const printContents = document.getElementById(divId).innerHTML;

          const iframe = document.createElement('iframe');
          iframe.style.position = 'absolute';
          iframe.style.top = '-10000px';  // Hide offscreen.
          document.body.appendChild(iframe);

          const doc = iframe.contentWindow.document;

          // Manually include essential styles.
          const criticalStyles = `
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; }
              h1 { color: #333; }
              p { line-height: 1.5; }
              /* Add more styles as needed */
              table {
                border-collapse:collapse;
              }
              tr,th,td{
                border:1px solid grey;
                border-collapse:collapse;
              }
              th,td{
                padding:2px 4px !important;
              }
              table{
                width:100% !important;
                border:true;
                margin-left: 0% !important;
                margin-right: 0% !important;
              }
            </style>
          `;

          doc.open();
          doc.write(`
            <html>
              <head>
                <title>Print</title>
                ${criticalStyles}
              </head>
              <body>${printContents}</body>
            </html>
          `);
          doc.close();

          iframe.contentWindow.focus();
          iframe.contentWindow.print();
          
          iframe.onload = () => document.body.removeChild(iframe);
    }


      // loading spinners  
    function myFunction() {
      myVar = setTimeout(showPage, 1000);
    }

    function showPage() {
        document.getElementById("spinners").style.display = "none";
        document.getElementById("myDiv").style.display = "block";
    }
      
  
    // download students and teacher templates to have their data inserted in bulk into the system
    function downloadStudentTemplate()
      {
        $.ajax({
          url:'dashboard/download/student_biodata.php',
          type:'POST',
          data:{
            item:'students'
          },
          beforeSend:function(){
            myFunction();
          },
          success:function(res){
            console.log(res);
          },
          error:function(err){
            alert('Error doanloading template');
          }
        });
      }

      function downloadStaffTemplate()
      {
        $.ajax({
          url:'dashboard/download/student_biodata.php',
          type:'POST',
          data:{
            item:'teachers'
          },
          beforeSend:function(){
            myFunction();
          },
          success:function(res){
            console.log(res);
          },
          error:function(err){
            alert('Error downloading template');
          }
        });
      }

  
      function getCheckboxCount(id,name) {
          var ck= document.querySelectorAll('input[name='+name+']:checked').length;
          var btn = document.getElementById('id');
          if(ck > 0)
          {
              if(btn.style.display =='none')
              {
                  btn.style.display ='block';
              }
          }else{
              btn.style.display ='hidden';
          }
      }
  
    // function deleteClassTeacher(id){
    //   $.ajax({
    //     url:'../class_register/delete_class_tr.php',
    //     data:{
    //       id:id
    //     },
    //     success:function(res){
    //       window.location.reload() = true;
    //     }
    //   });
    // }
  
    // load names for report
    function loadReportNames(examclass,examStream){
      var cls = $('#'+examclass).val();
      var stream = $('#'+examStream).val();
      // send data
      $.ajax({
        url:"../student update/studentNamesAjax.php",
        data:{
          class:cls,
          stream:stream,
          action:'reportNames'
        },
        success:function(res){
          $('#reportNames').html(res);
          $('#reportNames_old').html(res);
        }
      });
    }
    
  
    $('.edit-student-data').on('click',function(e){
        e.preventDefault();
        $('#edit-student-data-modal').modal('show');
        var id = $(this).data('id');
        findRow(id);
    });

    function findRow(id){
      $.ajax({
          type: 'POST',
          url: 'student update/student_data.php',
          data: {id:id},
          dataType: 'json',
          success: function(response){
            $('#student_admin_no').val(response.admission_number);
            $('#name_header').html(response.name);
            $('#exam_class').html("<option value='"+response.class+"'>"+response.class+"</option>");
            $('#exam_stream').html("<option value='"+response.stream+"'>"+response.stream+"</option>");
          },
          error:function(err){
            console.log(err);
          }
        });
    }
  
  
  // delete exam data for the student
        function DeleteData(id){
            xdialog.confirm("Delete this subject data?",function(){
              $("#data"+id).fadeOut();
                  $.ajax({
                      url:'../student update/deleteData.php',
                      data:{
                          id:id
                      },
                      type:'POST',
                      success:function(res){
                          xdialog.info('Exam data has been deleted');
                      }
                  });
            });
        }
  
    $(document).on('click','.subject-edit',function(){
        var id = $(this).data('id');
        $('#subject_edit').modal('show');
        //fetch subject from the database
        findSubject(id);
      });
    
    function findSubject(id)
    {
      $.ajax({
        url:'../subjects/find.php',
        data:{
          id:id
        },
        Type:'json',
        success:function(dat){
          var res = JSON.parse(dat);
          $('.subject_title').html(res.subject_name);
          $('#subject_name_edit').val(res.subject_name);
          $('#subject_papers_edit').val(res.papers);
          $('#subject_code_edit').val(res.subject_code);
          $('#subject_short_edit').val(res.subject_short);
          $('#subject_id').val(id);

          var nam = "";
          var papers = $.extend({},JSON.parse(res.paper_names)); // convert array to object
  
          $.each(papers,function(index,name){
            nam += "<select name='subject_paper_names[]' class='form-control paper_select'><option value='"+name+"'>"+name+"</option></select>";
          });
          $('#subject_paper_names_edit').html(nam);
  
          // add other options to allow editing
          var opt = "";
          for(p=1; p < 7;p++){
            opt += "<option value='PAPER "+p+"'>PAPER "+p+"</option>";
          }
          
          $('.paper_select').append(opt);
          
          // add levels to the level option
              var level = res.subject_level;
            lev = "<option value='"+level+"'>"+level+"</option>";            
            lev += "<option value='OLEVEL'>OLEVEL</option>";
            lev += "<option value='ALEVEL'>ALEVEL</option>";
          $('#subject_level_edit').html(lev);
  
          //check the section for the subject
            var section = JSON.parse(res.subject_section);
            const both = ['ARTS','SCINCES'];
            const multipleExist = both.every(value => {
            return section.includes(value);
          });
  
          // if the array is not empty
          if(section.length > 0){
            if(section.includes('ARTS')){ // checks if an array contains the value
            $('#Arts_edit').attr("checked", true);
            }else if(section.includes('SCIENCES')){
              $('#sciences_edit').attr("checked", true);
            }else if(multipleExist){ // if both 
              $('#Arts_edit').attr("checked", true);
              $('#sciences_edit').attr("checked", true);
            }
          }else{
            $('#Arts_edit').attr("checked", false);
            $('#sciences_edit').attr("checked", false);
          }
          
          //check the level of the subject to display the section
          checkLevel('subject_level_edit');
        }
      });
    }
  
    // enable addition or subtraction to the list
    $('#subject_papers_edit').on('blur',function(){
      var paps = $(this).val();
      if(paps > 1){
        var input = "";
        for(i=0;i<paps;i++){
          input += "<select type='text' name='subject_paper_names[]' class='form-control subject_papers_option'></select>";
        }
        $('#subject_paper_names_edit').html(input);
        // add subject papers to each section
        var opt = "";
        for(p=1; p < 7;p++){
          opt += "<option value='PAPER "+p+"'>PAPER "+p+"</option>";
        }
        $('.subject_papers_option').html(opt);
  
      }else{
          input += "<input type='text' name='subject_paper_names[]' class='form-control subject_papers_option' value='General'>";
  
        $('#subject_paper_names_edit').html(input);
      }
    });
  
    // loop the paper entry texts
    $('#subject_papers_add').on('blur',function(){
      var paps = $(this).val();
      if(paps > 1){
        var input = "";
        for(i=0;i<paps;i++){
          input += "<select type='text' name='subject_paper_names[]' class='form-control subject_papers_option'></select>";
        }
        $('#subject_paper_names_add').html(input);
        // add subject papers to each section
        var opt = "";
        for(p=1; p < 7;p++){
          opt += "<option value='PAPER "+p+"'>PAPER "+p+"</option>";
        }
        $('.subject_papers_option').html(opt);
  
      }else{
          input += "<input type='text' name='subject_paper_names[]' class='form-control subject_papers_option' value='General'>";
  
        $('#subject_paper_names_add').html(input);
      }
    });
    $('#subject_papers_edit').on('blur',function(){
      var paps = $(this).val();
      if(paps > 1){
        var input = "";
        for(i=0;i<paps;i++){
          input += "<select type='text' name='subject_paper_names[]' class='form-control subject_papers_option'></select>";
        }
        $('#subject_paper_names_add').html(input);
        // add subject papers to each section
        var opt = "";
        for(p=1; p < 7;p++){
          opt += "<option value='PAPER "+p+"'>PAPER "+p+"</option>";
        }
        $('.subject_papers_option').html(opt);
  
      }else{
          input += "<input type='text' name='subject_paper_names[]' class='form-control subject_papers_option' value='General'>";
  
        $('#subject_paper_names_edit').html(input);
      }
    });
  
    //function to check level and show the section area
    function checkLevel(id)
    {
      var lev = $('#'+id).val();
      if(lev == 'ALEVEL'){
        $('.section').show();
      }else{
        $('.section').hide();
      }
    }
  
    //add grading scale
   
      // add topic to list
  // function addTopicAdmin(){
  //   var input = "<div class='input-group mb-3'>"+
  //     "<input type='text' name='topic_name[]' class='form-control' placeholder='Topic name..'>"+
  //     "<select name='class[]' class='form-control topic_class'><option value=''>Select</option></select>"+
  //       "<div class='input-group-prepend'>"+
  //         "<button class='btn btn-light' onclick='$(this).parent().parent().remove();checkForm();'>&times;</button>"+
  //       "</div>"+
  //     "</div>";

  //     // fetch school classes
  //     $.ajax({
  //       url:'../../class data/getAllClasses.php',
  //       dataType:'json',
  //       data:{
  //         fetchClass:true
  //       },
  //       success:function(res){
  //         console.log(res);
  //         var data = "<option value=''>Select Class</option>";
  //        $.each(res,function(index,cls){
  //          data += "<option value='"+cls.class+"'>"+cls.class+"</option>";
  //        });
        
  //        // send classes to the class select option
  //        $('.topic_class').html(data);
  //       },
  //       error:function(error){
  //         console.log('Error fetching classes');
  //         console.log(error);
  //       }
  //     });

  //     var subject = $('#subj-select').val();
  //     if(subject == ""){
  //       xdialog.warn('No subject is selected');
  //     }else{
  //        // display the save button
  //       $('#new_topics').show();
  //       $('#save_new_topics').append(input);
  //       // show the save input bitton
  //       $('.save-topics-button').show();
  //       $('#subject_name_topic').val(subject);
  //     }
  // }

  // check if form is empty and hide the button
  function checkForm(){
    formname = document.getElementById('save_new_topics');
    var len = formname.getElementsByTagName('input').length;
    if(len ==0){
      $('.save-topics-button').hide();
      $('#new_topics').hide();
    }
  } 
  
  function countCheckbox()
  {
    var countChk = $('input[name="subject_topics_checked[]"]:checked').length;
    if(countChk > 0){
      $('#delete-topic').show();
    }else{
      $('#delete-topic').hide();
    }
  }
   // delete checked topics by enabling form submission
   $(document).on('click','#delete-topic',function(){
    var countChk = $('input[name="subject_topics_checked[]"]:checked').length;
      xdialog.confirm('You are sure to delete '+countChk+' Topics ?',function(){
        //submmit form here
        $('#topics-loaded-form-admin').submit();
        console.log('Form submitted');
      });
   });

//edit topic name and details
   $(document).on('click','.edit-topic-data',function(){
     var id = $(this).data('id');
     //alert(id);
     $('#topic_edit_modal').modal('show');
     findTopic(id);
   })

   //find topic function 
   function findTopic(id){
     $.ajax({
       url:'course work/topicFind.php',
       dataType:'json',
       data:{
         id:id
      },
       success:function(res){
        $('#topic_name_edit').val(res.topic_name);
        $('.topic-name-header').html(res.topic_name);
        $('#topic_id').val(res.id);
        $('#topic_class').val(res.class);
        $('#topic_subject').val(res.subject);
        $('#topic_paper').val(res.subject_paper);
        $('#topic_old_name').val(res.topic_name);
       },
       error:function(error){
        console.log(error);
       }
     });
   }
  
   //load subject papers on mark upload

   function loadMySubjects(val)
   {
     var level ="";
      if(val.toUpperCase() == 'SENIOR 5')
      {
        level ='ALEVEL';
      }else if(val.toUpperCase() =='SENIOR 6'){
        level ='ALEVEL';
      }else{
        level ='OLEVEL';
      }

      // ajax request
      $.ajax({
        url:'mark update/getSubjectPapers.php',
        data:{
          section:level
        },
        success:function(res){
          var subjects = JSON.parse(res);
          var subject ='<option value="">Select</option>';
          $.each(subjects,function(index,subj){
            subject +=  "<option value='"+subj.subject_name+"'>"+subj.subject_name+"</option>";
          });

          // update subjects section
          $('#mark_upload_subject').html(subject);
        }
      });
    }


   function loadMySubjectPapers()
   {
      var subject = $('#mark_upload_subject').val();
      var myclass = $('#mark_upload_class_select').val().toUpperCase();
      var level="";
      // console.log(myclass);
      // check class
      if(myclass == 'SENIOR 5')
      {
        level ='ALEVEL';
      }else if(myclass =='SENIOR 6'){
        level ='ALEVEL';
      }else{
        level ='OLEVEL';
      }

      $.ajax({
        url:'mark update/getSubjectPapers.php',
        data:{
          subject:subject,
          level:level
        },
        success:function(res){
          //get results
          var result = JSON.parse(res);
           var pap ="";
          $.each(result,function(index,data){
            pap = data.paper_names;
          });

          var papers = JSON.parse(pap);
          var opt ="";
          $.each(papers,function(index,paper){
            opt += "<option value='"+paper+"'>"+paper+"</option>";
          })
          $('#mark_upload_paper').html(opt);
          // console.log(level);
        }

      })
   }
  
  //datatables activation function
  // $('#dataTable').DataTable();
  // $('.dataTable').DataTable();


// initialise datatables designs

function openNav(id) {
  document.getElementById(id).style.width = "20%";
  document.getElementById(id).style.display = "true";
}
function closeNav(id) {
  document.getElementById(id).style.width = "0%";
    document.getElementById(id).style.display = "none";
}
// hide and show forms script functions
function hideform(id){
  document.getElementById(id).style.visibility='hidden';
}
function showform(id){
  document.getElementById(id).style.visibility='visible';
}

// activate all tooltips in the SYSTEM
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})

$('#print-canvas').on('click', function() {
  var canvas = document.querySelector("tables-print");
  var canvas_img = canvas.toDataURL("image/png",1.0); //JPEG will not match background color
  var pdf = new jsPDF('landscape','in', 'letter'); //orientation, units, page size
  pdf.addImage(canvas_img, 'png', .5, 1.75, 10, 5); //image, type, padding left, padding top, width, height
  pdf.autoPrint(); //print window automatically opened with pdf
  var blob = pdf.output("bloburl");
  window.open(blob);
});

$('.dropdown').click(function(){
  $('.dropdown-menu').toggleClass('show');
});

function printPage(id)
{
  var html="<html>";
  html+= document.getElementById(id).innerHTML;
  html+="</html>";
  var printWin = window.open('1','','left=1,top=0,width=0,height=0,toolbar=0,scrollbars=1,status =0');
  printWin.document.write(html);
  printWin.document.close();
  printWin.focus();
  printWin.print();
  printWin.close();

}

  $('#check_box_all').click(function () {
    $('input:checkbox').prop('checked', this.checked);
  });

  $(document).ready(function(){
    $('#form_message_display').modal('show');
    $('#add_dev_form').modal('show');
    $('#add_dev_form').modal('show');
  });

  function closenotif(id){
    var x = document.getElementById(id);
    x.style.display='none';
  }

  function display(id){
    var x = document.getElementById(id);
      if (x.style.display === "none") {
        x.style.display = "block";
      } else {
        x.style.display = "none";
      }
  }


$(document).on('click','.subject-edit',function(){
   var id = $(this).data('id');
   $('#subject_edit').modal('show');
   //fetch subject from the database
   findSubject(id);
 });

//  album student search
  $(document).ready(function(){
    $("#searchimg").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#class_album_print #image_div").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
    });
  });

  // side modal data
$(document).on('click','.side-modal-btn',function(){
    $('#side-modal').show('500');
});

$(document).on('click','.side-modal-close',function(){
  $(this).parent().parent().hide('500');
});

$(document).on('click','.central-modal-close',function(){
  $(this).parent().parent().hide('500');
});

function clearForm(formid) {
  // Get the form element
  var form = document.getElementById(formid);

  // Iterate through each form element and reset its value
  for (var i = 0; i < form.elements.length; i++) {
      var element = form.elements[i];

      // Check if the element is an input, textarea, or select
      if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
          element.value = '';
      }
  }
}

// for new message
$(document).on('click','.new-message-icon',function(){
  $('#central-modal-new-message').toggle();
});
// diable ck editor notifications

function searchTable(val,id)
{
    var value = val.toLowerCase();
    $("#"+id +" tr").filter(function() 
    {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
}

// image preview function
// function imagePreview(id,idimageHolder)
// {
//   document.getElementById(id).addEventListener("change", function () {
//       const file = this.files[0];
//       const preview = document.getElementById(idimageHolder);

//       if (!file) {
//           preview.style.display = "none";
//           preview.src = "";
//           return;
//       }

//       // Validate image type
//       if (!file.type.startsWith("image/")) {
//           alert("Please select a valid image file");
//           this.value = "";
//           preview.style.display = "none";
//           return;
//       }

//       const reader = new FileReader();

//       reader.onload = function (e) {
//           preview.src = e.target.result;
//           preview.style.display = "block";
//       };

//       reader.readAsDataURL(file);
//   });
// }


