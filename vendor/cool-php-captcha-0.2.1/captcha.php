<?php
/**
 * Script para la generación de CAPTCHAS
 *
 * Modified by Peter Epp to work with the Biscuit framework:
 *
 * - Uses Biscuit session var setting
 * - Made all properties private and accessible for setting via public methods
 * - Disabled session start as that's done by Biscuit
 *
 * @author  Jose Rodriguez <jose.rodriguez@exec.cl>
 * @license GPLv3
 * @link    http://code.google.com/p/cool-php-captcha
 * @package captcha
 *
 */

// Session will have been started by Biscuit, so skip this
// session_start();



// $captcha = new SimpleCaptcha();

// Change configuration...
//$captcha->wordsFile = 'words/es.txt';
//$captcha->session_var = 'secretword';
//$captcha->imageFormat = 'png';
//$captcha->scale = 3; $captcha->blur = true;




// $captcha->CreateImage();













/**
 * SimpleCaptcha class
 *
 */
class SimpleCaptcha {

    /** Width of the image */
    protected $width  = 200;

    /** Height of the image */
    protected $height = 70;

    /** Dictionary word file (empty for randnom text) */
    protected $wordsFile = 'words/en.txt';

    /** Min word length (for non-dictionary random text generation) */
    protected $minWordLength = 5;

    /**
     * Max word length (for non-dictionary random text generation)
     * 
     * Used for dictionary words indicating the word-length
     * for font-size modification purposes
     */
    protected $maxWordLength = 8;

    /** Sessionname to store the original text */
    protected $session_var = 'captcha';

    /** Background color in RGB-array */
    protected $backgroundColor = array(255, 255, 255);

    /** Foreground colors in RGB-array */
    protected $colors = array(
        array(27,78,181), // blue
        array(22,163,35), // green
        array(214,36,7),  // red
    );

    /** Shadow color in RGB-array or false */
    protected $shadowColor = false; //array(0, 0, 0);

