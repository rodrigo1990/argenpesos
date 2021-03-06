<?php
	$NO_CACHE = 1 ; include_once( "../inc_cache.php" ) ;
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	/****************************************/
	// STANDARD header for Setup
	include_once( "../web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Security.php" ) ;
	$ses = Util_Format_Sanatize( Util_Format_GetVar( "ses" ), "ln" ) ;
	if ( !$opinfo = Util_Security_AuthOp( $dbh, $ses ) ){ ErrorHandler( 602, "Invalid operator session or session has expired.", $PHPLIVE_FULLURL, 0, Array() ) ; }
	// STANDARD header end
	/****************************************/

	include_once( "$CONF[DOCUMENT_ROOT]/API/Depts/get.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Canned/get.php" ) ;

	$action = Util_Format_Sanatize( Util_Format_GetVar( "action" ), "ln" ) ;

	$error = "" ;
?>
<?php include_once( "../inc_doctype.php" ) ?>
<head>
<title> operators </title>

<meta name="description" content="v.<?php echo $VERSION ?>">
<meta name="keywords" content="<?php echo md5( $KEY ) ?>">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=utf-8">
<?php include_once( "../inc_meta_dev.php" ) ; ?>

<link rel="Stylesheet" href="../themes/<?php echo $opinfo["theme"] ?>/style.css?<?php echo $VERSION ?>">
<script type="text/javascript" src="../js/global.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="../js/framework.js?<?php echo $VERSION ?>"></script>

<script type="text/javascript">
<!--
	var st_op2op ;

	$(document).ready(function()
	{
		populate_ops_op2op() ;
		st_op2op = setInterval(function(){ populate_ops_op2op() ; }, 15000) ;

		$('#canned_wrapper').fadeIn() ;

		//$(document).dblclick(function() {
		//	parent.close_extra( "op2op" ) ;
		//});
	});

	function populate_ops_op2op()
	{
		var unique = unixtime() ;

		$.ajax({
		type: "POST",
		url: "../ajax/chat_actions_op_deptops.php",
		data: "action=deptops&unique="+unique,
		success: function(data){
			eval( data ) ;

			if ( json_data.status )
			{
				var ops_string = "" ;
				for ( c = 0; c < json_data.departments.length; ++c )
				{
					ops_string += "<div class=\"chat_info_td_t\" style=\"margin-bottom: 1px;\">"+json_data.departments[c]["name"]+"</div>" ;
					for ( c2 = 0; c2 < json_data.departments[c].operators.length; ++c2 )
					{
						var id, btn_id ;
						var status = "offline" ;
						var status_js = "JavaScript:void(0)" ;
						var status_bullet = "online_grey.png" ;
						var td_div = "chat_info_td_blank" ;
						var button = "" ;

						if ( json_data.departments[c].operators[c2]["status"] )
						{
							id = "op2op_"+json_data.departments[c].operators[c2]["opid"] ;
							btn_id = "btn_"+json_data.departments[c]["deptid"]+"_"+json_data.departments[c].operators[c2]["opid"] ;
							status = "online" ;
							status_js = "request_op2op("+json_data.departments[c]["deptid"]+","+json_data.departments[c].operators[c2]["opid"]+")" ;
							button = "<button type=\"button\" id=\""+btn_id+"\" class=\"input_button\" onClick=\""+status_js+"\">request chat</button> " ;

							status_bullet= "online_green.png" ;
						}

						if ( json_data.departments[c].operators[c2]["opid"] == parent.isop )
							ops_string += "<div class=\""+td_div+"\"><span class=\"chat_info_td_traffic\" style=\"padding-left: 15px;\"><img src=\"../themes/<?php echo $opinfo["theme"] ?>/"+status_bullet+"\" width=\"12\" height=\"12\" border=\"0\"> <span id=\""+id+"\"><b>(You)</b> are "+status+" chatting with "+json_data.departments[c].operators[c2]["requests"]+" visitors</span></span></div>" ;
						else
							ops_string += "<div class=\""+td_div+"\"><span class=\"chat_info_td_traffic\" style=\"padding-left: 15px;\"><img src=\"../themes/<?php echo $opinfo["theme"] ?>/"+status_bullet+"\" width=\"12\" height=\"12\" border=\"0\"> "+button+"<span id=\""+id+"\">"+json_data.departments[c].operators[c2]["name"]+" is "+status+" chatting with "+json_data.departments[c].operators[c2]["requests"]+" visitors</span></span></div>" ;
					}
				}
				$('#canned_body').html( ops_string ) ;
			}
		},
		error:function (xhr, ajaxOptions, thrownError){
			do_alert( 0, "Error retrieving operator list.  Please reload the console and try again." ) ;
		} });
	}

	function request_op2op( thedeptid, theopid )
	{
		$('#btn_'+thedeptid+"_"+theopid).html( "requesting..." ).attr("disabled", "true") ;
		request_op2op_doit( thedeptid, theopid ) ;
	}

	function request_op2op_doit( thedeptid, theopid )
	{
		var win_width = screen.width ;
		var win_height = screen.height ;
		var win_dim = win_width + " x " + win_height ;
		var unique = unixtime() ;

		$.ajax({
		type: "POST",
		url: "../ajax/chat_actions_op_op2op.php",
		data: "action=op2op&deptid="+thedeptid+"&opid="+theopid+"&resolution="+win_dim+"&unique="+unique,
		success: function(data){
			eval( data ) ;

			if ( json_data.status ) { setTimeout( function(){ parent.input_focus() ; parent.close_extra( parent.extra ) ; }, 5000 ) ; }
			else { do_alert( 0, "Error requesting operator chat.  Please reload the console and try again." ) ; }
		},
		error:function (xhr, ajaxOptions, thrownError){
			do_alert( 0, "Error requesting operator chat.  Please reload the console and try again." ) ;
		} });
	}

//-->
</script>
</head>
<body>

<div id="canned_wrapper" style="display: none; height: 100%; overflow: auto;">
	<table cellspacing=0 cellpadding=0 border=0 width="100%"><tr><td class="t_tl"></td><td class="t_tm"></td><td class="t_tr"></td></tr>
	<tr>
		<td class="t_ml"></td><td class="t_mm">
			<table cellspacing=0 cellpadding=0 border=0 width="100%">
			<tr>
				<td width="50%"><div id="canned_body"></div></td>
				<td width="50%">&nbsp;</td>
			</tr>
			</table>
		</td><td class="t_mr"></td>
	</tr>
	<tr><td class="t_bl"></td><td class="t_bm"></td><td class="t_br"></td></tr>
	</table>
</div>

</body>
</html>
<?php database_mysql_close( $dbh ) ; ?>