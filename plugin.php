<?php
/*
Plugin Name: COP CSS Custom Post Type Lite
Plugin URI: http://azuregreencreative.com/cop_css/
Description: Generate CSS from custom post types
Version: 0.2.0
Author: Trevor Green
Author URI: http://www.azuregreencreative.com/
*/
/*
 * Available Shortcodes
 * 
 * [meta key="(name of arbitrary custom field)" post_id="(optional)"]
 *
 * 0.2.0
 * 
 * Bug Fixes to hook registration
 * 
 * 0.1.9
 * 
 * Began adding shortcodes to css
 * 
 * 0.1.8
 * 
 * Fixed preview using option instead of transient.
 * Added check for permission to 'edit_themes' before loading interface.
 * 
 * 0.1.7
 * 
 * Initial Public Version
 * 
 * 
 */
new cop_css();

class cop_css {
	
	public $compiledcss;
	
    function __construct() {
    	
    	/* add output of the style via an ajax style redirection of the parse_request */
		add_action('parse_request', array($this, 'produce_my_css'), 10);
		
		/* Add compiled style to header */
		add_action('wp_print_styles', array($this, 'add_stylesheet'));
		// Creates Template post type
		add_action('init', array($this, 'register_post_type'));

		add_action('init', array($this, 'register_css_shortcodes'));		
		
		add_action('admin_menu', array($this, 'setup_editor'));
		
		add_action('admin_menu',  array($this,'cop_css_admin_menu'));
	    	
    }
    public function setup_editor() {
    	if(current_user_can('edit_themes')){
			/* turn of the rich editor which when used reformats the css to where its unusable */
			add_filter('user_can_richedit' , array($this, 'toggle_richedit') , 49);
					
			add_action('save_post', array($this, 'compile_css_on_save'));
				
			/* adding a column to the style sheet custom post type list */
			add_filter('manage_edit-css_columns', array($this, 'add_new_css_columns'));
			add_action('manage_css_posts_custom_column', array($this, 'manage_css_columns'), 10, 2);
			add_filter("manage_edit-css_sortable_columns", array($this,'order_sort') );
	
    	}
    }
    
	public function register_post_type() {
		$my_css_post_type = 'css';
		$show_ui = true;
		if(!current_user_can('edit_themes')) { $show_ui = false; }
		
		register_post_type($my_css_post_type, array(
		'label' => 'Style Sheets',
		'labels' => array('new_item' => 'New Style','edit_item' => 'Edit Style'),
		'public' => true,
		'show_ui' => $show_ui,
		'capability_type' => 'post',
		'hierarchical' => false,
		'rewrite' => array('slug' => 'css'),
		'query_var' => false,
		/*'capabilities' => array(
			'publish_posts' => 'publish_{$my_css_post_type}',
			'edit_posts' => 'edit_{$my_css_post_type}',
			'edit_others_posts' => 'edit_others_{$my_css_post_type}',
			'delete_posts' => 'delete_{$my_css_post_type}',
			'delete_others_posts' => 'delete_others_{$my_css_post_type}',
			'read_private_posts' => 'read_private_{$my_css_post_type}',
			'edit_post' => 'edit_{$my_css_post_type}',
			'delete_post' => 'delete_{$my_css_post_type}',
			'read_post' => 'read_{$my_css_post_type}',
		),*/
		'supports' => array(
		'title',
		'editor',
		//'excerpt',
		//'trackbacks',
		'custom-fields',
		//'comments',
		'revisions',
		'thumbnail',
		'author',
		'page-attributes',)
		) );
	}	
	
	function toggle_richedit() {
		global $post;
		
		if($post->post_type == "css") {
			return false;
		} else {
			return true;
		}
	}
	function compile_css_on_save($post_id) {
		if(get_post($post_id)->post_type == 'css') {
			$this->compile_css();		
			return $post_id;
		} else {
			return $post_id;
		}
	}
	function compile_css() {
		$encoded = $this->css_query();
		set_transient('cop_css', $encoded, 60*60*12);		
	}
	function produce_my_css() {
		
		if (isset($_GET['css'])){
			header( 'Content-Type: text/css' );
			
			// if transient is not cached regenerate.
			if(false === ($this->compiledcss = get_transient('cop_css'))) {
				$this->compile_css();
			}
			echo stripslashes(get_transient('cop_css'));
			exit;
		}		
	}
	function add_stylesheet() {
		$myStyleUrl = get_site_url() . '?css=1';
		if(!is_admin()) {	
			wp_register_style('cop_css_style_sheet', $myStyleUrl);
	    	wp_enqueue_style( 'cop_css_style_sheet');
		}
	}
	
	function add_new_css_columns($defaults) {
	    $defaults['order'] = __('Order');
	    return $defaults;
	}
	function manage_css_columns($column_name, $id) {
			global $post;
			switch ($column_name) {
			case 'order':
				echo $post->menu_order;
			        break;
	 		default:
				break;
			} // end switch
	}
	// Sort admin cols
	function order_sort($columns) {
		$custom = array(
			'orderby'    => 'menu_order',
			'order'    => 'order'
			);
		return wp_parse_args($custom, $columns);
	}

