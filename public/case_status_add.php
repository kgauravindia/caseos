<?php require_once("../system/all_header.php"); 
        $table_name = "case_reg";
        //$sql ="select id, court_id, case_type, case_number, case_year, cfp_name, csp_name, next_date, date_source from case_reg where status ='ACTIVE' and case_status ='ACTIVE' and next_date <='$today'";
      
        if( $user_type =='DEV')
        {
        $sql ="select * from case_reg where status ='ACTIVE' and case_status ='ACTIVE' and next_date <='$today'";
        }
        else if($user_type=='ADMIN')
        {
        $sql ="select * from case_reg where status ='ACTIVE' and case_status ='ACTIVE' and user_id='$user_id' and next_date <='$today'";
        }
        
        else  if($user_type =='ADVOCATE' or $user_type =='ADVOCATE CLERK')
        {
            $ref_id  = get_data('ref_reg', $_SESSION['user_name'],'id','mobile')['data'];
            
        $sql ="select * from case_reg where status ='ACTIVE' and case_status ='ACTIVE' and ref_id ='$ref_id' and user_id ='$user_id' and next_date <='$today'";
        }
        
         if(isset($_GET['court_location']) and $_GET['court_location']!='')
        {
            $court_location = $_GET['court_location'];
            
           $sql .=" and court_location = '$court_location'";
        }
        $res = direct_sql($sql);
        ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3" >Case Status</h1>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class='row'>
                                        <div class='col-md-9'>
                                            <h5 class="card-title mb-0"> Case Status Details</h5>
                                        </div>
                                        <div class='col-md-3'>
                                            <form>
                                            <select name='court_location' class='form-select select2' onchange='submit()'>
                                                <?= dropdown_list('court_location','id','name', @$court_location); ?>
                                            </select>
                                            </form>
                                        </div>
                                    </div>
                               
                                </div>
                                <div class="card-body">
                                    <?php
                                    $btn_arr =[
                                        'btn_next_date'=>' Next Date '
                                        ];
                                    // echo create_report_table($table_name,  $sql, $btn_arr);
                                    echo create_data_table($table_name,  $res, $btn_arr);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
             <!-- The Modal -->
        <div class="modal" id="myModal">
            <div class="modal-dialog">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Next Date Entry in Case <span id='case_number_1'> </span>/<span id='case_year_1'>  </span> </h4>
                        <!--<button type="button" class="close" data-dismiss="modal">&times;</button>-->
                    </div>
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <form action='next_date_entry' method='post' id='insert_frm' >
                            <input type='hidden' name='case_id' id='case_id'>
                            
                            <div class="form-group">
                                <label for="name">Next Date </label>
                                <input type="date" class="form-control" name="next_date"  id="next_date"  required>
                            </div>
                            <div class="form-group">
                                <label for="email">Case Work </label>
                                <input type="text" class="form-control" name="case_work" placeholder="Case Work Details">
                            </div>
                            <div class="form-group">
                                <label for="email">Date Source </label>
                                <select name='date_source' id='date_source' class='form-select'>
                                    <?= dropdown($date_source_type,); ?>
                                </select>
                            </div>
                        </form>
                        <button type="submit" class="btn btn-primary mt-3" id='insert_btn'>Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        require_once("../system/footer.php"); ?>
        <script>
        $(document).on('click','.btn_cal',function () {
                var cid = $(this).data('id');
                var next_date = $(this).data('next_date');
                var date_source = $(this).data('date_source');
                var mobile = $(this).data('mobile');
                var email = $(this).data('email');
                var party_name = $(this).data('party_name');
                var case_type_id = $(this).data('case_type_id');
                var court_id = $(this).data('court_id');
                var case_number = $(this).data('case_number');
                var case_year = $(this).data('case_year');
                var user_id = $(this).data('user_id');
                var court_location = $(this).data('court_location');
                $("#case_id").val(cid);
                $("#next_date").val(next_date);
                $("#party_name").val(party_name);
                $("#email").val(email);
                $("#mobile").val(mobile);
                $("#case_type_id").val(case_type_id);
                $("#court_id").val(court_id);
                $("#case_number").val(case_number);
                $("#case_number_1").html(case_number);
                $("#case_year").val(case_year);
                $("#case_year_1").html(case_year);
                $("#user_id").val(user_id);
                $("#court_location").val(court_location);
                $("#date_source").val(date_source);
                $('#myModal').modal('show');
            });
        </script>