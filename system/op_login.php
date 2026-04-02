<?php require_once('op_lib.php'); 
$login_video_url = ($login_video_url=='')?'https://media.istockphoto.com/id/1497945391/video/young-beautiful-hispanic-woman-business-worker-using-calculator-working-at-office.mp4?s=mp4-640x640-is&k=20&c=1XxBuXvvsPBXRW1MSneEHSgu3x5_Ylh16dt1hpU2fpI=':$login_video_url;
$logo = ($login_video_url=='')?$base_url.'system/img/logo.png':$base_url.'upload/'.$logo;
?>
<!DOCTYPE html>
<html lang="en">
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<!-- /Added by HTTrack -->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="CaseOS 1.0 : Case Operating System for Advocates / Legal Professionals of India. Login and send notification via SMS, email to you clients regarding the case listed in different courts.">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title><?= @$inst_name ?> </title>

	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

	<!-- BEGIN SETTINGS -->
	<!-- Remove this after purchasing -->
	<link class="js-stylesheet" href="<?= $base_url ?>system/css/light.css" rel="stylesheet">
	<link class="js-stylesheet" href="<?= $base_url ?>system/css/op.css" rel="stylesheet">
	<script src="js/settings.js"></script>
	<style>
	 @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Exo:wght@600&display=swap');
		body {
			font-family: 'Roboto', sans-serif;
			opacity: 0.85;
			background:#ddd url('img/back.jpg');
			}
	    .opex{
	        font-family: 'Caveat', cursive;
	        font-size:40px;
	        text-align:center;
	        color:#ffa346;
	    }
	    .credit{
	        font-family: 'Caveat', cursive;
	        font-size:22px;
	        color:#ffa346;
	    }
	    #myVideo {
          position: fixed;
          right: 0;
          bottom: 0;
          min-width: 100%;
          min-height: 100%;
        }

	</style>
	<!-- END SETTINGS -->

<!--
  HOW TO USE: 
  data-theme: default (default), dark, light, colored
  data-layout: fluid (default), boxed
  data-sidebar-position: left (default), right
  data-sidebar-layout: default (default), compact
-->

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<main class="d-flex w-100 h-100">
	    <video autoplay muted loop id="myVideo">
      <source src="<?= $login_video_url?>" type="video/mp4">
    </video>
		<div class="container d-flex flex-column" >
			<div class="row vh-100">
			   
			   	<div class="col-sm-12 col-md-4 col-lg-8 d-table mx-auto h-100">
					<div class="d-table-cell align-middle">

						<div class="card" style='opacity:1'>
							<div class="card-body p-0" >
							    <div class='row'>
							    
							    <div class="col-md-6 bg-dark d-none d-lg-block d-sm-none" style='border-radius:10px 0px 0px 10px;background:url("img/login.jpg"); background-size:100%  100%;'> 
							    	<div class="d-table-cell align-middle h-100 pl-3 pt-5">
							    	    
							    	    <!--<br>-->
							    	    <!--<p class='opex'> OPeX provide a way to extend or enhance the functionality of a program without modifying its core codebase.</p>-->
    							     <!--   <span class='credit float-end text-warning' > -Team OfferPlant</span>-->
								    </div>
								</div>
								
								<div class="col-md-6 p-5 bg-light" style='border-radius:0px 10px 10px 0px'>
									<div class="text-center">
									        <img src='<?= $logo ?>' width ='<?= $logo_width ?>'>
											<!--<h1 class="h2 text-dark"><?= $full_name; ?></h1><br>-->
									</div>
									<form  id='login_frm' method='post' type='system'>
										<div class="mb-3">
											<label class="form-label text-dark">User Name</label>
											<input class="form-control form-control-lg" autocomplete ="username" type="text" name="user_name" placeholder="Username" />
										</div>
										<div class="mb-3">
											<label class="form-label text-dark">Password</label>
											<input class="form-control form-control-lg" type="password" autocomplete="new-password" name="user_pass" placeholder="Enter your password" />
											<small>
												<a id='forget_password' class='text-dark'>Forgot password?</a>
											</small>
										</div>
										<div>
											<label class="form-check">
												<input class="form-check-input" type="checkbox" value="remember-me" name="remember-me" checked>
												<span class="form-check-label text-dark">
													Remember me next time
												</span>
											</label>
										</div>
										<div class="text-center mt-3">
											<span class="btn btn-lg btn-dark" id='login_btn'>Sign In </span>
											<a href='op_signup.php' class="btn btn-lg btn-border border-primary">Don't Have Account ! Sign Up</a> 
										</div>
										<div class="text-center mt-3">
											
											<a href='https://caseos.olaw.in/public/caseos-help.pdf' class="btn btn-lg btn-border border-success">Help | Case Operating System</a> 
										</div>
									</form>
							    	</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
<script src="<?= $base_url?>system/js/app.js"></script>
<script src="<?= $base_url?>system/js/validate.js"></script>
<script src="<?= $base_url?>system/js/bootbox.all.js"></script>
<script src="<?= $base_url?>system/js/notify.min.js"></script>
<script src="<?= $base_url?>system/js/shortcut.js"></script>
<script src="<?= $base_url?>system/js/op.js"></script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<script>
    $(document).ready(function() {
      $(window).keydown(function(event) {
        if (event.keyCode == 13) {
          event.preventDefault();
          $("#login_btn").trigger('click');
        }
      });
    });
</script>

</body>
</html>