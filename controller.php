<?php
require_once('modules/captcha/vendor/cool-php-captcha-0.2.1/captcha.php');
/**
 * Wrapper module for Cool PHP Captcha and checking if user input matches captcha
 *
 * @package default
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class Captcha extends AbstractModuleController {
	protected $_uncacheable_actions = array('index');
	public function action_index() {
		if ($this->Biscuit->Page->short_slug() == "captcha") {
			$this->set_view_var('captcha',new SimpleCaptcha(array('wordsFile' => '')));
			Response::content_type("image/png");
			$this->render();
		}
	}
	/**
	 * Does the user input match the captcha?
	 *
	 * @param string $user_input
	 * @return bool
	 * @author Peter Epp
	 */
	public static function matches($user_input) {
		$captcha = Session::get('captcha');
		$user_input = $user_input;
		return ($user_input == $captcha);
	}
	/**
	 * Include the captcha widget view file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function render_widget() {
		echo Crumbs::capture_include('captcha/views/widget.php');
	}
	/**
	 * Return a time-stamped URL for the captcha
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function image_url() {
		return '/captcha/'.time();
	}
	/**
	 * Run migrations required for module to be installed properly
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install_migration() {
		$captcha_page = DB::fetch_one("SELECT `id` FROM `page_index` WHERE `slug` = 'captcha'");
		if (!$captcha_page) {
			// Add captcha page:
			DB::insert("INSERT INTO `page_index` SET `parent` = 9999999, `slug` = 'captcha', `title` = 'Captcha'");
			// Get module row ID:
			$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'Captcha'");
			// Remove captcha from module pages first to ensure clean install:
			DB::query("DELETE FROM `module_pages` WHERE `module_id` = {$module_id} AND `page_name` = 'captcha'");
			// Add Captcha to captcha page:
			DB::insert("INSERT INTO `module_pages` SET `module_id` = {$module_id}, `page_name` = 'captcha', `is_primary` = 1");
		}
	}
	/**
	 * Run migrations to properly uninstall the module
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uninstall_migration() {
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'Captcha'");
		DB::query("DELETE FROM `page_index` WHERE `slug` = 'captcha'");
		DB::qruey("DELETE FROM `module_pages` WHERE `module_id` = ".$module_id);
	}
	/**
	 * Set not to render with template prior to dispatch
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_dispatch_request() {
		if ($this->is_primary()) {
			$this->Biscuit->render_with_template(false);
		}
	}
}
?>