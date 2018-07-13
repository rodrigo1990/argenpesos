<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	if ( !is_file( "./web/config.php" ) ){ HEADER("location: ./setup/install.php") ; exit ; }
	include_once( "./web/config.php" ) ;

	if ( !isset( $CONF['SQLTYPE'] ) ) { $CONF['SQLTYPE'] = "SQL.php" ; }
	else if ( $CONF['SQLTYPE'] == "mysql" ) { $CONF['SQLTYPE'] = "SQL.php" ; }

	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Error.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Vals.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/".Util_Format_Sanatize($CONF["SQLTYPE"], "ln") ) ;

	$from = Util_Format_Sanatize( Util_Format_GetVar( "from" ), "ln" ) ;
	$patch = Util_Format_Sanatize( Util_Format_GetVar( "patch" ), "ln" ) ;
	$patch_c = Util_Format_Sanatize( Util_Format_GetVar( "patch_c" ), "ln" ) ;
	$patched = 0 ;
	$loopy = Util_Format_Sanatize( Util_Format_GetVar( "loopy" ), "ln" ) ;

	$query = isset( $_SERVER["QUERY_STRING"] ) ? $_SERVER["QUERY_STRING"] : "" ;

	// basic check for permissions
	if ( !is_file( "$CONF[DOCUMENT_ROOT]/blank.php" ) )
		ErrorHandler( 612, "\$CONF[DOCUMENT_ROOT] variable in config.php is invalid.", $PHPLIVE_FULLURL, 0, Array() ) ;
	else if ( !is_writeable( "$CONF[CONF_ROOT]/" ) )
		ErrorHandler( 609, "Permission denied on web/ directory.", $PHPLIVE_FULLURL, 0, Array() ) ;
	else if ( !is_writeable( "$CONF[CONF_ROOT]/config.php" ) )
		ErrorHandler( 609, "Permission denied on web/config.php directory.", $PHPLIVE_FULLURL, 0, Array() ) ;
	else if ( !is_writeable( "$CONF[CONF_ROOT]/patches/" ) )
		ErrorHandler( 609, "Permission denied on web/patches/ directory.", $PHPLIVE_FULLURL, 0, Array() ) ;
	else if ( !is_writeable( $CONF["CHAT_IO_DIR"] ) )
		ErrorHandler( 609, "Permission denied on web/chat_sessions directory.", $PHPLIVE_FULLURL, 0, Array() ) ;
	else if ( !is_writeable( $CONF["TYPE_IO_DIR"] ) )
		ErrorHandler( 609, "Permission denied on web/chat_initiate directory.", $PHPLIVE_FULLURL, 0, Array() ) ;

	if ( $from == "chat" )
		$url = "phplive.php?patched=1&".$query ;
	else if ( $from == "embed" )
		$url = "phplive_embed.php?patched=1&".$query ;
	else if ( $from == "setup" )
		$url = "setup/?patched=1&".$query ;
	else
		$url = "index.php?patched=1&".$query ;

	if ( $patch )
	{
		if ( !is_file( "$CONF[CONF_ROOT]/patches/$patch_v" ) )
		{
			if ( $patch_c <= 51 ) { include_once( "$CONF[DOCUMENT_ROOT]/API/Patches/Util_Patches_1.php" ) ; }
			else if ( $patch_c <= 85 ) { include_once( "$CONF[DOCUMENT_ROOT]/API/Patches/Util_Patches_2.php" ) ; }
			else { include_once( "$CONF[DOCUMENT_ROOT]/API/Patches/Util_Patches_3.php" ) ; }
			$json_data = "json_data = { \"status\": 0, \"patch_c\": $patched };" ;
		}
		else { $json_data = "json_data = { \"status\": 1 };" ; }

		if ( isset( $dbh ) && isset( $dbh['con'] ) ) { database_mysql_close( $dbh ) ; }
		print "$json_data" ;
		exit ;
	}

?>
<?php include_once( "./inc_doctype.php" ) ?>
<head>
<title> PHP Live! Support <?php echo $VERSION ?> </title>

<meta name="description" content="PHP Live! Support <?php echo $VERSION ?>">
<meta name="keywords" content="powered by: PHP Live!  www.phplivesupport.com">
<meta name="robots" content="all,index,follow">
<meta http-equiv="content-type" content="text/html; CHARSET=utf-8">
<?php include_once( "./inc_meta_dev.php" ) ; ?>

<link rel="Stylesheet" href="./css/setup.css?<?php echo $VERSION ?>">
<script type="text/javascript" src="./js/global.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/framework.js?<?php echo $VERSION ?>"></script>
<script type="text/javascript" src="./js/framework_cnt.js?<?php echo $VERSION ?>"></script>

<script type="text/javascript">
<!--
	var patch_c = 0 ;

	$(document).ready(function()
	{
		$("body").css({'background': '#F2F2F2'}) ;
		auto_patch() ;
	});

	function auto_patch()
	{
		var json_data = new Object ;
		var unique = unixtime() ;

		$('#loading').fadeTo("fast", .5) ;
		$.ajax({
		type: "POST",
		url: "./patch.php",
		data: "patch=1&patch_c="+patch_c+"unique="+unique,
		success: function(data){
			try {
				eval(data) ;
			} catch(err) {
				alert( err ) ;
				return false ;
			}

			patch_c = json_data.patch_c ;

			if ( json_data.status )
			{
				location.href = "<?php echo $url ?>" ;
			}
			else
			{
				var percent = Math.round( ( patch_c/<?php echo $patch_v ?> )*100 ) ;
				$('#status').html( percent ) ; $('#loading').fadeTo("fast", 1) ;
				setTimeout( function(){ patch_c += 1 ; auto_patch() ; }, 500 ) ;
			}
		},
		error:function (xhr, ajaxOptions, thrownError){
			do_alert( 0, "Error patch "+patch_c+" process.  Please reload the page and try again." ) ;
		} });
	}
//-->
</script>
</head>
<body style="overflow: hidden;">

<div style="width: 400px; margin: 0 auto; text-align: center; margin-top: 15px; background: #FFFFFF;" class="info_info">
	<div style="padding: 25px; text-shadow: none;" class="round">
		<div id="loading"><img src="pics/loading_patch.gif" width="44" height="44" border="0" alt="" style="background: #FFFFFF; -moz-border-radius: 5px; border-radius: 5px; padding: 2px;"></div>
		<div style="margin-top: 15px;">Configuring the system.  Just a moment... [<span id="status"></span>%]</div>
	</div>
</div>

<!-- [winapp=4] -->

</body>
</html>
<?php
	if ( isset( $dbh ) && isset( $dbh['con'] ) )
		database_mysql_close( $dbh ) ;
?>
