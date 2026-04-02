<?php 
require_once("../system/all_header.php"); 
?>

<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Calender Wise View</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
						Date Wise Task View
						</div>
						<div class="card-body">
                         
                         <style>
                                /* calendar */
                                table.calendar		{ border-left:1px solid #999; }
                                tr.calendar-row	{  }
                                td.calendar-day	{ min-height:80px; font-size:11px; position:relative; } * html div.calendar-day { height:80px; }
                                td.calendar-day:hover	{ background:#eceff5; }
                                td.calendar-day-np	{ background:#eee; min-height:60px; } * html div.calendar-day-np { height:80px; }
                                td.calendar-day-head { background:#ccc; font-weight:bold; text-align:center; width:60px; padding:5px; border-bottom:1px solid #999; border-top:1px solid #999; border-right:1px solid #999; }
                                div.day-number		{ background:#999; padding:5px; color:#111; font-weight:bold; margin: 0; width:60px; text-align:center;font-size:20px; }
                                /* shared */
                                td.calendar-day, td.calendar-day-np { width:80px; padding:5px; border-bottom:1px solid #999; border-right:1px solid #999; }
                                The above code 
                                </style>
                                    
                                        <!--  page-wrapper -->
                                        <div id="page-wrapper">
                                			<div class="row">
                                <?php
                                require_once('../function.php');
                                function draw_calendar($month,$year){
                                
                                	/* draw table */
                                	$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
                                
                                	/* table headings */
                                	$headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
                                	$calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';
                                
                                	/* days and weeks vars now ... */
                                	$running_day = date('w',mktime(0,0,0,$month,1,$year));
                                	$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
                                	$days_in_this_week = 1;
                                	$day_counter = 0;
                                	$dates_array = array();
                                
                                	/* row for week one */
                                	$calendar.= '<tr class="calendar-row">';
                                
                                	/* print "blank" days until the first of the current week */
                                	for($x = 0; $x < $running_day; $x++):
                                		$calendar.= '<td class="calendar-day-np"> </td>';
                                		$days_in_this_week++;
                                	endfor;
                                
                                	/* keep going with days.... */
                                	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
                                		$calendar.= '<td class="calendar-day">';
                                			/* add in the day number */
                                			if($list_day<=9)
                                			{
                                				$ldt ='0'.$list_day;
                                			}
                                			else{
                                				$ldt =$list_day;
                                			}
                                			$dt1 =$year.'-'.$month.'-'.$ldt ;
                                			$calendar.= '<div class="day-number"><span style="background:yellow;color:red;padding:5px;">'.datecount($dt1) .'</span> <br>' .$list_day.'</div>';
                                
                                			/** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
                                			$calendar.= str_repeat('<p> </p>',2);
                                			
                                		$calendar.= '</td>';
                                		if($running_day == 6):
                                			$calendar.= '</tr>';
                                			if(($day_counter+1) != $days_in_month):
                                				$calendar.= '<tr class="calendar-row">';
                                			endif;
                                			$running_day = -1;
                                			$days_in_this_week = 0;
                                		endif;
                                		$days_in_this_week++; $running_day++; $day_counter++;
                                	endfor;
                                
                                	/* finish the rest of the days in the week */
                                	if($days_in_this_week < 8):
                                		for($x = 1; $x <= (8 - $days_in_this_week); $x++):
                                			$calendar.= '<td class="calendar-day-np"> </td>';
                                		endfor;
                                	endif;
                                
                                	/* final row */
                                	$calendar.= '</tr>';
                                
                                	/* end the table */
                                	$calendar.= '</table>';
                                	
                                	/* all done, return result */
                                	return $calendar;
                                }
                                
                                /* sample usages */
                                if(isset($_GET['tdate']))
                                {
                                	$t=$_GET['tdate'];
                                	$m =date('m',strtotime($t));
                                	$y =date('Y',strtotime($t));
                                	echo "<h2>". date('F-Y',strtotime($t))."</h2>";
                                }
                                else{
                                	$m=date('m');
                                	$y=date('Y');
                                	echo "<h2>". date('F-Y')."</h2>";
                                }
                                echo "<form action='' onChange='submit()'>";
                                echo "<input type='date' name='tdate'>";
                                echo "</form>";
                                echo draw_calendar($m,$y);
                                ?>
                         </div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>
<?php 
require_once("../system/footer.php"); ?>