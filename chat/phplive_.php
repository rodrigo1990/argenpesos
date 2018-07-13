<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	include_once( "./web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_IP.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Vars/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Security.php" ) ;

	$postembed = Util_Format_Sanatize( Util_Format_GetVar( "postembed" ), "n" ) ;
	$deptid = Util_Format_Sanatize( Util_Format_GetVar( "deptid" ), "n" ) ;
	$theme = Util_Format_Sanatize( Util_Format_GetVar( "theme" ), "ln" ) ;
	$ces = Util_Format_Sanatize( Util_Format_GetVar( "ces" ), "ln" ) ;
	$auto_pop = Util_Format_Sanatize( Util_Format_GetVar( "auto_pop" ), "ln" ) ;
	$popout = Util_Format_Sanatize( Util_Format_GetVar( "popout" ), "n" ) ;
	$vname = Util_Format_Sanatize( Util_Format_GetVar( "vname" ), "ln" ) ;
	$vemail = Util_Format_Sanatize( Util_Format_GetVar( "vemail" ), "e" ) ;
	$vsubject = rawurldecode( Util_Format_Sanatize( Util_Format_GetVar( "vsubject" ), "htmltags" ) ) ;
	$question = Util_Format_Sanatize( Util_Format_GetVar( "vquestion" ), "htmltags" ) ;
	$onpage = rawurldecode( Util_Format_Sanatize( Util_Format_GetVar( "onpage" ), "url" ) ) ;  $onpage = ( $onpage ) ? $onpage : "" ;
	$title = Util_Format_Sanatize( Util_Format_GetVar( "title" ), "title" ) ; $title = ( $title ) ? $title : "" ;
	$resolution = Util_Format_Sanatize( Util_Format_GetVar( "win_dim" ), "ln" ) ;
	$widget = Util_Format_Sanatize( Util_Format_GetVar( "widget" ), "n" ) ;
	$embed = Util_Format_Sanatize( Util_Format_GetVar( "embed" ), "n" ) ;
	$custom = Util_Format_Sanatize( Util_Format_GetVar( "custom" ), "ln" ) ;
	$token = Util_Format_Sanatize( Util_Format_GetVar( "token" ), "ln" ) ;
	$dept_themes = ( isset( $VALS["THEMES"] ) ) ? unserialize( $VALS["THEMES"] ) : Array() ;
	if ( !$theme && isset( $dept_themes[$deptid] ) && $deptid ) { $theme = $dept_themes[$deptid] ; }
	else if ( !$theme ) { $theme = $CONF["THEME"] ; }

	$now = time() ;
	$salt = md5( $CONF["SALT"] ) ;
	$agent = isset( $_SERVER["HTTP_USER_AGENT"] ) ? $_SERVER["HTTP_USER_AGENT"] : "&nbsp;" ;
	LIST( $ip, $vis_token ) = Util_IP_GetIP( $token ) ;
	LIST( $os, $browser ) = Util_Format_GetOS( $agent ) ;
	$mobile = ( $os == 5 ) ? 1 : 0 ;

	if ( !$ces ) { $ces = Util_Security_GenSetupSes() ; }
	$vemail = ( !$vemail ) ? "null" : $vemail ;
	if ( preg_match( "/$ip/", $VALS["CHAT_SPAM_IPS"] ) )
	{
		database_mysql_close( $dbh ) ;
		$url_redirect = "phplive_m.php?ces=$ces&deptid=$deptid&theme=$theme&embed=$embed&vname=$vname&vemail=$vemail&vquestion=&onpage=".urlencode( Util_Format_URL( $onpage ) )."&custom=$custom" ;
		if ( $postembed )
		{
			$url_redirect = rawurlencode( $url_redirect ) ;
			$json_data = "json_data = { \"status\": 0, \"url_redirect\": \"$url_redirect\" };" ;
			print $json_data ; exit ;
		}
		else { HEADER( "location: $url_redirect" ) ; }
	}

	if ( $deptid && $ces && $vname )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Functions_itr.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Email.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/get.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/update.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get_itr.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/put.php" ) ;
		include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/get_ext.php" ) ;

		if ( $popout ) { $requestinfo = Chat_get_itr_RequestCesInfo( $dbh, $ces ) ; }
		else { $requestinfo = Chat_get_itr_RequestIPInfo( $dbh, $ip, $vis_token ) ; }
		$deptinfo = Depts_get_DeptInfo( $dbh, $deptid ) ;
		if ( $deptinfo["smtp"] )
		{
			$smtp_array = unserialize( Util_Functions_itr_Decrypt( $CONF["SALT"], $deptinfo["smtp"] ) ) ;
			$CONF["SMTP_HOST"] = $smtp_array["host"] ;
			$CONF["SMTP_LOGIN"] = $smtp_array["login"] ;
			$CONF["SMTP_PASS"] = $smtp_array["pass"] ;
			$CONF["SMTP_PORT"] = $smtp_array["port"] ;
		}
		if ( $deptinfo["lang"] ) { $CONF["lang"] = $deptinfo["lang"] ; }
		include_once( "$CONF[DOCUMENT_ROOT]/lang_packs/".Util_Format_Sanatize($CONF["lang"], "ln").".php" ) ;

		if ( isset( $requestinfo["requestID"] ) )
		{
			$vname = $requestinfo["vname"] ;
			$vemail = $requestinfo["vemail"] ;
			$question = $requestinfo["question"] ;
		}
		else
		{
			$vname_orig = $vname ;
			$question = preg_replace( "/(\r\n)|(\n)|(\r)/", "<br>", preg_replace( "/\"/", "&quot;", $question ) ) ;
			$question_sms = preg_replace( "/<br>/", " ", $question ) ;
			$question_sms = ( strlen( $question_sms ) > 100 ) ? substr( $question_sms, 0, 100 ) . "..." : $question_sms ;
		}

		$sim_ops = "" ;
		Ops_update_CurcValues( $dbh ) ;
		if ( $deptinfo["rtype"] < 3 )
		{
			if ( isset( $requestinfo["opID"] ) ) { $opinfo_next = Ops_get_OpInfoByID( $dbh, $requestinfo["opID"] ) ; }
			else { $opinfo_next = Ops_get_NextRequestOp( $dbh, $deptid, $deptinfo["rtype"], "" ) ; }
			if ( !isset( $opinfo_next["opID"] ) )
			{
				database_mysql_close( $dbh ) ;

				$url_redirect = "phplive_m.php?ces=$ces&chat=1&deptid=$deptid&token=$token&theme=$theme&embed=$embed&vname=$vname&vemail=$vemail&vquestion=".rawurlencode($question)."&title=".rawurlencode($title)."&onpage=".rawurlencode( Util_Format_URL( $onpage ) )."&custom=$custom" ;
				if ( $postembed )
				{
					$url_redirect = rawurlencode( $url_redirect ) ;
					$json_data = "json_data = { \"status\": 0, \"url_redirect\": \"$url_redirect\" };" ;
					print $json_data ; exit ;
				}
				else { HEADER( "location: $url_redirect" ) ; }
			}
			else { $opid = $opinfo_next["opID"] ; }

			if ( !isset( $requestinfo["opID"] ) && ( $opinfo_next["sms"] == 1 ) )
				Util_Email_SendEmail( $opinfo_next["name"], $opinfo_next["email"], $vname_orig, base64_decode( $opinfo_next["smsnum"] ), "Chat Request", $question_sms, "sms" ) ;
		}
		else
		{
			$opid = 1111111111 ; $sim_ops = "" ;
			$opinfo_next = Array( "rate" => 0, "sms" => 0 ) ;
			if ( !isset( $requestinfo["requestID"] ) )
			{
				$sim_operators = Depts_get_DeptOps( $dbh, $deptid, 1 ) ;
				for ( $c = 0; $c < count( $sim_operators ); ++$c )
				{
					$operator = $sim_operators[$c] ;
					$sim_ops .= "$operator[opID]-" ;
					if ( !isset( $requestinfo["opID"] ) && ( $operator["sms"] == 1 ) )
						Util_Email_SendEmail( $operator["name"], $operator["email"], $vname_orig, base64_decode( $operator["smsnum"] ), "Chat Request", $question_sms, "sms" ) ;
				}
			}
		}

		$vses = $t_vses = 1 ; $connected = $created_embed = 0 ; $connected_trans = $text = "" ;
		if ( isset( $requestinfo["requestID"] ) )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/update.php" ) ;

			$requestid = $requestinfo["requestID"] ;
			$t_vses = $requestinfo["t_vses"] ;
			$vses = $t_vses + 1 ;
			Chat_update_RequestValueByCes( $dbh, $requestinfo["ces"], "t_vses", $vses ) ;

			if ( $vses > $VARS_MAX_EMBED_SESSIONS ) { $vses = $vses - $VARS_MAX_EMBED_SESSIONS ; }
			function get_diff( $x, $y ) { return $x-$y ; }
			$diff = get_diff( $vses, $VARS_MAX_EMBED_SESSIONS ) ;
			while( $diff > 0 )
			{
				$vses = $diff ;
				$diff = get_diff( $diff, $VARS_MAX_EMBED_SESSIONS ) ;
			}

			if ( $requestinfo["status"] && is_file( "$CONF[CHAT_IO_DIR]/$ces.txt" ) )
			{
				$connected = 1 ;
				$created_embed = $requestinfo["created"] ;
	
				$rid = "0_$vses" ;
				$filename = $ces."-".$rid ;

				if ( is_file( "$CONF[CHAT_IO_DIR]/$filename.text" ) )
					unlink( "$CONF[CHAT_IO_DIR]/$filename.text" ) ;

				$chat_file = "$CONF[CHAT_IO_DIR]/$ces.txt" ;
				$trans_raw = file( $chat_file ) ;
				$text = addslashes( preg_replace( "/\"/", "&quot;", $trans_raw[0] ) ) ;
				$text = preg_replace( "/(\r\n)|(\n)|(\r)/", "<br>", $text ) ;
			}
		}
		else
		{
			$referinfo = Footprints_get_IPRefer( $dbh, $vis_token ) ;
			$marketid = ( isset( $referinfo["marketID"] ) && $referinfo["marketID"] ) ? $referinfo["marketID"] : 0 ;
			$vis_token_ = $vis_token ;
			if ( !$embed && !$widget ) { $vis_token = "" ; }

			$refer = ( isset( $referinfo["refer"] ) ) ? $referinfo["refer"] : "" ;
			$requestid = Chat_put_Request( $dbh, $deptid, $opid, 0, $widget, 0, $vses, $os, $browser, $ces, $resolution, $vname, $vemail, $ip, $vis_token, $vis_token_, $onpage, $title, $question, $marketid, $refer, $custom, $auto_pop, $sim_ops ) ;
		}
		
		if ( $requestid )
		{
			include_once( "$CONF[DOCUMENT_ROOT]/API/Ops/put_itr.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/update.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/Util.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Marquee/get.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/Footprints/update.php" ) ;
			include_once( "$CONF[DOCUMENT_ROOT]/API/IPs/update.php" ) ;

			Footprints_update_FootprintUniqueValue( $dbh, $vis_token, "chatting", 1 ) ;
			if ( isset( $requestinfo["requestID"] ) && !$requestinfo["status"] && $requestinfo["initiated"] )
			{
				Chat_update_RequestValueByCes( $dbh, $requestinfo["ces"], "status", 1 ) ;
				Ops_put_itr_OpReqStat( $dbh, $deptid, $opid, "initiated_", 1 ) ;
				$filename = $ces."-".$opid ;
				$text = "<widget><div class='ca'><b>$vname</b> ".$LANG["CHAT_NOTIFY_JOINED"]."</div></widget>" ;
				UtilChat_AppendToChatfile( "$ces.txt", $text ) ;
				UtilChat_AppendToChatfile( "$filename.text", $text ) ;
			}
			else if ( !isset( $requestinfo["requestID"] ) )
			{
				include_once( "$CONF[DOCUMENT_ROOT]/API/IPs/put.php" ) ;
				IPs_put_IP( $dbh, $ip, $vis_token, $deptid, 0, 1, 0, 1, 0, 0, $now ) ;
				Chat_put_ReqLog( $dbh, $requestid ) ;
				Footprints_update_FootprintUniqueValue( $dbh, $vis_token, "requests", "requests + 1" ) ;

				if ( !isset( $CONF["cookie"] ) || ( $CONF["cookie"] == "on" ) )
				{
					if ( $vname_orig != "null" ) { setcookie( "phplive_vname", $vname_orig, $now+60*60*24*365 ) ; }
					if ( $vemail != "null" ) { setcookie( "phplive_vemail", $vemail, $now+60*60*24*365 ) ; }
				}
				if ( $deptinfo["rtype"] < 3 ) { Ops_put_itr_OpReqStat( $dbh, $deptid, $opid, "requests", 1 ) ; }
				else
				{
					Ops_put_itr_OpReqStat( $dbh, $deptid, 0, "requests", 1 ) ;
					for ( $c = 0; $c < count( $sim_operators ); ++$c )
					{
						$operator = $sim_operators[$c] ;
						Ops_put_itr_OpReqStat( $dbh, 0, $operator["opID"], "requests", 1 ) ;
					}
				}
				$text = ( $question ) ? "<div class='ca'><i>".$question."</i></div>" : "" ;
				UtilChat_AppendToChatfile( "$ces.txt", $text ) ;

				if ( $postembed )
				{
					$json_data = "json_data = { \"status\": 1, \"deptid\": $deptid, \"ces\": \"$ces\" };" ;
					print $json_data ; exit ;
				}
			}

			// reset auto initiate timer since visitor requested chat
			$initiate_array = ( isset( $VALS["auto_initiate"] ) && $VALS["auto_initiate"] ) ? unserialize( html_entity_decode( $VALS["auto_initiate"] ) ) : Array() ;
			$auto_initiate_reset = ( isset( $initiate_array["reset"] ) ) ? $initiate_array["reset"] : 60*60 ;
			$reset = 60*60*24*$auto_initiate_reset ;
			IPs_update_IpValue( $dbh, $vis_token, "i_initiate", $now + $reset ) ;

			$marquees = Marquee_get_DeptMarquees( $dbh, $deptid ) ;
			$marquee_string = "" ;
			for ( $c = 0; $c < count( $marquees ); ++$c )
			{
				$marquee = $marquees[$c] ;
				$snapshot = preg_replace( "/'/", "&#39;", preg_replace( "/\"/", "&quot;", $marquee["snapshot"] ) ) ;
				$message = preg_replace( "/'/", "&#39;", preg_replace( "/\"/", "", $marquee["message"] ) ) ;

				$marquee_string .= "marquees[$c] = '$snapshot' ; marquees_messages[$c] = '$message' ; " ;
			}
			if ( !count( $marquees ) )
				$marquee_string = "marquees[0] = '' ; marquees_messages[0] = '' ; " ;

			$stars_five = Util_Functions_Stars( 5 ) ; $stars_four = Util_Functions_Stars( 4 ) ; $stars_three = Util_Functions_Stars( 3 ) ; $stars_two = Util_Functions_Stars( 2 ) ; $stars_one = Util_Functions_Stars( 1 ) ;

			$email_display = ( $vemail != "null" ) ? $vemail : "" ;
			$div_email = ( $deptinfo["temail"] ) ? "<div class='cl'>$LANG[TXT_EMAIL] : <input type='text' class='input_text' size='30' malength='160' id='vemail' name='vemail' value='$email_display'> <input type='button' id='btn_email' value='$LANG[CHAT_BTN_EMAIL_TRANS]' onClick='send_email()'></div>" : "" ;
			$survey = "$div_email<div class='cl'><div class='ctitle'>".$LANG["CHAT_NOTIFY_RATE"]."</div>
				<table cellspacing=0 cellpadding=2 border=0 style='padding-top: 10px; padding-bottom: 10px;'>
				<tr><td><input type='radio' name='rating' id='rating_5' value=5 onClick='submit_survey(this, survey_texts)'></td><td style='padding-left: 2px;'>$stars_five</td></tr>
				<tr><td><input type='radio' name='rating' id='rating_4' value=4 onClick='submit_survey(this, survey_texts)'></td><td style='padding-left: 2px;'>$stars_four</td></tr>
				<tr><td><input type='radio' name='rating' id='rating_3' value=3 onClick='submit_survey(this, survey_texts)'></td><td style='padding-left: 2px;'>$stars_three</td></tr>
				<tr><td><input type='radio' name='rating' id='rating_2' value=2 onClick='submit_survey(this, survey_texts)'></td><td style='padding-left: 2px;'>$stars_two</td></tr>
				<tr><td><input type='radio' name='rating' id='rating_1' value=1 onClick='submit_survey(this, survey_texts)'></td><td style='padding-left: 2px;'>$stars_one</td></tr>
				</table></div>" ;
			$survey = preg_replace( "/(\r\n)|(\n)|(\r)/", "", $survey ) ;

			$socials = Vars_get_Socials( $dbh, $deptid ) ;
			if ( !count( $socials ) && $deptid )
				$socials = Vars_get_Socials( $dbh, 0 ) ;
			$socials_string = "" ;
			foreach ( $socials as $social => $data )
			{
				if ( $data["status"] )
					$socials_string .= "<a href=\"$data[url]\" target=\"_blank\" title=\"$data[tooltip]\" alt=\"$data[tooltip]\"><img src=\"themes/$theme/social/$social.png\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"></a> &nbsp;" ;
			}
		}
		else { ErrorHandler( 603, "Chat session did not create.  $dbh[query]<br>$dbh[error].", $PHPLIVE_FULLURL, 0, Array() ) ; }
	}
	else
	{
		$onpage = rawurlencode( Util_Format_URL( $onpage ) ) ;
		database_mysql_close( $dbh ) ;

		$url_redirect = "phplive.php?d=$deptid&token=$token&onpage=$onpage&embed=$embed&theme=$theme" ;
		if ( $postembed )
		{
			$url_redirect = rawurlencode( $url_redirect ) ;
			$json_data = "json_data = { \"status\": 0, \"url_redirect\": \"$url_redirect\" };" ;
			print $json_data ; exit ;
		}
		else { HEADER( "location: $url_redirect" ) ; }
	}

	include_once( "./inc_cache.php" ) ;