	/* Query the css custom post type for all posts, compile and return */
	function css_query($name = null) {
	
		$this->compiledcss = "";
		
		$cssquery = new WP_Query(array( 'showposts' => '10000', 'post_type' => 'css', 'orderby' => 'menu_order', 'order' => 'asc', 'post_status' => 'publish' )); 
		

		if ($cssquery->have_posts()) {
			while ( $cssquery->have_posts() ) : $cssquery->the_post();
				$this->compiledcss .= "/* style sheet : " . get_the_title() . "*/";
				if(get_post_meta(get_the_ID(), 'selector', TRUE) != '') {
					$this->compiledcss .= $this->add_path(get_post_meta(get_the_ID(), 'selector', TRUE), do_shortcode(stripslashes(get_the_content())));		
				} else {
					$this->compiledcss .= do_shortcode(stripslashes(get_the_content()));				
				}
			endwhile;
		} 
	
		if(get_option('cop_css_minify') == 'true') {
			return $this->minify($this->compiledcss);
		} else {
			return $this->compiledcss;
		}
	}
	
	/* credit: http://www.lateralcode.com/css-minifier/ */
	function minify( $css ) {
		$css = preg_replace( '#\s+#', ' ', $css );
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );
		$css = str_replace( '; ', ';', $css );
		$css = str_replace( ': ', ':', $css );
		$css = str_replace( ' {', '{', $css );
		$css = str_replace( '{ ', '{', $css );
		$css = str_replace( ', ', ',', $css );
		$css = str_replace( '} ', '}', $css );
		$css = str_replace( ';}', '}', $css );
	
		return trim( $css );
	}

	function parse_css($css) {
	
		preg_match_all( '/(?ims)([a-z0-9,\s\.\:#_\-@]+)\{([^\}]*)\}/', $css, $arr);
	
		$result = array();
		foreach ($arr[0] as $i => $x)
		{
		    $selector = trim($arr[1][$i]);
		    $rules = explode(';', trim($arr[2][$i]));
		    $result[$selector] = array();
		    foreach ($rules as $strRule)
		    {
		        if (!empty($strRule))
		        {
		            $rule = explode(":", $strRule, 2);
		            
		            $result[$selector][][trim($rule[0])] = $rule[1];
		        }
		    }
		}   
		
		return $result;
	}
	
	function add_path($path, $css) {

		/* load the css into an array for parseing */
		$cssarray = $this->parse_css($css);
		
		$cssarraykeys = array_keys($cssarray);
		
		foreach($cssarraykeys as $csskey) {
		    $keyarray = explode(',', trim($csskey));

		    $firstkey = true;
		    foreach($keyarray as $singlekey) {
		    	
		    	if(trim($singlykey) == "(selector)") {
		    		$keyname =  trim($path);
		    	} else {
		    		$keyname = trim($path) . " " . trim($singlekey);
		    	}
		    	
		    	if ($firstkey) {
			    	$result .=  "\n" . $keyname; 
			    	$firstkey = false;
		    	} else {
		        	$result .= ", \n" . $keyname . "\n"; 	
		    	}
		    }
		    
		    $result .= " { \n";
			foreach(array_keys($cssarray[$csskey]) as $rule) {
				foreach(array_keys($cssarray[$csskey][$rule]) as $cssrule) {
					$result .= $cssrule . ": ";
					$result .= $cssarray[$csskey][$rule][$cssrule] . ";\n";
				}
			}
			$result .= "}\n";	
		}
		
		return $result;	
	}
	/* Add shortcodes here */
	function register_css_shortcodes(){
		add_shortcode('meta', array($this, 'insert_css_meta') );	
	}
	function insert_css_meta($atts = null, $content = null){
		global $post;
		extract( shortcode_atts( array(
			'post_id' => $post->ID,
			'key' => '',
		), $atts ) );

		return get_post_meta($post_id, $key, true);
	}
	/* End of shortcodes */
	
	function control_press_css_options() {
		
		if(isset($_POST['cop_css_minify'])) {
			update_option('cop_css_minify', $_POST['cop_css_minify']);		
		} else {
			update_option('cop_css_minify', 'false');		
		}
		
		$this->compile_css();	
	
		echo '<form method="post" action="">';
		echo '<div style="margin:20px;">';	
	
		if(isset($_POST['cop_css'])) {
			//commented out manual editing of previewed css.
			//update_option('cop_css', $_POST['cop_css']);			   
		}			
				
		if( get_option('cop_css_minify') == 'true') { $checked = 'checked="checked"'; } else { $checked = ""; }
		
		echo '<input type="checkbox" name="cop_css_minify" id="cop_css_minify" value="true" ' . $checked . ' /> Minify your css.';    
		echo '<br/><br/><label>Generated CSS</label><br/><br/><textarea width="600px" rows="5" cols="100" name="cop_css">' .  get_transient('cop_css') 
		. '</textarea>';
	
		echo '<br/><br/><br/><br/><button type="submit">ReGenerate</button>';
		echo '<br/><br/><br/><br/><button type="submit">Save</button>';
		echo '</div>';	
		echo '</form>';
	}	

	public function cop_css_admin_menu() {
		$page = add_submenu_page('edit.php?post_type=css','Options', 'Options', 'manage_options','control-press-css-options',  array($this, 'control_press_css_options'));
	
	}
}

