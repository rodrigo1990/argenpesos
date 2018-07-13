<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	include_once( "../web/config.php" ) ;
	include_once( "$CONF[DOCUMENT_ROOT]/API/Util_Format.php" ) ;

	$isop = Util_Format_Sanatize( Util_Format_GetVar( "isop" ), "n" ) ;
	$isop_ = Util_Format_Sanatize( Util_Format_GetVar( "isop_" ), "n" ) ;
	$isop__ = Util_Format_Sanatize( Util_Format_GetVar( "isop__" ), "n" ) ;
	$op2op = Util_Format_Sanatize( Util_Format_GetVar( "op2op" ), "n" ) ;
	$requestid = Util_Format_Sanatize( Util_Format_GetVar( "requestid" ), "ln" ) ;
	$ces = Util_Format_Sanatize( Util_Format_GetVar( "ces" ), "ln" ) ;
	$salt = Util_Format_Sanatize( Util_Format_GetVar( "salt" ), "ln" ) ;
	$text = preg_replace( "/(p_br)/", "<br>", Util_Format_Sanatize( Util_Format_GetVar( "text" ), "" ) ) ;
	$t_vses = Util_Format_Sanatize( Util_Format_GetVar( "t_vses" ), "n" ) ;

	if ( ( md5( $CONF["SALT"] ) == $salt ) || isset( $_COOKIE["phplive_opID"] ) )
	{
		include_once( "$CONF[DOCUMENT_ROOT]/API/Chat/Util.php" ) ;

		// override javascript timestamp
		$now = time() ;
		$text = preg_replace( "/<timestamp_(\d+)_((co)|(cv))>/", "<timestamp_".$now."_$2>", $text ) ;

		if ( ( $isop && $isop_ ) && ( $isop == $isop_ ) ) { $wid = $isop_ ; }
		else if ( $isop && $isop_ ) { $wid = $isop__ ; }
		else { $wid = $isop_ ; }

		UtilChat_AppendToChatfile( "$ces.txt", $text ) ;
		if ( $isop )
		{
			if ( $op2op )
			{
				$filename = $ces."-".$wid ;
				UtilChat_AppendToChatfile( "$filename.text", $text ) ;
			}
			else
			{
				$max_vses = ( $t_vses > $VARS_MAX_EMBED_SESSIONS ) ? $VARS_MAX_EMBED_SESSIONS : $t_vses ;
				for ( $c = 1; $c <= $max_vses; ++$c )
				{
					$filename = $ces."-".$wid."_".$c ;
					UtilChat_AppendToChatfile( "$filename.text", $text ) ;
				}
			}
		}
		else
		{
			$filename = $ces."-".$wid ;
			UtilChat_AppendToChatfile( "$filename.text", $text ) ;
		}
		UtilChat_WriteIsWriting( $ces, 0, $isop, $isop_, $isop__ ) ;
		$json_data = "json_data = { \"status\": 1 };" ;
	}
	else
		$json_data = "json_data = { \"status\": -1 };" ;
	
	$json_data = preg_replace( "/\r\n/", "", $json_data ) ;
	$json_data = preg_replace( "/\t/", "", $json_data ) ;
	print "$json_data" ;
	exit ;
?>