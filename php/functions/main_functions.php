<?php


function liac_get_default_data() {
    
    $linkedin_api = liac_get_api_controller();
    
    $resource = '/v1/people/~:(id,email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp,recommendations-received)';
    $result = $linkedin_api->fetch( $resource, 'GET', get_option( 'liac-api_languages', 'en-US' ) );
    
    return $result;
    
}

/**
 * Get the email template for the application email
 * @return string
 */
function liac_get_email_template( $type = "organization" ) {

    $filename = "liac-email-template-$type.php";

    if ( file_exists( TEMPLATEPATH . "/$filename" ) ) {
	$filename = TEMPLATEPATH . "/$filename";
    } else {
	$filename = LIAC_ROOT . "/templates/$filename";
    }
    return $filename;
}

/**
 * Get the LinkedIn API Controller object
 * @return \LinkedIN_API_Controller
 */
function liac_get_api_controller() {

    $settings = array(
	'api_key' => get_option( 'liac-api_key' ),
	'api_secret' => get_option( 'liac-api_secret' ),
	'scope' => get_option( 'liac-api_scope' ),
	'redirect_uri' => get_option( 'liac-api_redirect' )
    );

    return new LinkedIN_API_Controller( $settings );
}

/**
 * Shortcode function to authorize the plugin at LinkedIn
 * @param array $atts Attributes of the shortcode
 * @return mixed Most likely to return a string. The code or text to show.
 */
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
    $linkedin_api = liac_get_api_controller();
    if ( !$linkedin_api->hasAccessToken() ) {
	?>
	<a class="liac-apply_linkedin_button" href='<?php echo $linkedin_api->getAuthorizationCode( $atts['redirect'] ); ?>'></a>
	<?php
    } else {

	$content = "<a class='liac-apply_linkedin_button' href='" . home_url( '?liac-show-pdf' ) . "' target='_blank'></a>";
	$content = apply_filters( "liac-authorized_content", $content );

	echo $content;
    }
    $html = ob_get_contents();
    ob_end_clean();

    $html = apply_filters( "liac-authorization_content", $html );

    return $html;
}

add_shortcode( 'liac-linkedin_authorization', 'liac_authorize' );

/**
 * Sends an email with a resume.
 * @param object $linkedin_data An PHP Object with the linkedin data
 */
function liac_send_resume_mail( $linkedin_data, $vacancy_id ) {
    
    global $wpdb;
    
    $vacancy = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}posts WHERE ID = $vacancy_id" );
    
    // email stuff (change data below)
    $to = get_option( 'liac-api_email' );
    $from = $linkedin_data->email;
    $subject = __( "{$linkedin_data->first_name} {$linkedin_data->last_name} applied to {$vacancy->post_title}", "liac" );

    ob_start();
    include_once( liac_get_email_template() );
    $message_organization = ob_get_contents();
    ob_end_clean();
    
    ob_start();
    include_once( liac_get_email_template( "applicant" ) );
    $message_applicant = ob_get_contents();
    ob_end_clean();

    // a random hash will be necessary to send mixed content
    $separator = md5( time() );

    // carriage return type (we use a PHP end of line constant)
    $eol = PHP_EOL;

    $filename = "{$linkedin_data->first_name}_{$linkedin_data->last_name}_CV.pdf";
    // encode data (puts attachment in proper format)
    $pdfdoc = liac_writePDF( $linkedin_data, true );
    $attachment = chunk_split( base64_encode( $pdfdoc ) );

    // Organization headers
    $headers_organization = "From: " . $from . $eol;
    $headers_organization .= "MIME-Version: 1.0" . $eol;
    $headers_organization .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"";
    
    // Applicant Headers
    $headers_applicant = "From: " . $to . $eol;
    $headers_applicant .= "MIME-Version: 1.0" . $eol;
    $headers_applicant .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"";
    
    // no more headers after this, we start the body! //

//    $body = "--" . $separator . $eol;
//    $body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol;
//    $body .= "This is a MIME encoded message." . $eol;
    
    // Create Organization email
    // message
    $body_organization .= "--" . $separator . $eol;
    $body_organization .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
    $body_organization .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $body_organization .= $message_organization . $eol;

    // attachment
    $body_organization .= "--" . $separator . $eol;
    $body_organization .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
    $body_organization .= "Content-Transfer-Encoding: base64" . $eol;
    $body_organization .= "Content-Disposition: attachment" . $eol . $eol;
    $body_organization .= $attachment . $eol;
    $body_organization .= "--" . $separator . "--";
    // End create organization email
    
    // Create Applicant email
    // message
    $body_applicant .= "--" . $separator . $eol;
    $body_applicant .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
    $body_applicant .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $body_applicant .= $message_applicant . $eol;

    // attachment
    $body_applicant .= "--" . $separator . $eol;
    $body_applicant .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
    $body_applicant .= "Content-Transfer-Encoding: base64" . $eol;
    $body_applicant .= "Content-Disposition: attachment" . $eol . $eol;
    $body_applicant .= $attachment . $eol;
    $body_applicant .= "--" . $separator . "--";
    // End create Applicant email
    
    // send message
    mail( $from, $subject, $body_applicant, $headers_applicant );
    mail( $to, $subject, $body_organization, $headers_organization );
}

/**
 * Redirect user when authorized linkedin
 */
