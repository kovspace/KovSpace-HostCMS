<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Template Helpers
 *
 * @author KovSpace
 * @version 2018-09-11
 * @copyright © 2018 https://kovspace.com/
 */
class KovSpace_Template
{
	public $title;
	public $description;
	public $keywords;
	public $root;
	public $path;
	public $section;
	public $object;
	public $objectGroupId;
	public $objectItemId;
	public $kovspace;
	public $hostcms;

	protected $_aSection;

	public function __construct() {

		// Short Link to Shop Item
		if (strstr(Core::$url['path'], "/shop/item_id/")) {
			if (basename(Core::$url['path']) > 0) {
				$shopItemId = basename(Core::$url['path']);
				$oShopItem = Core_Entity::factory('Shop_Item', $shopItemId);

				if ($oShopItem->name) {
					$shopItemPath = 'https://' . $oShopItem->Shop->Site->name . $oShopItem->Shop->Structure->getPath() . $oShopItem->getPath();
				} else {
					$shopItemPath = '/';
				}
				if ($_SERVER['QUERY_STRING']) {
					header('Location: ' . $shopItemPath . '?' . $_SERVER['QUERY_STRING']);
				} else {
					header('Location: ' . $shopItemPath);
				}
				exit();
			}
		}

		Core_Page::instance()->css = array(); // remove style.css
		Core_Page::instance()->js = array(); // remove script.js

		$this->title = str_replace('&amp;', '&', htmlspecialchars(Core_Page::instance()->title));
		$this->description = htmlspecialchars(Core_Page::instance()->description);
		$this->keywords = htmlspecialchars(Core_Page::instance()->keywords);

		$this->root = rtrim(CMS_FOLDER, '/\\');
		$this->path = '/templates/template' . Core_Page::instance()->template->id . '/';

		// The current object
		$this->object = Core_Page::instance()->object;

		$this->objectGroupId = isset($this->object->group) && $this->object->group != ''
		? $this->object->group
		: 0;

		$this->objectItemId = isset($this->object->item) && $this->object->item != ''
		? $this->object->item
		: 0;

		// CSS Breadcrumbs
		$oStructure = clone Core_Page::instance()->structure;
		$oStructure->path = $oStructure->path == '/'
		? 'home'
		: $oStructure->path;

		$aPath = [];
		do {
			$aPath[] = $oStructure->path;
		} while($oStructure = $oStructure->getParent());
		$aPath = array_reverse($aPath);

		$aSection = [];
		for ($i = 1; $i <= count($aPath); $i++) {
			$aSection[] = implode("-", array_slice($aPath, 0, $i));
		}

		$this->_aSection = $aSection;
		$this->section = implode($aSection, ' ');

		// Copyrights
		$this->kovspace = '<a rel="noopener" target="_blank" href="https://kovspace.com/">KovSpace</a>';
		$this->hostcms = '<a rel="noopener" target="_blank" href="http://www.hostcms.ru/">HostCMS</a>';
	}

	// Image Upload Timestamp
	public function imageTimestamp() {
		Core_Event::attach('shop_item.onBeforeGetXml', array('Image_Upload_Timestamp_Observer', 'onBeforeGetXml'));
		Core_Event::attach('shop_group.onBeforeGetXml', array('Image_Upload_Timestamp_Observer', 'onBeforeGetXml'));
		Core_Event::attach('informationsystem_item.onBeforeGetXml', array('Image_Upload_Timestamp_Observer', 'onBeforeGetXml'));
		Core_Event::attach('informationsystem_group.onBeforeGetXml', array('Image_Upload_Timestamp_Observer', 'onBeforeGetXml'));
		return $this;
	}

	public function imageCDN() {
		Core_Page::instance()->informationsystemCDN = 'https://i0.wp.com/' . Core::$url['host'];
		Core_Page::instance()->shopCDN = 'https://i0.wp.com/' . Core::$url['host'];
		Core_Page::instance()->structureCDN = 'https://i0.wp.com/' . Core::$url['host'];
		return $this;
	}

	public function informationsystemCDN() {
		Core_Page::instance()->informationsystemCDN = 'https://i0.wp.com/' . Core::$url['host'];
		return $this;
	}

	public function shopCDN() {
		Core_Page::instance()->shopCDN = 'https://i0.wp.com/' . Core::$url['host'];
		return $this;
	}

	public function structureCDN() {
		Core_Page::instance()->structureCDN = 'https://i0.wp.com/' . Core::$url['host'];
		return $this;
	}

	public function showMeta() {
		echo '<title>'.$this->title.'</title>' . "\n\t";
		echo '<meta charset="utf-8">' . "\n\t";
		echo '<meta name="description" content="'.$this->description.'"/>' . "\n\t";
		echo '<meta name="keywords" content="'.$this->keywords.'"/>' . "\n\t";
		return $this;
	}

	public function showViewport($width = NULL) {
		if ($width) {
			echo '<meta name="viewport" content="width=' . $width . '">' . "\n\t";
		} else {
			echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n\t";
		}

		return $this;
	}