?>
<?php include_once( "./inc_doctype.php" ) ?>
<?php if ( isset( $CONF["KEY"] ) && ( $CONF["KEY"] == md5($KEY."-c615") ) ): ?><?php else: ?>
<!--
********************************************************************
* PHP Live! (c) OSI Codes Inc.
* www.phplivesupport.com
********************************************************************
-->
<?php endif ; ?>
<head>
<title> <?php echo $LANG["CHAT_WELCOME"] ?> </title>

<meta name="description" content="v.<?php echo $VERSION ?>">
<meta name="keywords" content="<?php echo md5( $KEY ) ?>">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=<?php echo $LANG["CHARSET"] ?>">
<?php include_once( "./inc_meta_dev.php" ) ; ?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

<link rel="Stylesheet" href="./themes/<?php echo $theme ?>/style.css?<?php echo $VERSION ?>">
<script type="text/javascript" src="./js/global.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/global_chat.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/framework.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/framework_cnt.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/jquery.tools.min.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/jquery_md5.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/autolink.js?<?php echo $VERSION ?>"></script>

<script type="text/javascript">
<!--
	var base_url = "." ; var base_url_full = "<?php echo $CONF["BASE_URL"] ?>" ;
	var isop = 0 ; var isop_ = 11111111111 ; var isop__ = 0 ;
	var cname = "<?php echo $vname ?>" ; var cemail = "<?php echo $vemail ?>" ;
	var ces = "<?php echo $ces ?>" ;
	var st_typing, st_flash_console ;
	var si_timer, si_title, si_typing ;
	var deptid = <?php echo $deptinfo["deptID"] ?> ;
	var temail = <?php echo $deptinfo["temail"] ?> ;
	var rtype = <?php echo $deptinfo["rtype"] ?> ;
	var rtime = <?php echo $deptinfo["rtime"] ?> ;
	var rloop = <?php echo ( $deptinfo["rloop"] ) ? $deptinfo["rloop"] : 1 ; ?> ;
	var cid = "cid_"+unixtime() ;
	var chat_sound = 1 ;
	var title_orig = document.title ;
	var si_counter = 0 ;
	var focused = 1 ;
	var widget = 0 ; var embed = <?php echo $embed ?> ;
	var wp = 0 ;
	var mobile = <?php echo $mobile ?> ;
	var sound_new_text = "default" ;
	var salt = "<?php echo $salt ?>" ;
	var theme = "<?php echo $theme ?>" ;

	var marquees = new Array(), marquees_messages = new Array() ;
	var marquee_index = 0 ;

	var loaded = 0 ;
	var newwin_print ;
	var survey_texts = new Array("<?php echo $LANG["CHAT_SURVEY_THANK"] ?>", "<?php echo $LANG["CHAT_CLOSE"] ?>") ;
	var survey = "<?php echo $survey ?>" ;
	var phplive_browser = navigator.appVersion ; var phplive_mime_types = "" ;
	if ( navigator.mimeTypes.length > 0 ) { for (var x=0; x < navigator.mimeTypes.length; x++) { phplive_mime_types += navigator.mimeTypes[x].description ; } }
	var phplive_browser_token = phplive_md5( phplive_browser+phplive_mime_types ) ;

	var chats = new Object ;
	chats[ces] = new Object ;
	chats[ces]["requestid"] = <?php echo $requestid ?> ;
	chats[ces]["cid"] = cid ;
	chats[ces]["vname"] = cname ;
	chats[ces]["trans"] = "<xo><div class=\"ca\"><?php echo ( $question ) ? "<div class=\'info_box\'><i>$question</i></div>" : "" ; ?><div style=\"margin-top: 10px;\"><?php echo addslashes( $deptinfo["msg_greet"] ) ?><div style=\"margin-top: 10px;\"><img src=\"themes/<?php echo $theme ?>/loading_bar.gif\" border=\"0\" alt=\"\"></div></div></div></xo>".vars() ;
	chats[ces]["status"] = 0 ;
	chats[ces]["disconnected"] = 0 ;
	chats[ces]["tooslow"] = 0 ;
	chats[ces]["op2op"] = 0 ;
	chats[ces]["t_ses"] = <?php echo $vses ?> ;
	chats[ces]["deptid"] = 0 ;
	chats[ces]["opid"] = 0 ;
	chats[ces]["opid_orig"] = 0 ;
	chats[ces]["oname"] = "" ;
	chats[ces]["ip"] = "<?php echo $ip ?>" ;
	chats[ces]["vis_token"] = phplive_browser_token ;
	chats[ces]["chatting"] = 0 ;
	chats[ces]["vsurvey"] = <?php echo $opinfo_next["rate"] ?> ;
	chats[ces]["survey"] = 0 ;
	chats[ces]["timer"] = unixtime() ;
	chats[ces]["istyping"] = 0 ;

	$(document).ready(function()
	{
		$.ajaxSetup({ cache: false }) ;

		<?php echo $marquee_string ?>

		$("body").show() ;
		loaded = 1 ;
		init_divs(0) ;
		init_disconnects() ;
		init_disconnect() ;

		if ( <?php echo $connected ?> )
		{
			chats[ces]["chatting"] = 1 ;
			chats[ces]["trans"] = init_timestamps( "<?php echo $text ?>" ) ;
			$('#chat_body').empty().html( chats[ces]["trans"] ) ;
		}
		else { $('#chat_body').empty().html( chats[ces]["trans"] ) ; }
		init_scrolling() ;
		init_marquees() ;
		init_typing() ;

		if ( typeof( parent.chat_connected ) != "undefined" )
		{
			parent.chat_connected = 1 ;
		}
	});
	$(window).resize(function() {
		if ( !mobile ) { init_divs(1) ; }
	});

	<?php if ( !$embed ): ?>window.onbeforeunload = function() { disconnect() ; }<?php endif ; ?>

	$(window).focus(function() {
		input_focus() ;
	});
	$(window).blur(function() {
		focused = 0 ;
	});

	function init_disconnects()
	{
		// to fix div text not udating if covered by invibile layer image on parent (embed chat)
		var width = $('#info_disconnect').outerWidth() ;
		var width_embed = $('#info_disconnect_embed').outerWidth() ;
		var height = $('#info_disconnect').outerHeight() ;
		var height_embed = $('#info_disconnect_embed').outerHeight() ;

		if ( width_embed > width ) { $('#info_disconnect').css({'width': width_embed}) ; }
		if ( height_embed > height ) { $('#info_disconnect').css({'height': height_embed}) ; }

		$('#info_disconnect').addClass("info_disconnect") ;
		$('#info_disconnect_embed').addClass("info_disconnect") ;
	}

	function init_connect( thejson_data )
	{
		init_connect_doit( thejson_data ) ;
	}

	function init_connect_doit( thejson_data )
	{
		isop_ = thejson_data.opid ;
		chats[ces]["status"] = thejson_data.status_request ;
		chats[ces]["oname"] = thejson_data.name ;
		chats[ces]["opid"] = thejson_data.opid ;
		chats[ces]["deptid"] = thejson_data.deptid ;
		chats[ces]["opid_orig"] = thejson_data.opid ;
		chats[ces]["vsurvey"] = thejson_data.rate ;
		chats[ces]["timer"] = ( chats[ces]["chatting"] ) ? <?php echo $created_embed ?> : unixtime() ;
		chats[ces]["trans"] = chats[ces]["trans"].replace( /<xo>(.*)<\/xo>/, "" ) ;

		$('#chat_body').empty().html( chats[ces]["trans"] ) ;
		$('#chat_vname').empty().html( chats[ces]["oname"] ) ;
		$('textarea#input_text').val( "" ) ;
		init_scrolling() ;
		init_textarea() ;

		$('#options_print').show() ;
		init_timer() ;
	}

	function init_chats()
	{
	}

	function cleanup_disconnect( theces )
	{
		// visitor disconnects
		// - disconnected by operator located at global_chat.js update_ces() through parsing
		if ( !chats[theces]["disconnected"] && chats[theces]["status"] )
		{
			chats[theces]["disconnected"] = unixtime() ;
			var text = "<div class='cl'><?php echo $LANG["CHAT_NOTIFY_VDISCONNECT"] ?></div>" ;
			if ( !chats[theces]["status"] )
			{
				// clear it out so the loading image is not shown
				$('#chat_body').empty().html( "" ) ;
				chats[theces]["trans"] = "" ;
			}

			add_text( text ) ;
			init_textarea() ;
			document.getElementById('iframe_chat_engine').contentWindow.stopit(0) ;

			window.onbeforeunload = null ;
			if ( typeof( parent.chat_disconnected ) != "undefined" )
				parent.chat_disconnected = 1 ;

			if ( chats[theces]["status"] || ( chats[theces]["status"] == 2 ) )
				chat_survey() ;
			else
				leave_a_mesg() ;
		}
	}

	function leave_a_mesg()
	{
		<?php if ( $vsubject ): ?>var vsubject = encodeURIComponent( "<?php echo $vsubject ?>" ) ;<?php else: ?>var vsubject = "" ;<?php endif ; ?>

		window.onbeforeunload = null ;
		var url = base_url_full+"/phplive_m.php?ces=<?php echo $ces ?>&chat=1&deptid=<?php echo $deptid ?>&token="+phplive_browser_token+"&theme=<?php echo $theme ?>&embed=<?php echo $embed ?>&vname=<?php echo $vname ; ?>&vemail=<?php echo $vemail ?>&vsubject="+vsubject+"&vquestion=<?php echo rawurlencode( $question ) ?>&onpage=<?php echo rawurlencode( Util_Format_URL( $onpage ) ) ?>&custom=<?php echo $custom ?>" ;

		if ( embed ) { parent.leave_a_message( url ); }
		else { location.href = url ; }
	}

	function send_email()
	{
		if ( !$('#vemail').val() )
			do_alert( 0, "<?php echo $LANG["CHAT_JS_BLANK_EMAIL"] ?>" ) ;
		else if ( !check_email( $('#vemail').val() ) )
			do_alert( 0, "<?php echo $LANG["CHAT_JS_INVALID_EMAIL"] ?>" ) ;
		else
		{
			$('#btn_email').attr( "disabled", true ) ;
			$('#vemail').attr( "disabled", true ) ;

			var unique = unixtime() ;
			var vname = "<?php echo $vname ?>" ;
			var vemail = $('#vemail').val() ;

			$.ajax({
			type: "POST",
			url: "phplive_m.php",
			data: "&action=send_email_trans&ces=<?php echo $ces ?>&opid="+chats[ces]["opid"]+"&deptid="+chats[ces]["deptid"]+"&token="+phplive_browser_token+"&vname="+vname+"&vemail="+vemail+"&"+unique,
			success: function(data){
				eval( data ) ;

				if ( json_data.status )
				{
					do_alert( 1, "<?php echo $LANG["CHAT_JS_EMAIL_SENT"] ?>" ) ;
				}
				else
				{
					do_alert( 0, json_data.error ) ;
					$('#btn_email').attr( "disabled", false ) ;
				}
			},
			error:function (xhr, ajaxOptions, thrownError){
				
			} });
		}
	}

	function toggle_show_disconnect( theflag )
	{
		if ( theflag ) { $('#info_disconnect').show() ; }
		else { $('#info_disconnect').hide() ; }
	}
