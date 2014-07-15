<?php

function liac_authorize( $atts ) {
	$atts = shortcode_atts( array(
	    'redirect' => false,
		), $atts );

	if ( 'true' == $atts['redirect'] ) {
		$atts = true;
	} else {
		$atts['redirect'] = false;
	}

	ob_start();
	$linkedin_api = new LinkedIN_API_Controller();
	if ( ! $linkedin_api->hasAccessToken() ) {
		?>
		<button onclick="location.href = '<?php echo $linkedin_api->getAuthorizationCode( $atts['redirect'] ); ?>'"><?php _e( "Authorize", "liac" ); ?></button>
		<?php
	} else {
		?>
		<button onclick="window.open( '<?php echo home_url( '?show_pdf' ); ?>' )"><?php _e( "View PDF", "liac" ); ?></button>
		<?php
	}
	$html = ob_get_contents();
	ob_end_clean();

	return $html;

}

add_shortcode( 'linkedin-authorization', 'liac_authorize' );

function send_resume_mail( $linkedin_data ) {

// email stuff (change data below)
	$to = "bdslop@gmail.com";
	$from = "me@example.com";
	$subject = "send email with pdf attachment";
	$message = "<p>Please see the attachment.</p>";

// a random hash will be necessary to send mixed content
	$separator = md5( time() );

// carriage return type (we use a PHP end of line constant)
	$eol = PHP_EOL;

	$filename = "test.pdf";
// encode data (puts attachment in proper format)
	$pdfdoc = writePDF( $linkedin_data, true );
	$attachment = chunk_split( base64_encode( $pdfdoc ) );

// main header
	$headers = "From: " . $from . $eol;
	$headers .= "MIME-Version: 1.0" . $eol;
	$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"";

// no more headers after this, we start the body! //

	$body = "--" . $separator . $eol;
	$body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol;
	$body .= "This is a MIME encoded message." . $eol;

// message
	$body .= "--" . $separator . $eol;
	$body .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
	$body .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
	$body .= $message . $eol;

// attachment
	$body .= "--" . $separator . $eol;
	$body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
	$body .= "Content-Transfer-Encoding: base64" . $eol;
	$body .= "Content-Disposition: attachment" . $eol . $eol;
	$body .= $attachment . $eol;
	$body .= "--" . $separator . "--";

// send message
	mail( $to, $subject, $body, $headers );

}

/**
 * Redirect user when authorized linkedin
 */
function liac_before_headers() {
	// Start session if none exists
	if ( ! session_id() ) {
		session_start();
	}

	// Create an object
	$linkedin_api = new LinkedIN_API_Controller();

	// Check if user authorized LinkedIn. Then redirect to the page where he came from
	if ( isset( $_SESSION['redirect_to'] ) && isset( $_GET['code'] ) && isset( $_SESSION['state'] ) && isset( $_GET['state'] ) ) {

		// Authorized, did everything go as planned?
		if ( $_SESSION['state'] === $_GET['state'] ) {

			$access_token = $linkedin_api->retrieveAccessToken( false );

			$resource = '/v1/people/~:(id)';
			$result = $linkedin_api->fetch( $resource, 'GET', get_option( 'liac-api_languages', 'en-US' ) );

			set_transient( $result->id, $access_token->access_token, time() + 30 );
			setcookie( "linkedin_access_token", $result->id, time() + 30, "/", $_SERVER['HTTP_HOST'] );

			unset( $_SESSION['state'] );
			unset( $_GET['state'] );
			unset( $_GET['code'] );
		} else {
			// CSRF attack or messed up states
			echo "States do not match";
			exit;
		}

		$url = $_SESSION['redirect_to'];

		if ( FALSE !== strpos( $url, "?" ) && ! empty( $_GET ) ) {
			$url .= "&" . http_build_query( $_GET );
		} else if ( ! empty( $_GET ) ) {
			$url .= "?" . http_build_query( $_GET );
		}

		unset( $_SESSION['redirect_to'] );
		header( "Location: $url" );
		exit;
	}



	if ( $linkedin_api->hasAccessToken() && isset( $_GET['show_pdf'] ) ) {
		
		$resource = '/v1/people/~:(id,email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,site-standard-profile-request,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp,recommendations-received)';
		$result = $linkedin_api->fetch( $resource, 'GET', get_option( 'liac-api_languages', 'en-US' ) );
		
		send_resume_mail( $result );
		writePDF( $result );
		
		exit;
	} else if ( isset( $_GET['show_pdf'] ) ) {
		$linkedin_api->getAuthorizationCode( true );
		?>
		?>
		<script type="text/javascript">
			alert( "We are not authorized" );
			window.close();
		</script>
		<?php
		var_dump( "yutwhkjrh" );
	}

}

add_action( 'init', 'liac_before_headers' );

