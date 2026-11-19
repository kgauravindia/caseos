<?php require_once('function.php'); ?>
<!DOCTYPE html>
<html lang="en">
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<!-- /Added by HTTrack -->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="CaseOS : Legal Case Management System for Advocates">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="<?= $base_url ?>img/icons/icon-48x48.png" />

	<!--<link rel="canonical" href="pages-sign-in.html" />-->

	<title><?= @$inst_name ?> </title>

	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
	<!-- Choose your prefered color scheme -->
	<!-- <link href="css/light.css" rel="stylesheet"> -->
	<!-- <link href="css/dark.css" rel="stylesheet"> -->

	<!-- BEGIN SETTINGS -->
	<link class="js-stylesheet" href="<?= $base_url ?>system/css/light.css" rel="stylesheet">
	<link class="js-stylesheet" href="<?= $base_url ?>system/css/op.css" rel="stylesheet">
	<script src="<?= $base_url ?>system/js/settings.js"></script>
	<style>
		body {
			font-family: 'Roboto', sans-serif;
			opacity: 0.90;
			overflow-y:hidden;
		}
		#myVideo {
          position: fixed;
          right: 0;
          bottom: 0;
          min-width: 100%;
          min-height: 100%;
        }
	</style>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<!-- END SETTINGS -->

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(88, 28, 135, 0.4)); z-index: -1;"></div>
    <video autoplay muted loop id="myVideo" style="z-index: -2;">
      <source src="https://media.istockphoto.com/id/1497945391/video/young-beautiful-hispanic-woman-business-worker-using-calculator-working-at-office.mp4?s=mp4-640x640-is&k=20&c=1XxBuXvvsPBXRW1MSneEHSgu3x5_Ylh16dt1hpU2fpI=" type="video/mp4">
    </video>

	<main class="d-flex w-100 h-100">
		<div class="container d-flex flex-column">
			<div class="row vh-100">
				<div class="col-sm-10 col-md-8 col-lg-5 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">

									<div class="text-center mb-4">
										<div class="mb-3 d-inline-block p-3 rounded-circle" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
										    <img src="<?= $base_url ?>system/img/logo.png" alt="Logo" class="img-fluid" width="100" height="100" />
										</div>
                                        <h1 class="h2 text-white fw-bold"><?= $full_name; ?></h1>
                                        <p class="text-white-50">Welcome back! Please login to your account.</p>
									</div>
									<div class="card glass shadow-premium">
										<div class="card-body p-4">
											<form id='login_frm'>
												<div class="mb-4">
													<label class="form-label text-dark fw-semibold">User Name</label>
													<div class="input-group">
													    <span class="input-group-text bg-light border-end-0"><i data-feather="user" class="feather-sm"></i></span>
													    <input class="form-control form-control-lg border-start-0" type="text" name="user_name" placeholder="Enter Your Username" />
													</div>
												</div>
												<div class="mb-4">
													<label class="form-label text-dark fw-semibold">Password</label>
													<div class="input-group">
													    <span class="input-group-text bg-light border-end-0"><i data-feather="lock" class="feather-sm"></i></span>
													    <input class="form-control form-control-lg border-start-0" type="password" name="user_pass" placeholder="Enter your password" />
													</div>
													<div class="text-end mt-2">
														<a id='forget_password' class='text-primary small fw-semibold cursor-pointer' style="text-decoration: none;">Forgot password?</a>
													</div>
												</div>
												<div class="mb-4">
													<label class="form-check">
														<input class="form-check-input" type="checkbox" value="remember-me" name="remember-me" checked>
														<span class="form-check-label text-muted">
															Keep me logged in
														</span>
													</label>
												</div>
												<div class="d-grid mt-3">
													<button class="btn btn-lg btn-primary shadow-sm" id='login_btn' type="button">
													    <i data-feather="log-in" class="me-2 feather-sm"></i> Secure Login
													</button>
												</div>
											</form>
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

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    $(document).ready(function() {
      feather.replace();
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