function liac_before_headers() {
    // Start session if none exists
    if ( !session_id() ) {
	session_start();
    }

    // Create an object
    $linkedin_api = liac_get_api_controller();
    
    // Check if user authorized LinkedIn. Then redirect to the page where he came from
    if ( isset( $_SESSION['redirect_to'] ) && isset( $_GET['code'] ) && isset( $_SESSION['state'] ) && isset( $_GET['state'] ) ) {

	// Authorized, did everything go as planned?
	if ( $_SESSION['state'] === $_GET['state'] ) {

	    $access_token = $linkedin_api->retrieveAccessToken( false );

	    $resource = '/v1/people/~:(id)';
	    $result = $linkedin_api->fetch( $resource, 'GET', get_option( 'liac-api_languages', 'en-US' ) );
	    
	    set_transient( $result->id, $access_token->access_token, time() + $access_token->expires_in - ( 60 * 60 * 24 * 10 ) );
	    setcookie( "linkedin_access_token", $result->id, time() + $access_token->expires_in - ( 60 * 60 * 24 * 10 ), "/", $_SERVER['HTTP_HOST'] );

	    unset( $_SESSION['state'] );
	    unset( $_GET['state'] );
	    unset( $_GET['code'] );
	} else {
	    // CSRF attack or messed up states
	    echo "States do not match";
	    exit;
	}

	$url = $_SESSION['redirect_to'];

	if ( FALSE !== strpos( $url, "?" ) && !empty( $_GET ) ) {
	    $url .= "&" . http_build_query( $_GET );
	} else if ( !empty( $_GET ) ) {
	    $url .= "?" . http_build_query( $_GET );
	}
	
	unset( $_SESSION['redirect_to'] );
	header( "Location: $url" );
	exit;
    }


if ( ( $linkedin_api->hasAccessToken() ) && ( isset( $_GET['liac-show-pdf'] ) || isset( $_GET['liac-apply-via-mail'] ) ) ) {
    
	$resource = '/v1/people/~:(id,email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp,recommendations-received)';
	$result = $linkedin_api->fetch( $resource, 'GET', get_option( 'liac-api_languages', 'en-US' ) );

	if ( isset( $_GET['liac-show-pdf'] ) ) {

	    liac_writePDF( $result );
	    exit;
	} else if ( $linkedin_api->hasAccessToken() && isset( $_GET['liac-apply-via-mail'] ) ) {
	    
	    if( isset( $_GET['vacancy_id'] ) && is_numeric( $_GET['vacancy_id'] ) ) {
		liac_send_resume_mail( $result, $_GET['vacancy_id'] );
		
		ob_start();
		?>
		
		<div class="liac-email-notification">
		    <?php _e( "You successfully applied via linkedin!", "liac" ); ?>
		</div>
		<?php
		$email_notification = ob_get_contents();
		ob_end_clean();
		
		$email_notification = apply_filters( "liac-email_apply_success_notification", $email_notification, $_GET['vacancy_id'] );
		
		echo $email_notification;
	    } else {
		_e( "Vacancy ID unknown (parameter 'vacancy_id' not set or not numeric)!", "liac" );
	    }
	}
    } else {
	
	if ( isset( $_GET['liac-show-pdf'] ) || isset( $_GET['liac-apply-via-mail'] ) ) {
	    // Not authorized so redirect to authorize again before watching pdf or send email
	    $linkedin_api->getAuthorizationCode( true );
	}
    }
}

add_action( 'init', 'liac_before_headers' );

function liac_writePDF( $linkedin_data, $return = false, $name = null ) {
    $linkedin_data instanceof LIAC_Data;
    $pdf = new FPDF_HTML();

    $pdf->AddPage();

    // Start PDF page Block with Name, headline, email and linked in profile link
    $pdf->SetFont( 'Times', 'B', 22 );
    $pdf->Cell( null, 10, "{$linkedin_data->first_name} {$linkedin_data->last_name}", 0, 1 );
    $pdf->SetFont( 'Arial', null, 11 );
    $pdf->Cell( null, 10, $linkedin_data->headline, 0, 1 );
    $pdf->Write( 5, __( "Phone number", "liac" ) . ": {$linkedin_data->phone_number}" );
    $pdf->Ln();
    $pdf->Write( 5, __( "E-mail address", "liac" ) . ": {$linkedin_data->email}" );
    $pdf->Ln();
    $pdf->Write( 5, __( "LinkedIn URL", "liac" ) . ": ");
    $pdf->SetTextColor( 0, 0, 255 );
    $pdf->SetFont( 'Arial', 'U' );
    $pdf->Write( 5, $linkedin_data->public_profile_url, $linkedin_data->public_profile_url );
    
    $pdf->WriteHTML( "<br /><br /><hr><br />" );
    // End Block

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
    $pdf->Image( $linkedin_data->picture_url, 165, 10, null, null, "JPG" );
    $pdf->WriteHTML( "<br /><br /><hr><br />" );
    /* End Block Summary */

    /* Start Block Experience */
    $pdf->SetFont( 'Times', 'B', 17 );
    $pdf->SetTextColor( 150, 150, 150 );
    $pdf->Cell( null, 10, "Experience", 0, 1 );
    
    foreach ( $linkedin_data->all_positions as $position ) {

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

    foreach ( $linkedin_data->educations as $education ) {

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
    foreach ( $linkedin_data->skills as $skill ) {
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
    foreach ( $linkedin_data->languages as $language ) {
	$list['text'][$i] = $language->language->name;
	$i ++;
    }

    $column_width = $pdf->w - 30;
    $pdf->SetX( 10 );
    $pdf->MultiCellBltArray( $column_width - $pdf->x, 6, $list );

    $pdf->WriteHTML( "<br /><hr><br />" );
    /* End Block Languages */

    if ( $return ) {
	return $pdf->Output( "", "S" );
    } else {
	$pdf->Output();
    }
}
