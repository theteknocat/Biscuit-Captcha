<img src="<?php echo Captcha::image_url()?>" border="0" alt="Security Captcha" id="captcha_image"><br>
We know these things are annoying, but they prove you are human and<br>really help prevent spam.<br>
If you can't read the text, <a href="#refresh_captcha" id="captcha_refresh_button">please click here for a new one</a>.
<input type="hidden" name="captcha_required" value="1">
<script type="text/javascript">
<?php
if (!Request::is_ajax()) {
?>
$(document).ready(function() {
<?php
}
?>
	$('#captcha_refresh_button').click(function() {
		// Generate a new timestamp to use for the captcha url:
		var currTime = new Date();
		var now = currTime.getTime();
		// Set the image source to the new captcha url:
		$('#captcha_image').attr('src',"/captcha/"+now);
		return false;
	});
<?php
if (!Request::is_ajax()) {
?>
});
<?php
}
?>
</script>