//-->
</script>
</head>
<body style="display: none;">

<div id="chat_canvas" style="min-height: 100%; width: 100%;"></div>
<div style="position: absolute; top: 2px; padding: 10px; z-Index: 2;">
	<div id="chat_body" style="overflow: auto;"></div>
	<div id="chat_options" style="padding-top: 10px;">
		<div style="height: 16px;">
			<div id="options_socials" style="float: left; <?php echo ( count( $socials ) ) ? "padding-right: 20px;" : "" ?>"><?php echo $socials_string ?></div>
			<div id="options_print" style="display: none; float: left;">
				<?php if ( !$mobile ): ?>
				<span><img src="./themes/<?php echo $theme ?>/sound_on.png" width="16" height="16" border="0" alt="" onClick="toggle_chat_sound('<?php echo $theme ?>')" id="chat_sound" title="<?php echo $LANG["CHAT_SOUND"] ?>" alt="<?php echo $LANG["CHAT_SOUND"] ?>" style="cursor: pointer;"></span>
				<span style="padding-left: 10px;"><img src="./themes/<?php echo $theme ?>/printer.png" width="16" height="16" border="0" alt="" onClick="do_print(ces, <?php echo $deptinfo["deptID"] ?>, 0, <?php echo $VARS_CHAT_WIDTH ?>, <?php echo $VARS_CHAT_HEIGHT ?>)" title="<?php echo $LANG["CHAT_PRINT"] ?>" alt="<?php echo $LANG["CHAT_PRINT"] ?>" style="cursor: pointer;"></span>
				<?php endif ; ?>
				<span id="chat_vtimer" style="position: relative; top: -2px; padding-left: 15px;"></span>
				<span id="chat_processing" style="padding-left: 15px;"><img src="./pics/space.gif" width="16" height="16" border="0" alt=""></span>
				<span id="chat_vname" style="position: relative; top: -2px; padding-left: 15px;"></span>
				<span id="chat_vistyping" style="position: relative; top: -2px;"></span>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div>
	<div id="chat_input" style="margin-top: 8px;">
		<textarea id="input_text" rows="3" style="padding: 2px; height: 75px; resize: none;" wrap="virtual" onKeyup="input_text_listen(event);" onKeydown="input_text_typing(event);" onFocus="clear_flash_console();" disabled><?php echo $LANG["TXT_CONNECTING"] ?></textarea>
	</div>