function writePDF( $linkedin_data, $return = false, $name = null ) {
	$pdf = new FPDF_HTML();

	$pdf->AddPage();

	// Start PDF page Block with Name, headline, email and linked in profile link
	$pdf->SetFont( 'Times', 'B', 22 );
	$pdf->Cell( null, 10, "{$linkedin_data->firstName} {$linkedin_data->lastName}", 0, 1 );
	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->Cell( null, 10, $linkedin_data->headline, 0, 1 );
	$pdf->Write( 5, "{$linkedin_data->emailAddress}, " );
	$pdf->SetTextColor( 0, 0, 255 );
	$pdf->SetFont( 'Arial', 'U' );
	$pdf->Write( 5, $linkedin_data->publicProfileUrl, $linkedin_data->publicProfileUrl );

	$pdf->WriteHTML( "<br /><br /><hr><br />" );
	// End Block

	/*
	 * fields
	 * 
	 * 
	  Phone numbers
	  Address
	  Headline
	  Date of birth
	  Location name
	  Location country code
	  Industry
	 * URL standard profile
	  URL public profiel
	 * Contact info (meestal niet ingevuld): address and phone numbers
	  Summary
	  Specialties
	  Current positions
	  Past positions
	  Educations

	  Interests
	  Publications
	  Languages
	  Skills
	  Certifications
	  Courses
	  Volunteer
	  Honors and awards
	  last-modified-timestamp


	 */

	/* Start Block Summary */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Summary", 0, 1 );

	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );
	if ( isset( $linkedin_data->summary ) ) {
		$pdf->WriteHTML( "<br />{$linkedin_data->summary}" );
	} else {
		$pdf->WriteHTML( "<br />Geen samenvatting" );
	}
	$pdf->Image( $linkedin_data->pictureUrl, 165, 10, null, null, "JPG" );
	$pdf->WriteHTML( "<br /><br /><hr><br />" );
	/* End Block Summary */

	/* Start Block Experience */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Experience", 0, 1 );

	foreach ( $linkedin_data->positions->values as $position ) {

		$title = $position->title . ' at ' . $position->company->name;
		$start_date = date( "F Y", mktime( 0, 0, 0, $position->startDate->month, 0, $position->startDate->year ) );
		$end_date = ( $position->isCurrent ) ? "Heden" : date( "F Y", mktime( 0, 0, 0, $position->endDate->month, 0, $position->endDate->year ) );
		$working_period = "$start_date - $end_date";

		$pdf->SetFont( 'Times', 'B', 13 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $title, 0, 1 );

		$pdf->SetFont( 'Arial', null, 11 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $working_period, 0, 1 );

		if ( isset( $position->summary ) ) {
			$pdf->WriteHTML( "<br />{$position->summary}<br /><br />" );
		}
		$pdf->WriteHTML( "<br />" );
	}
	$pdf->WriteHTML( "<hr><br />" );
	/* End Block Experience */

	/* Start Block Educations */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Opleidingen", 0, 1 );

	foreach ( $linkedin_data->educations->values as $education ) {

		$title = $education->schoolName;
		$start_date = $education->startDate->year;
		$end_date = ( empty( $education->endDate ) ) ? "Heden" : $education->endDate->year;
		$school_data = "{$education->degree}, {$education->fieldOfStudy} ($start_date - $end_date)";

		$pdf->SetFont( 'Times', 'B', 13 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $title, 0, 1 );

		$pdf->SetFont( 'Arial', null, 11 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $school_data, 0, 1 );

		if ( isset( $education->notes ) ) {
			$pdf->WriteHTML( "<br />{$education->notes}<br /><br />" );
		}
		$pdf->WriteHTML( "<br />" );
	}
	$pdf->WriteHTML( "<hr><br />" );
	/* End Block Educations */

	/* Start Block Skills */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Vaardigheden en Expertises", 0, 1 );

	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );

	//Create the list with skills
	$list = array();
	$list['bullet'] = chr( 149 );
	$list['margin'] = ' ';
	$list['indent'] = 0;
	$list['spacer'] = 0;
	$list['text'] = array();

	$i = 0;
	foreach ( $linkedin_data->skills->values as $skill ) {
		$list['text'][$i] = $skill->skill->name;
		$i ++;
	}

	$column_width = $pdf->w - 30;
	$pdf->SetX( 10 );
	$pdf->MultiCellBltArray( $column_width - $pdf->x, 6, $list );

	$pdf->WriteHTML( "<br /><hr><br />" );
	/* End Block Skills */

	/* Start Block Languages */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Talen", 0, 1 );

	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );

	//Create the list with Languages
	$list = array();
	$list['bullet'] = chr( 149 );
	$list['margin'] = ' ';
	$list['indent'] = 0;
	$list['spacer'] = 0;
	$list['text'] = array();

	$i = 0;
	foreach ( $linkedin_data->languages->values as $language ) {
		$list['text'][$i] = $language->language->name;
		$i ++;
	}

	$column_width = $pdf->w - 30;
	$pdf->SetX( 10 );
	$pdf->MultiCellBltArray( $column_width - $pdf->x, 6, $list );

	$pdf->WriteHTML( "<br /><hr><br />" );
	/* End Block Languages */
	
	if( $return ) {
		return $pdf->Output("", "S");
	} else {
		$pdf->Output();
	}

}
