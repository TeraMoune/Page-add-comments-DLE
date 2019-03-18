<?php
if( !defined('DATALIFEENGINE') OR !$config['allow_comments'] ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

$news_id = intval($_REQUEST['newsid']);


$row = $db->super_query("SELECT id, title, xfields FROM `".PREFIX."_post` WHERE id = '{$news_id}'");
		
if( $row ) {

	$xfields = xfieldsload();
			
	$tpl->load_template( 'addcomments.tpl' );

	if( count($xfields) ) {
			
		$xfieldsdata = xfieldsdataload( $row['xfields'] );
			
		foreach ( $xfields as $value ) {
			$preg_safe_name = preg_quote( $value[0], "'" );
				
			if( $value[20] ) {
				  
			  $value[20] = explode( ',', $value[20] );
				  
			  if( $value[20][0] AND !in_array( $member_id['user_group'], $value[20] ) ) {
				$xfieldsdata[$value[0]] = "";
			  }
				  
			}
				
			if ( $value[3] == "yesorno" ) {
					
			    if( intval($xfieldsdata[$value[0]]) ) {
					$xfgiven = true;
					$xfieldsdata[$value[0]] = $lang['xfield_xyes'];
				} else {
					$xfgiven = false;
					$xfieldsdata[$value[0]] = $lang['xfield_xno'];
				}
					
			} else {
					
				if($xfieldsdata[$value[0]] == "") $xfgiven = false; else $xfgiven = true;
					
			}
				
			if( !$xfgiven ) {
				$tpl->copy_template = preg_replace( "'\\[xfgiven_{$preg_safe_name}\\](.*?)\\[/xfgiven_{$preg_safe_name}\\]'is", "", $tpl->copy_template );
				$tpl->copy_template = str_ireplace( "[xfnotgiven_{$value[0]}]", "", $tpl->copy_template );
				$tpl->copy_template = str_ireplace( "[/xfnotgiven_{$value[0]}]", "", $tpl->copy_template );
			} else {
				$tpl->copy_template = preg_replace( "'\\[xfnotgiven_{$preg_safe_name}\\](.*?)\\[/xfnotgiven_{$preg_safe_name}\\]'is", "", $tpl->copy_template );
				$tpl->copy_template = str_ireplace( "[xfgiven_{$value[0]}]", "", $tpl->copy_template );
				$tpl->copy_template = str_ireplace( "[/xfgiven_{$value[0]}]", "", $tpl->copy_template );
			}
				
			if(strpos( $tpl->copy_template, "[ifxfvalue" ) !== false ) {
				$tpl->copy_template = preg_replace_callback ( "#\\[ifxfvalue(.+?)\\](.+?)\\[/ifxfvalue\\]#is", "check_xfvalue", $tpl->copy_template );
			}
				
			if ( $value[6] AND !empty( $xfieldsdata[$value[0]] ) ) {
				$temp_array = explode( ",", $xfieldsdata[$value[0]] );
				$value3 = array();

				foreach ($temp_array as $value2) {

					$value2 = trim($value2);
					$value2 = str_replace("&#039;", "'", $value2);

					if( $config['allow_alt_url'] ) $value3[] = "<a href=\"" . $config['http_home_url'] . "xfsearch/" .$value[0]."/". urlencode( $value2 ) . "/\">" . $value2 . "</a>";
					else $value3[] = "<a href=\"$PHP_SELF?do=xfsearch&amp;xfname=".$value[0]."&amp;xf=" . urlencode( $value2 ) . "\">" . $value2 . "</a>";
				}
					
				if( empty($value[21]) ) $value[21] = ", ";
					
				$xfieldsdata[$value[0]] = implode($value[21], $value3);

				unset($temp_array);
				unset($value2);
				unset($value3);

			}
				
			if ($config['allow_links'] AND $value[3] == "textarea" AND function_exists('replace_links')) $xfieldsdata[$value[0]] = replace_links ( $xfieldsdata[$value[0]], $replace_links['news'] );

			if($value[3] == "image" AND $xfieldsdata[$value[0]] ) {
				$path_parts = @pathinfo($xfieldsdata[$value[0]]);
		
				if( $value[12] AND file_exists(ROOT_DIR . "/uploads/posts/" .$path_parts['dirname']."/thumbs/".$path_parts['basename']) ) {
					$thumb_url = $config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/thumbs/".$path_parts['basename'];
					$img_url = $config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/".$path_parts['basename'];
				} else {
					$img_url = 	$config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/".$path_parts['basename'];
					$thumb_url = "";
				}
					
				if($thumb_url) {
					$xfieldsdata[$value[0]] = "<a href=\"$img_url\" class=\"highslide\" target=\"_blank\"><img class=\"xfieldimage {$value[0]}\" src=\"$thumb_url\" alt=\"\"></a>";
				} else $xfieldsdata[$value[0]] = "<img class=\"xfieldimage {$value[0]}\" src=\"{$img_url}\" alt=\"\">";
			}
				
			if($value[3] == "image") {

				if( $xfieldsdata[$value[0]] ) {
					$tpl->set( "[xfvalue_thumb_url_{$value[0]}]", $thumb_url);
					$tpl->set( "[xfvalue_image_url_{$value[0]}]", $img_url);
				} else {
					$tpl->set( "[xfvalue_thumb_url_{$value[0]}]", "");
					$tpl->set( "[xfvalue_image_url_{$value[0]}]", "");
				}
			}
				
			if($value[3] == "imagegalery" AND $xfieldsdata[$value[0]] AND stripos ( $tpl->copy_template, "[xfvalue_{$value[0]}" ) !== false) {
					
				$fieldvalue_arr = explode(',', $xfieldsdata[$value[0]]);
				$gallery_image = array();
				$gallery_single_image = array();
				$xf_image_count = 0;
				$single_need = false;
	
				if(stripos ( $tpl->copy_template, "[xfvalue_{$value[0]} image=" ) !== false) $single_need = true;
					
				foreach ($fieldvalue_arr as $temp_value) {
					$xf_image_count ++;
						
					$temp_value = trim($temp_value);
				
					if($temp_value == "") continue;

					$path_parts = @pathinfo($temp_value);
						
					if( $value[12] AND file_exists(ROOT_DIR . "/uploads/posts/" .$path_parts['dirname']."/thumbs/".$path_parts['basename']) ) {
						$thumb_url = $config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/thumbs/".$path_parts['basename'];
						$img_url = $config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/".$path_parts['basename'];
					} else {
						$img_url = 	$config['http_home_url'] . "uploads/posts/" . $path_parts['dirname']."/".$path_parts['basename'];
						$thumb_url = "";
					}
						
					if($thumb_url) {
							
						$gallery_image[] = "<li><a href=\"$img_url\" onclick=\"return hs.expand(this, { slideshowGroup: 'xf_{$row['id']}_{$value[0]}' })\" target=\"_blank\"><img src=\"{$thumb_url}\" alt=\"\"></a></li>";
						$gallery_single_image['[xfvalue_'.$value[0].' image="'.$xf_image_count.'"]'] = "<a href=\"{$img_url}\" class=\"highslide\" target=\"_blank\"><img class=\"xfieldimage {$value[0]}\" src=\"{$thumb_url}\" alt=\"\"></a>";

					} else {
						$gallery_image[] = "<li><img src=\"{$img_url}\" alt=\"\"></li>";
						$gallery_single_image['[xfvalue_'.$value[0].' image="'.$xf_image_count.'"]'] = "<img class=\"xfieldimage {$value[0]}\" src=\"{$img_url}\" alt=\"\">";
					}
					
				}
					
				if($single_need AND count($gallery_single_image) ) {
					foreach($gallery_single_image as $temp_key => $temp_value) $tpl->set( $temp_key, $temp_value);
				}
					
				$xfieldsdata[$value[0]] = "<ul class=\"xfieldimagegallery {$value[0]}\">".implode($gallery_image)."</ul>";
					
			}

			$tpl->set( "[xfvalue_{$value[0]}]", $xfieldsdata[$value[0]] );

			if ( preg_match( "#\\[xfvalue_{$preg_safe_name} limit=['\"](.+?)['\"]\\]#i", $tpl->copy_template, $matches ) ) {
				$count= intval($matches[1]);
	
				$xfieldsdata[$value[0]] = str_replace( "><", "> <", $xfieldsdata[$value[0]] );
				$xfieldsdata[$value[0]] = strip_tags( $xfieldsdata[$value[0]], "<br>" );
				$xfieldsdata[$value[0]] = trim(str_replace( "<br>", " ", str_replace( "<br />", " ", str_replace( "\n", " ", str_replace( "\r", "", $xfieldsdata[$value[0]] ) ) ) ));
				$xfieldsdata[$value[0]] = preg_replace('/\s+/', ' ', $xfieldsdata[$value[0]]);
					
				if( $count AND dle_strlen( $xfieldsdata[$value[0]], $config['charset'] ) > $count ) {
							
					$xfieldsdata[$value[0]] = dle_substr( $xfieldsdata[$value[0]], 0, $count, $config['charset'] );
							
					if( ($temp_dmax = dle_strrpos( $xfieldsdata[$value[0]], ' ', $config['charset'] )) ) $xfieldsdata[$value[0]] = dle_substr( $xfieldsdata[$value[0]], 0, $temp_dmax, $config['charset'] );
						
				}
		
				$tpl->set( $matches[0], $xfieldsdata[$value[0]] );
		
			}
		}
	}
	
	if ($config['allow_subscribe'] AND $is_logged AND $user_group[$member_id['user_group']]['allow_subscribe']) $allow_subscribe = true; else $allow_subscribe = false;
		
	if( strpos( $tpl->copy_template, "[catlist=" ) !== false ) {
		$tpl->copy_template = preg_replace_callback ( "#\\[(catlist)=(.+?)\\](.*?)\\[/catlist\\]#is", "check_category", $tpl->copy_template );
	}
								
	if( strpos( $tpl->copy_template, "[not-catlist=" ) !== false ) {
		$tpl->copy_template = preg_replace_callback ( "#\\[(not-catlist)=(.+?)\\](.*?)\\[/not-catlist\\]#is", "check_category", $tpl->copy_template );
	}
		
	$text='';
		
	if( $config['allow_comments_wysiwyg'] > 0 ) {
			
		$p_name = urlencode($member_id['name']);
		$p_id = 0;
		include_once (DLEPlugins::Check(ENGINE_DIR . '/editor/comments.php'));
		$bb_code = "";
		$allow_comments_ajax = true;
			
	} else {
			
		include_once (DLEPlugins::Check(ENGINE_DIR . '/modules/bbcode.php'));
			
	}

	if ( $is_logged AND $user_group[$member_id['user_group']]['disable_comments_captcha'] AND $member_id['comm_num'] >= $user_group[$member_id['user_group']]['disable_comments_captcha'] ) {
		
		$user_group[$member_id['user_group']]['comments_question'] = false;
		$user_group[$member_id['user_group']]['captcha'] = false;
		
	}

	if( $user_group[$member_id['user_group']]['comments_question'] ) {

		$tpl->set( '[question]', "" );
		$tpl->set( '[/question]', "" );

		$question = $db->super_query("SELECT id, question FROM " . PREFIX . "_question ORDER BY RAND() LIMIT 1");
		$tpl->set( '{question}', "<span id=\"dle-question\">".htmlspecialchars( stripslashes( $question['question'] ), ENT_QUOTES, $config['charset'] )."</span>" );

		$_SESSION['question'] = $question['id'];

	} else {

		$tpl->set_block( "'\\[question\\](.*?)\\[/question\\]'si", "" );
		$tpl->set( '{question}', "" );

	}
		
	if( $user_group[$member_id['user_group']]['captcha'] ) {

		if ( $config['allow_recaptcha'] ) {

			$tpl->set( '[recaptcha]', "" );
			$tpl->set( '[/recaptcha]', "" );

			$tpl->set( '{recaptcha}', "<div class=\"g-recaptcha\" data-sitekey=\"{$config['recaptcha_public_key']}\" data-theme=\"{$config['recaptcha_theme']}\"></div>" );

			$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
			$tpl->set( '{reg_code}', "" );

		} else {
				
			$tpl->set( '[sec_code]', "" );
			$tpl->set( '[/sec_code]', "" );
			$path = parse_url( $config['http_home_url'] );
			$tpl->set( '{sec_code}', "<a onclick=\"reload(); return false;\" title=\"{$lang['reload_code']}\" href=\"#\"><span id=\"dle-captcha\"><img src=\"" . $path['path'] . "engine/modules/antibot/antibot.php\" alt=\"{$lang['reload_code']}\" width=\"160\" height=\"80\"></span></a>" );
			$tpl->set_block( "'\\[recaptcha\\](.*?)\\[/recaptcha\\]'si", "" );
			$tpl->set( '{recaptcha}', "" );
		}

	} else {
		$tpl->set( '{sec_code}', "" );
		$tpl->set( '{recaptcha}', "" );
		$tpl->set_block( "'\\[recaptcha\\](.*?)\\[/recaptcha\\]'si", "" );
		$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
	}

	if( $config['allow_comments_wysiwyg'] > 0 ) {

		$tpl->set( '{editor}', $wysiwyg );

	} else {

		$tpl->set( '{editor}', $bb_code );

	}
		
	$tpl->set( '{title}', 'Добавления комментария к новости: ' . $row['title'] );

	if( $vk_url ) {
		$tpl->set( '[vk]', "" );
		$tpl->set( '[/vk]', "" );
		$tpl->set( '{vk_url}', $vk_url );	
	} else {
		$tpl->set_block( "'\\[vk\\](.*?)\\[/vk\\]'si", "" );
		$tpl->set( '{vk_url}', '' );	
	}
	if( $odnoklassniki_url ) {
		$tpl->set( '[odnoklassniki]', "" );
		$tpl->set( '[/odnoklassniki]', "" );
		$tpl->set( '{odnoklassniki_url}', $odnoklassniki_url );
	} else {
		$tpl->set_block( "'\\[odnoklassniki\\](.*?)\\[/odnoklassniki\\]'si", "" );
		$tpl->set( '{odnoklassniki_url}', '' );	
	}
	if( $facebook_url ) {
		$tpl->set( '[facebook]', "" );
		$tpl->set( '[/facebook]', "" );
		$tpl->set( '{facebook_url}', $facebook_url );	
	} else {
		$tpl->set_block( "'\\[facebook\\](.*?)\\[/facebook\\]'si", "" );
		$tpl->set( '{facebook_url}', '' );	
	}
	if( $google_url ) {
		$tpl->set( '[google]', "" );
		$tpl->set( '[/google]', "" );
		$tpl->set( '{google_url}', $google_url );
	} else {
		$tpl->set_block( "'\\[google\\](.*?)\\[/google\\]'si", "" );
		$tpl->set( '{google_url}', '' );	
	}
	if( $mailru_url ) {
		$tpl->set( '[mailru]', "" );
		$tpl->set( '[/mailru]', "" );
		$tpl->set( '{mailru_url}', $mailru_url );	
	} else {
		$tpl->set_block( "'\\[mailru\\](.*?)\\[/mailru\\]'si", "" );
		$tpl->set( '{mailru_url}', '' );	
	}
	if( $yandex_url ) {
		$tpl->set( '[yandex]', "" );
		$tpl->set( '[/yandex]', "" );
		$tpl->set( '{yandex_url}', $yandex_url );
	} else {
		$tpl->set_block( "'\\[yandex\\](.*?)\\[/yandex\\]'si", "" );
		$tpl->set( '{yandex_url}', '' );
	}
		
	if ( $allow_subscribe ) {
		$tpl->set( '[comments-subscribe]', "<a href=\"#\" onclick=\"subscribe('{$news_id}'); return false;\" >" );
		$tpl->set( '[/comments-subscribe]', '</a>' );
	} else {
		$tpl->set_block( "'\\[comments-subscribe\\](.*?)\\[/comments-subscribe\\]'si", "" );
	}
		
	if( ! $is_logged ) {
		$tpl->set( '[not-logged]', '' );
		$tpl->set( '[/not-logged]', '' );
	} else $tpl->set_block( "'\\[not-logged\\](.*?)\\[/not-logged\\]'si", "" );
		
	if( $is_logged ) $hidden = "<input type=\"hidden\" name=\"name\" id=\"name\" value=\"{$member_id['name']}\"><input type=\"hidden\" name=\"mail\" id=\"mail\" value=\"\">";
	else $hidden = "";
		
	$tpl->copy_template = "<form  method=\"post\" name=\"dle-comments-form\" id=\"dle-comments-form\" >" . $tpl->copy_template . "
	<input type=\"hidden\" name=\"subaction\" value=\"addcomment\">{$hidden}
	<input type=\"hidden\" name=\"post_id\" id=\"post_id\" value=\"{$news_id}\"><input type=\"hidden\" name=\"user_hash\" value=\"{$dle_login_hash}\"></form>";

	$ajax .= <<<HTML
<script>
function doAddComments2(){

	var form = document.getElementById('dle-comments-form');
	var editor_mode = '';
	var question_answer = '';
	var sec_code = '';
	var g_recaptcha_response= '';
	var allow_subscribe= "0";
	var mail = '';
	
	if (dle_wysiwyg == "1" || dle_wysiwyg == "2") {

		if (dle_wysiwyg == "2") {
			tinyMCE.triggerSave();
		}

		editor_mode = 'wysiwyg';

	}

	if (form.comments.value == '' || form.name.value == '')
	{
		DLEalert ( dle_req_field, dle_info );
		return false;
	}

	if ( form.question_answer ) {

	   question_answer = form.question_answer.value;

    }

	if ( form.sec_code ) {

	   sec_code = form.sec_code.value;

    }

	if ( typeof grecaptcha != "undefined"  ) {
	   g_recaptcha_response = grecaptcha.getResponse();
    }

	if ( form.allow_subscribe ) {

		if ( form.allow_subscribe.checked == true ) {
	
		   allow_subscribe= "1";

		}

    }

	if ( form.mail ) {

	   mail = form.mail.value;

    }

	ShowLoading('');

	$.post(dle_root + "engine/ajax/controller.php?mod=addcomments", { post_id: form.post_id.value, comments: form.comments.value, name: form.name.value, mail: mail, editor_mode: editor_mode, skin: dle_skin, sec_code: sec_code, question_answer: question_answer, g_recaptcha_response: g_recaptcha_response, allow_subscribe: allow_subscribe, user_hash: dle_login_hash, redirect_idcomm:1}, function(data){

		HideLoading('');

		window.location.href = dle_root + "index.php?newsid=" + form.post_id.value + "#" + data;

	});
	
	return false;

};
</script>
HTML;
		
	$onload_scripts[] = <<<HTML
$('#dle-comments-form').submit(function() {
	doAddComments2();
	return false;
});
HTML;


	if ( $user_group[$member_id['user_group']]['captcha'] AND $config['allow_recaptcha'] ) {

	$tpl->copy_template .= <<<HTML
<script src='https://www.google.com/recaptcha/api.js?hl={$lang['wysiwyg_language']}' async defer></script>
HTML;
		
	}
		
	$tpl->compile( 'content' );
	$tpl->clear();

		
} else msgbox( $lang['all_err_1'], "Статьи не существует.<br /><br /><a href=\"javascript:history.go(-1)\">" . $lang['all_prev'] . "</a>" );
?>
