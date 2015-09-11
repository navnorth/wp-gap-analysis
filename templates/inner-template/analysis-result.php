<?php
	global $wpdb;
	$results_table = PLUGIN_PREFIX . "results";
	$videotable = PLUGIN_PREFIX . "videos";
	$watchtable = PLUGIN_PREFIX . "resulted_video";
	$token = htmlspecialchars($_COOKIE['GAT_token']);

	if(isset($_GET["sortby"]) && !empty($_GET["sortby"]))
	{
		switch ($_GET["sortby"]) {
			case "priority":
				$sql = $wpdb->prepare("SELECT a.* FROM $videotable as a LEFT JOIN $results_table as b ON(a.dimensions_id = b.dimension_id)
        where (b.rating_scale != NULL OR b.rating_scale != '') AND b.token=%s AND b.assessment_id=%d AND <condition> ORDER BY b.rating_scale ASC", $token, $post->ID);
				$sql = stripslashes(str_replace("<condition>", "a.`rating_scale` LIKE CONCAT('".'"'."%', b.rating_scale, '".'"'."%') OR
				 a.`rating_scale` LIKE IF((b.rating_scale = NULL OR b.rating_scale = ''), '%1%', b.rating_scale)", $sql));

				$data_rslts = $wpdb->get_results($sql);
				break;
			case "domains":
				$sql = $wpdb->prepare("SELECT a.* FROM $videotable as a LEFT JOIN $results_table as b ON(a.dimensions_id = b.dimension_id)
where (b.rating_scale != NULL OR b.rating_scale != '') AND b.token=%s AND b.assessment_id=%d AND <condition> ORDER BY b.domain_id ASC", $token, $post->ID);
				$sql = stripslashes(str_replace("<condition>", "a.`rating_scale` LIKE CONCAT('%".'"'."', b. `rating_scale`, '".'"'."%')", $sql));
				$data_rslts = $wpdb->get_results($sql);
				break;
			case "watched":
				$sql = $wpdb->prepare("SELECT a.*, ((a.seek/a.end)*100) as percent FROM $watchtable as a where assessment_id=%d AND token=%s ORDER BY CAST(percent as DECIMAL(10,5)) DESC", $post->ID, $token);
				$data_rslts = $wpdb->get_results($sql);
				break;
		}
	}
	else
	{
		$sql = $wpdb->prepare("SELECT a.* FROM $videotable as a LEFT JOIN $results_table as b ON(a.dimensions_id = b.dimension_id)
        where (b.rating_scale != NULL OR b.rating_scale != '') AND b.token=%s AND b.assessment_id=%d AND <condition> ORDER BY b.rating_scale ASC", $token, $post->ID);
        $sql = stripslashes(str_replace("<condition>", "a.`rating_scale` LIKE CONCAT('%".'"'."', b.rating_scale, '".'"'."%') OR
         a.`rating_scale` LIKE IF((b.rating_scale = NULL OR b.rating_scale = ''), '%1%', b.rating_scale)", $sql));

		$data_rslts = $wpdb->get_results($sql);
	}

	//Email Result POST
	if(isset($_POST['email_results']))
	{
		extract($_POST);

		$assign = "";
		if(!empty($data_rslts))
		{
			$i = 1;
			foreach($data_rslts as $data)
			{
				if($i <= 3)
				{
					$sql = $wpdb->prepare("SELECT title FROM wp_gat_dimensions as a WHERE id = %d", $data->dimensions_id);
					$dimensionTitle = $wpdb->get_row($sql);
					$assign .= '<ul>
									<li>
										<a href="'.get_permalink($assessment_id).'?action=analysis-result&token='.$token.'">
											'.get_the_title($data->domain_id).' - '.$dimensionTitle->title.' - '.$data->label.'
										</a>
									</li>
								</ul>';
				}
				else
				{
					break;
				}
				$i++;
			}
		}

		$to = $email;
		$from = get_option( 'admin_email' );
		$subject = get_bloginfo('name','raw').' '.get_the_title($assessment_id).' Access Code';

		$message = '<p>Thank you for participating in the '.get_the_title($assessment_id).' Assessment. If you would like to access the tool again to update your results and gauge your progress in addressing identified gaps, use this access code: <a href="'.get_permalink($assessment_id).'?action=resume-analysis&token='.$token.'">'.$token.'</a></p>';

		$message .= '<p>Here are the top videos selected for you based on your self-assessment:</p>';

		$message .= $assign;
		$message .= 'View Complete List of Video <a href="'.get_permalink($assessment_id).'?action=analysis-result&token='.$token.'"> Selections '.$token.'</a>';

		$headers = 'From: ' .$from. "\r\n" .
					'Reply-To: ' . $from."\r\n" .
					'X-Mailer: PHP/' . phpversion();
					
		add_filter( 'wp_mail_content_type', 'set_html_content_type' );
		if(wp_mail( $to, $subject, $message, $headers ))
		{
			$alert_message = 'Your assessment result sent';
		}
		remove_filter( 'wp_mail_content_type', 'set_html_content_type' ); 			
	}
	?>
	<div class="col-md-9 col-sm-12 col-xs-12 analysis_result leftpad">
		 <h3><?php echo get_the_title($post->ID); ?></h3>
          <?php
			  if(isset($alert_message) && !empty($alert_message))
				 { 
					echo '<div class="gat_error">'.$alert_message.'</div>'; 
				 }
		 ?>
		 <div class="gat_moreContent">
		 	<?php
				$content = get_post_meta($post->ID, "result_content", true);
				echo apply_filters("the_content", $content);
			?>
         </div>
		<ul class="get_domainlist analysis-result-list">
		<?php
		    $domainids = get_domainid_by_assementid($post->ID);
		    if(isset($domainids) && !empty($domainids))
		    {
			$i = 1;
			foreach($domainids as $domainid)
			{
				$domain = get_post($domainid);
				$total_dmnsn = get_dimensioncount($domainid);
				$total_dmnsn_rated = get_dimensioncount_domainid($domainid, htmlspecialchars($_COOKIE['GAT_token']));
				$total_rating = get_ratingcount_domainid($domainid, htmlspecialchars($_COOKIE['GAT_token']));
				$dimensions = get_alldimension_domainid($domainid);
				$dmnsn_percent = 100/$total_dmnsn;
				echo '<li>';
					echo '<h4><a href="'.get_permalink().'?action=token-saved&list='.$i.'"><strong>'.$domain->post_title.'</strong></a></h4>';
					echo '<div class="bar-result">';
						if ($total_dmnsn_rated>0) {
							echo '<ul class="gat_bargraph">';
								$half_total = ceil($total_dmnsn/2);
								$rVal = floor(255/$half_total);
								$n = $half_total;
								$x=0;
								foreach($dimensions as $dimension){
									if ($x>=$half_total)
										$bgColor="rgb(0,".$n*$rVal.",0)";
									else
										$bgColor="rgb(".$n*$rVal.",0,0)";
									$rating = get_rating_by_dimensionid($dimension->id,htmlspecialchars($_COOKIE['GAT_token']));
									if ($rating==0) $bgColor = "transparent";
									$title_alt = ($rating==0)?"No Answer - ":"";
									echo '<li class="bar" style="width:'.$dmnsn_percent.'%;background-color:'.$bgColor.';"><a href="'.get_permalink().'?action=token-saved&list='.$i.'#gat'.$dimension->id.'" title="'.$title_alt.$dimension->title.'">&nbsp;</a></li>';
									$x++;
									if ($x>=$half_total)
										$n++;
									else{
										$n--;
										if ($n==0) $n=1;
									}
								}
							echo '</ul>';
						} else {
							echo "<span>Not yet submitted! <a href='".get_permalink()."?action=token-saved&list=".$i."' class='domain-link'><strong>Complete the Analysis for this Focus Area Now</strong></a></span>";
						}
					echo '</div>';
				echo '</li>';
				$i++;
			}
		    }
		?>
		</ul>
		 <ul class="gat_domainsbmt_btn">
			<li><a href="<?php echo get_permalink($post->ID); ?>?action=resume-analysis" class="btn btn-default gat_buttton">Back to Focus Areas</a></li>
			<li>
            	<?php
					$response = PLUGIN_PREFIX . "response";  
					$sql = $wpdb->prepare("select email from $response where assessment_id = %d AND token = %s", $post->ID, $token);
					$result = $wpdb->get_row($sql);
				?>
            	<form method="post">
                	<input type="hidden" name="email" value="<?php echo $result->email; ?>" />
                	<input type="hidden" name="assessment_id" value="<?php echo $post->ID; ?>" />
                	<input type="submit" class="btn btn-default gat_buttton" name="email_results" value="Email Results &amp; Playlist" />
                </form>
            </li>
			<li><a href="<?php echo get_permalink($post->ID); ?>?action=video-playlist" class="btn btn-default gat_buttton">Get Your Video Playlist</a></li>
		  </ul>
	</div>
	<div class="col-md-3 col-sm-12 col-xs-12">
		<div class="gat_sharing_widget">
			<p class="pblctn_scl_icn_hedng"> Share the GAP analysis tool </p>
			<div class="pblctn_scl_icns">
			    <?php echo do_shortcode("[ssba]"); ?>
			</div>
		</div>
		<?php progress_indicator_sidebar($post->ID, $token); ?>
	</div>