	public function showFavicon() {
		if (is_file(CMS_FOLDER . $this->path . 'img/favicon.png')) {
			echo '<link rel="icon" type="image/png" href="' . $this->path . 'img/favicon.png">' . "\n\t";
			echo '<link rel="apple-touch-icon" href="' . $this->path . 'img/favicon.png">' . "\n\t";
		}
		return $this;
	}
	public function showVendorCSS($url) {
		echo '<link rel="stylesheet" href="' . $url . '">' . "\n\t";
		return $this;
	}

	public function showSectionCSS() {
		echo "\n\t";
		echo '<style>' . "\n\t";
		echo $this->_CSS($this->path . 'css/base.css');
		foreach ($this->_aSection as $section) {
			echo $this->_CSS($this->path . 'css/' . $section . '.css');
		}
		echo "\t";
		echo '</style>' . "\n";
		return $this;
	}

	public function showCSS($file) {
		echo "\n\t" .'<style>' . "\n\t";
		echo $this->_CSS($file) . "\t";
		echo '</style>' . "\n";
		return $this;
	}

	protected function _CSS($file) {
		if (is_file($this->root . $file)) {
			if (Core::moduleIsActive('compression')) {
				$oCompression_Controller = Compression_Controller::instance('css');
				$oCompression_Controller->clear();
				$oCompression_Controller->addCss($file);
				$sPath = $oCompression_Controller->getPath();

				$css_source_time = filemtime($this->root . $file);
				$css_compress_time = filemtime($this->root . $sPath);

				if ($css_compress_time < $css_source_time) {
					Core_File::delete($this->root . $sPath);
					$oCompression_Controller->clear();
					$oCompression_Controller->addCss($file);
					$sPath = $oCompression_Controller->getPath();
				}

				return Core_File::read($this->root . $sPath);
			} else {
				$css = Core_File::read($this->root . $file);
				$css = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $css);
				$css = str_replace([': '], ':', $css);
				$css = str_replace(['    '], '', $css);
				$css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
				return $css;
			}
		}
	}

	public function showJS($file, $id = NULL) {

		if ($id) {
			echo '<script id="'.$id.'">';
		} else {
			echo '<script>';
		}

		if (Core::moduleIsActive('compression')) {
			$oCompression_Controller = Compression_Controller::instance('js');
			$oCompression_Controller->clear();
			$oCompression_Controller->addJs($file);
			$sPath = $oCompression_Controller->getPath();

			$js_source_time = filemtime($this->root . $file);
			$js_compress_time = filemtime($this->root . $sPath);;

			if ($js_compress_time < $js_source_time) {
				Core_File::delete($this->root . $sPath);
				$oCompression_Controller->clear();
				$oCompression_Controller->addJs($file);
				$sPath = $oCompression_Controller->getPath();
			}

			$js_file = Core_File::read($this->root . $sPath);

			if (substr($js_file, -3) == ';;'.PHP_EOL) {
				echo substr($js_file, 0, -2).PHP_EOL;
			} else {
				echo $js_file;
			}
		} else {
			echo Core_File::read($this->root . $file);
		}
		echo '</script>';
	}

	public function showKovSpace() {
		echo 'Создание сайта ' . $this->kovspace;
	}
	public function showHostCMS() {
		echo 'Работает на ' . $this->hostcms;
	}
	public function showPrivacy() {
		echo '<a href="/privacy/">Политика конфиденциальности</a>';
	}

	public function gTag($id) {
		echo '
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id='.$id.'"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag("js", new Date());
      gtag("config", "'.$id.'");
    </script>'."\n";
	}

	public function googleTagManager($id) {
		echo '
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":
	new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src=
	"https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,"script","dataLayer","'.$id.'");</script>
	<!-- End Google Tag Manager -->'."\n";
	}

	public function googleTagManagerNoScript($id) {
		echo '
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$id.'"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->'."\n";
	}

	public function emailFrom() {
		$config = Core::$config->get('core_mail');
        if (isset($config['smtp'][CURRENT_SITE]['username'])) {
            $emailFrom = $config['smtp'][CURRENT_SITE]['username'];
        } elseif (isset($config['smtp']['username'])) {
            $emailFrom = $config['smtp']['username'];
        } else {
            $emailFrom = EMAIL_TO;
		}
		return $emailFrom;
	}

}


/* Additional Classes */

class Image_Upload_Timestamp_Observer
{
   static public function onBeforeGetXml($object, $args)
   {

   		if ($object->image_small && !stristr($object->image_small, '?')) {
   			$image_small_timestamp = @filemtime($object->getSmallFilePath());
   			$object->image_small .= '?' . $image_small_timestamp;
   		}

   		if ($object->image_large && !stristr($object->image_large, '?')) {
   			$image_large_timestamp = @filemtime($object->getLargeFilePath());
   			$object->image_large .= '?' . $image_large_timestamp;
   		}
   }
}
