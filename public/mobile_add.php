<?php 
require_once("../system/all_header.php"); 
        $table_name = "case_reg";
        //$sql ="select id, court_id, case_type, case_number, case_year, cfp_name, csp_name, next_date, date_source from case_reg where status ='ACTIVE' and case_status ='ACTIVE' and next_date <='$today'";
        $sql ="select * from case_reg where mobile ='' and case_status='ACTIVE' and status ='ACTIVE' ";
        $res = direct_sql($sql);
        ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3" >Case Status</h1>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> Case Status Details
                                     <?php // btn_save($table_name); ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $btn_arr =[
                                        'btn_update_mobile'=>'Update Mobile'
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
                        <h4 class="modal-title">Add Moblie and Email</h4>
                        <!--<button type="button" class="close" data-dismiss="modal">&times;</button>-->
                    </div>
                    <!-- Modal Body -->
                    <div class="modal-body">
                        <form action='update_mobile_email' method='post' id='update_frm' >
                            <input type='hidden' name='case_id' id='case_id'>
                            <div class="form-group">
                                <label for="mobile">Mobile </label>
                                <input type="number" class="form-control" name="mobile"  id="mobile"  required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="email@myadv.in">
                            </div>
                            
                        </form>
                        <button type="submit" class="btn btn-primary mt-3" id='update_btn'>Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        require_once("../system/footer.php"); ?>
        <script>
        $(document).on('click','.btn_cal',function () {
                var cid = $(this).data('id');
                var mobile = $(this).data('mobile');
                var email = $(this).data('email');
                $("#case_id").val(cid);
                $("#mobile").val(mobile);
                $("#email").val(email);
                $('#myModal').modal('show');
            });
        </script>
        <!--  page-wrapper -->