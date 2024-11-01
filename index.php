<?php
	/*
	 * Plugin Name: WebP Express Plus
	 * Description: Excludes the necessary images and folders with graphics from processing using the "WebP Express" plugin. Works ONLY in combination with "WebP Express" by Bjørn Rosell. Manage in "Settings" -> "WebP Express Plus".
	 * Version: 0.2.1
	 * Author: WP01
	 * Author URI: https://wp01.ru
	 * Text Domain: webp-express-plus
	 * Domain Path: /languages
	*/


    load_plugin_textdomain('webp-express-plus', false, dirname(plugin_basename(__FILE__)) . '/languages/');

	
	if(!class_exists('simple_html_dom_node')){
		require_once __DIR__ . '/simple_html_dom/simple_html_dom.php';
	}


    add_action('admin_menu', function(){
        add_options_page('Webp Express Plus', 'Webp Express Plus', 'manage_options', 'webp-express-plus', function(){
        ?>
            <div class="wrap">
                <h1><?php _e('WebP Express Plus settings', 'webp-express-plus') ?></h1>

                <form method="post" action="options.php">
                    <?php
                        settings_fields('webp_express_plus_settings');

                        do_settings_sections('webp-express-plus');

                        submit_button();
                    ?>
				</form>
				<br>
				<strong><?php _e('Information', 'webp-express-plus') ?></strong><br>
				<?php _e('1. Specify all photos and/or folders that should not be converted to WebP format using the WebP Express plugin', 'webp-express-plus');?>
                <br>
                <?php _e('2. New line = new path to the photo and / or photo folder.', 'webp-express-plus');?>
                <br>
                <?php _e('3. You must specify the path to the original jpg / png files.', 'webp-express-plus');?>
                <br>
                <?php _e('4. The indication must start with "/", for example: /wp-content/uploads/2021/05/pic1.jpg', 'webp-express-plus');?>
                <br>
                <?php _e('5. The path to the graphics folder must end with "/", for example: /wp-content/uploads/2021/05/', 'webp-express-plus');?>
                <br>
                <?php _e('6. You can check the work of WebP Express Plus through the developer console in the browser (Ctrl+Shift+I -> Network -> Img).', 'webp-express-plus');?>
                <br><br>

            </div>

            <style type="text/css">
                #webp_express_plus_rules{
                    width:500px;
                    height:200px;
                }
            </style>
        <?php
        });
    });

    add_action('admin_init', function(){
        register_setting('webp_express_plus_settings', 'webp_express_plus_rules');

        add_settings_section('webp_express_plus_settings_section', '', '', 'webp-express-plus');

        add_settings_field('webp_express_plus_rules', __('Photos and/or folders', 'webp-express-plus') . ':', function(){
            $rules = get_option('webp_express_plus_rules');
        
            printf('<textarea id="webp_express_plus_rules" name="webp_express_plus_rules">%s</textarea>', esc_attr($rules));
        }, 'webp-express-plus', 'webp_express_plus_settings_section', array( 
            'label_for' => 'homepage_text'
        ));
    });

    add_action('update_option_webp_express_plus_rules', function(){
        $rules = get_option('webp_express_plus_rules');

        if(!empty($rules)){
            $rules = array_map('trim', explode(PHP_EOL, $rules));

            $content = '# BEGIN MY WebP Express Plus' . PHP_EOL;

            foreach($rules as $rule){
                $content .= 'RewriteCond %{REQUEST_URI} ^' . $rule . PHP_EOL;
            }
            
            $content .= 'RewriteCond %{REQUEST_FILENAME} -f' . PHP_EOL;
            $content .= 'RewriteRule . - [L]' . PHP_EOL;

            $content .= '# END MY WebP Express Plus';
        }else{
            $content = '';
        }


        $files = array('.htaccess', 'wp-content/.htaccess', 'wp-content/plugins/.htaccess', 'wp-content/themes/.htaccess', 'wp-content/uploads/.htaccess');

        foreach($files as $file){
            if(!file_exists(ABSPATH . $file)){
                continue;
            }

            $file_content = file_get_contents(ABSPATH . $file);

            if(strpos($file_content, '# BEGIN WebP Express') === false || empty($content)){
                $file_content = preg_replace('/# BEGIN MY WebP Express Plus.*# END MY WebP Express Plus/s', '', $file_content);

                file_put_contents(ABSPATH . $file, $file_content);

                continue;
            }

            if(strpos($file_content, '# BEGIN MY WebP Express Plus') !== false){
                $file_content = preg_replace('/\# BEGIN MY WebP Express Plus.*\# END MY WebP Express Plus/s', $content, $file_content);
            }else{
                $file_content = str_replace('# BEGIN WebP Express', $content . PHP_EOL . PHP_EOL . '# BEGIN WebP Express', $file_content);
            }

            file_put_contents(ABSPATH . $file, $file_content);
        }
    });


	add_action('wp_head', function(){
		ob_start();
	});

	add_action('wp_footer', function(){
		$data = ob_get_clean();
		
		$rules = get_option('webp_express_plus_rules');
		
		if(empty($rules)){
			echo $data;
			return;
		}
		
		$rules = array_map('trim', explode(PHP_EOL, $rules));

		$html = str_get_html($data, false, false, DEFAULT_TARGET_CHARSET, false);
		
		foreach($html->find('img') as $img){
			foreach($rules as $rule){
				if(strpos(str_replace(get_site_url(), '', $img->src), $rule) === 0){
					$img->class .= ' webpexpress-processed';
				}
			}
		}
		
		echo $html->outertext;
	});
?>