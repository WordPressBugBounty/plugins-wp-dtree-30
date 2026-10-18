<?php
defined('ABSPATH') || exit;
class WPDT_Menu_Widget extends WPDT_Widget{	
	function __construct() {			
		$widget_ops = array('classname' => 'wpdt-menu', 'description' => __('Your custom menus, the dTree way.', 'wp-dtree-30') ); //widget settings. 
		$control_ops = array('width' => 200, 'height' => 350, 'id_base' => 'wpdt-menu-widget'); //Widget control settings.
		parent::__construct('wpdt-menu-widget', __('WP-dTree Menu', 'wp-dtree-30'), $widget_ops, $control_ops ); //Create the widget.		
	}
	
	function widget($args, $settings){
		$settings = wp_parse_args((array) $settings, wpdt_get_defaults('mnu'));
		$settings['treetype'] = 'mnu';
		parent::widget($args, $settings);
	}

	function update($new_settings, $old_settings) {// Update the widget settings.					
		$new_settings = array_filter((array) $new_settings, 'is_scalar');
		$old_settings = parent::update($new_settings, $old_settings);
		$settings = $old_settings;
		$settings['menuslug'] 	= isset($new_settings['menuslug']) ? $new_settings['menuslug'] : '';	
		$settings['treetype']	= 'mnu';		
		return $settings;
	}

	function form($settings) {
		$defaults = wpdt_get_defaults('mnu');	
		$settings = wp_parse_args((array) $settings, $defaults); 
		parent::form($settings);
		$menus = wp_get_nav_menus(array('orderby'=>'name','hide_empty'=>true));		
	?>
		<p>
		
			<label for="<?php echo esc_attr($this->get_field_id('menuslug')); ?>"><?php esc_html_e('Select Menu:', 'wp-dtree-30'); ?></label> 	
			<select id="<?php echo esc_attr($this->get_field_id('menuslug')); ?>" name="<?php echo esc_attr($this->get_field_name('menuslug')); ?>" class="widefat" style="width:100px;">	
				<?php 
					if(is_array($menus)){
						foreach($menus as $menu){
							echo '<option value="' . esc_attr($menu->slug) . '"'; selected($settings['menuslug'], $menu->slug); echo '>' . esc_html($menu->name) . '</option>';
						}					
					}
				?>				
			</select>	
		</p>			
	<?php
	}
}

?>