    /**
     * Font configuration
     *
     * - font: TTF file
     * - spacing: relative pixel space between character
     * - minSize: min font size
     * - maxSize: max font size
     */
    protected $fonts = array(
        'Antykwa'  => array('spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'),
        'Candice'  => array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'),
        'DingDong' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'Ding-DongDaddyO.ttf'),
        'Duality'  => array('spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'),
        'Heineken' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 34, 'font' => 'Heineken.ttf'),
        'Jura'     => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'),
        'StayPuft' => array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'),
        'Times'    => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'),
        'VeraSans' => array('spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'),
    );

    /** Wave configuracion in X and Y axes */
    protected $Yperiod    = 12;
    protected $Yamplitude = 14;
    protected $Xperiod    = 11;
    protected $Xamplitude = 5;

    /** letter rotation clockwise */
    protected $maxRotation = 8;

    /**
     * Internal image size factor (for better image quality)
     * 1: low, 2: medium, 3: high
     */
    protected $scale = 2;

    /** 
     * Blur effect for better image quality (but slower image processing).
     * Better image results with scale=3
     */
    protected $blur = false;

    /** Debug? */
    protected $debug = false;
    
    /** Image format: jpeg or png */
    protected $imageFormat = 'png';


    /** GD image */
    protected $im;

	/**
	 * Set configuration, if provided
	 *
	 * @param string $config 
	 * @author Peter Epp
	 */
    public function __construct($config = array()) {
		// Set configuration, if provided
		if (!empty($config)) {
			$this->set_attributes($config);
		}
    }


	/**
	* Set a single attribute
	*
	* @param string $name Name of the attribute
	* @param string $value Desired value
	* @return void
	* @author Lee O'Mara
	* @author Peter Epp
	*/
	public function set($name,$value) {
		$this->$name = $value;
	}
	/**
	* Set attributes
	*
	* @param array $attributes
	* @author Lee O'Mara
	*/
	public function set_attributes($attributes) {
		$class_vars = get_object_vars($this);
		foreach ($class_vars as $key => $value) {
			if(!isset($attributes[$key])){
				continue;
			}
			$this->set($key, $attributes[$key]);
		}
	}


    public function CreateImage() {
        $ini = microtime(true);

        /** Initialization */
        $this->ImageAllocate();
        
        /** Text insertion */
        $text = $this->GetCaptchaText();
        $fontcfg  = $this->fonts[array_rand($this->fonts)];
        $this->WriteText($text, $fontcfg);

        // Use the Biscuit way of setting the session var:

        // $_SESSION[$this->session_var] = $text;
        Session::set($this->session_var,$text);

        /** Transformations */
        $this->WaveImage();
        if ($this->blur) {
            imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $this->ReduceImage();


        if ($this->debug) {
            imagestring($this->im, 1, 1, $this->height-8,
                "$text {$fontcfg['font']} ".round((microtime(true)-$ini)*1000)."ms",
                $this->GdFgColor
            );
        }


        /** Output */
        $this->WriteImage();
        $this->Cleanup();
    }









    /**
     * Creates the image resources
     */
    protected function ImageAllocate() {
        // Cleanup
        if (!empty($this->im)) {
            imagedestroy($this->im);
        }

        $this->im = imagecreatetruecolor($this->width*$this->scale, $this->height*$this->scale);

        // Background color
        $this->GdBgColor = imagecolorallocate($this->im,
            $this->backgroundColor[0],
            $this->backgroundColor[1],
            $this->backgroundColor[2]
        );
        imagefilledrectangle($this->im, 0, 0, $this->width*$this->scale, $this->height*$this->scale, $this->GdBgColor);

        // Foreground color
        $color           = $this->colors[mt_rand(0, sizeof($this->colors)-1)];
        $this->GdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);

        // Shadow color
        if (!empty($this->shadowColor)) {
            $this->GdShadowColor = imagecolorallocate($this->im,
                $this->shadowColor[0],
                $this->shadowColor[1],
                $this->shadowColor[2]
            );
        }
    }





    /**
     * Text generation
     *
     * @return string Text
     */
    protected function GetCaptchaText() {
        $text = $this->GetDictionaryCaptchaText();
        if (!$text) {
            $text = $this->GetRandomCaptchaText();
        }
        return $text;
    }






    /**
     * Random text generation
     *
     * @return string Text
     */
    protected function GetRandomCaptchaText($length = null) {
        if (empty($length)) {
            $length = rand($this->minWordLength, $this->maxWordLength);
        }

        $words  = "abcdefghijlmnopqrstvwyz";
        $vocals = "aeiou";

        $text  = "";
        $vocal = rand(0, 1);
        for ($i=0; $i<$length; $i++) {
            if ($vocal) {
                $text .= substr($vocals, mt_rand(0, 4), 1);
            } else {
                $text .= substr($words, mt_rand(0, 22), 1);
            }
            $vocal = !$vocal;
        }
        return $text;
    }









    /**
     * Random dictionary word generation
     *
     * @param boolean $extended Add extended "fake" words
     * @return string Word
     */
    function GetDictionaryCaptchaText($extended = false) {
        if (empty($this->wordsFile)) {
            return false;
        }

        $words     = file_get_contents($this->wordsFile, "r");
		if (!$words) {
			return false;
		}
		$word_array = explode("\n",$words);
		$word_array = array_filter($word_array);
		$word = rand(0,count($word_array)-1);
		$text = $word_array[$word];

        /** Change ramdom volcals */
        if ($extended) {
            $text   = str_split($text, 1);
            $vocals = array('a', 'e', 'i', 'o', 'u');
            foreach ($text as $i => $char) {
                if (mt_rand(0, 1) && in_array($char, $vocals)) {
                    $text[$i] = $vocals[mt_rand(0, 4)];
                }
            }
            $text = implode('', $text);
        }

        return $text;
    }










    /**
     * Text insertion
     */
    protected function WriteText($text, $fontcfg = array()) {
        if (empty($fontcfg)) {
            // Select the font configuration
            $fontcfg  = $this->fonts[array_rand($this->fonts)];
        }
        $fontfile = FW_MODULE_PATH.'/captcha/vendor/cool-php-captcha-0.2.1/fonts/'.$fontcfg['font'];
		Console::log("Captcha font file: ".$fontfile);
		if (!file_exists($fontfile)) {
			trigger_error("Font file not found: ".$fontfile,E_USER_WARNING);
		}

        /** Increase font-size for shortest words: 9% for each glyp missing */
        $lettersMissing = $this->maxWordLength-strlen($text);
        $fontSizefactor = 1+($lettersMissing*0.09);

        // Text generation (char by char)
        $x      = 20*$this->scale;
        $y      = round(($this->height*27/40)*$this->scale);
        $length = strlen($text);
        for ($i=0; $i<$length; $i++) {
            $degree   = rand($this->maxRotation*-1, $this->maxRotation);
            $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$this->scale*$fontSizefactor;
            $letter   = substr($text, $i, 1);

            if ($this->shadowColor) {
                $coords = imagettftext($this->im, $fontsize, $degree,
                    $x+$this->scale, $y+$this->scale,
                    $this->GdShadowColor, $fontfile, $letter);
            }
            $coords = imagettftext($this->im, $fontsize, $degree,
                $x, $y,
                $this->GdFgColor, $fontfile, $letter);
            $x += ($coords[2]-$x) + ($fontcfg['spacing']*$this->scale);
        }
    }



    /**
     * Wave filter
     */
    protected function WaveImage() {
        // X-axis wave generation
        $xp = $this->scale*$this->Xperiod*rand(1,3);
        $k = rand(0, 100);
        for ($i = 0; $i < ($this->width*$this->scale); $i++) {
            imagecopy($this->im, $this->im,
                $i-1, sin($k+$i/$xp) * ($this->scale*$this->Xamplitude),
                $i, 0, 1, $this->height*$this->scale);
        }

        // Y-axis wave generation
        $k = rand(0, 100);
        $yp = $this->scale*$this->Yperiod*rand(1,2);
        for ($i = 0; $i < ($this->height*$this->scale); $i++) {
            imagecopy($this->im, $this->im,
                sin($k+$i/$yp) * ($this->scale*$this->Yamplitude), $i-1,
                0, $i, $this->width*$this->scale, 1);
        }
    }




    /**
     * Reduce the image to the final size
     */
    protected function ReduceImage() {
        // Reduzco el tamaño de la imagen
        $imResampled = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($imResampled, $this->im,
            0, 0, 0, 0,
            $this->width, $this->height,
            $this->width*$this->scale, $this->height*$this->scale
        );
        imagedestroy($this->im);
        $this->im = $imResampled;
    }








    /**
     * File generation
     */
    protected function WriteImage() {
        if ($this->imageFormat == 'png') {
            imagepng($this->im);
        } else {
            imagejpeg($this->im, null, 80);
        }
    }







    /**
     * Cleanup
     */
    protected function Cleanup() {
        imagedestroy($this->im);
    }
}
?>