</div>

<div id="chat_btn" style="position: absolute; z-Index: 10;">
	<button id="input_btn" type="button" class="input_button" style="<?php echo ( $mobile ) ? "" : "width: 104px; height: 45px; font-size: 14px; font-weight: bold;" ?> padding: 6px;" OnClick="add_text_prepare()" disabled><?php echo $LANG["TXT_SUBMIT"] ?></button>
	<div id="sounds" style="width: 1px; height: 1px; overflow: hidden; opacity:0.0; filter:alpha(opacity=0);"><span id="div_sounds_new_text"></span></div>
</div>

<iframe id="iframe_chat_engine" name="iframe_chat_engine" style="position: absolute; width: 100%; border: 0px; bottom: -50px; height: 20px;" src="ops/p_engine.php?ces=<?php echo $ces ?>" scrolling="no" frameBorder="0"></iframe>

<div id="info_disconnect" style="position: absolute; top: 0px; right: 0px; text-align: center; z-Index: 102;" onClick="disconnect()"><img src="./themes/<?php echo $theme ?>/close_extra.png" width="14" height="14" border="0" alt=""> <span id="info_disconnect_text"><?php echo $LANG["TXT_DISCONNECT"] ?></span></div>

<div id="profile_pic" style="position: absolute; display: none;">
	<div style="background: #FFFFFF; border: 2px solid #446996; width: 100px; height: 100px; -moz-border-radius: 10px; border-radius: 10px;"><img src="themes/winterland/profile_cover.png" width="100" height="100" border="0" alt=""></div>
	<div style="margin-top: 5px; width: 100px;">
		<img src="themes/winterland/email.png" width="16" height="16" border="0" alt="">
	</div>
</div>

<?php if ( !$mobile ): ?>
<div id="chat_footer" style="position: relative; width: 100%; margin-top: -28px; height: 28px; padding-top: 7px; padding-left: 15px; z-Index: 10;"></div>
<?php endif ; ?>

</body>
</html>
<?php
	if ( isset( $dbh ) && isset( $dbh['con'] ) )
		database_mysql_close( $dbh ) ;
?>
