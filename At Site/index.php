<?php
/*
	Nathan/SoreThumb's Explosive Dropbox CMS.
		This ver last Modified: 7/18/2012
		sorethumb@gmail.com
		http://thenateway.net , http://thenatework.com , http://sorethumb.me
	
	If you're reading this, here's just a brief outline:
		1. Have Dropbox, and a website.
		2. Make sure the folder you put all this into has +w permissions. (Look up chmod.)
		3. If there's no 'my-site.php' file, there's no site prepared. So, the site auto-configs..
		4. Introduce a visitor to a configuration page that helps them set up the site.
		5. After they set up all the information, get Dropbox's participation.
		6. Create the my-site.php file with all that info.
		7. Once that's done, do a cleanup and setup for Dropbox and this site.
			7a. If there're files at this site, check their compatability.
			7b. If there're none, set up template files at both places so the user can figure it out.
			
		
	After the above, here's the Normal Operation:
		1.	.htaccess uses "Rewrite" to turn urls like...
			/this-page-exists/ into a mash-together/composite of 
				design/header.html + pages/This Page Exists.txt + design/footer.html
		2.	.htaccess uses "Rewrite" to turn urls like...
			/portfolio/some-file-name/ into a mash-together/composite of..
				design/header.html + portfolio/some File name.jpg + design/footer.html
				
				(The details of that image are taken from the "EXIF" data.
				Don't know what EXIF is? Well, get the properties of your JPG file, and
				you'll find a 'details' page with the EXIF information.)
				
			Or,
			/portfolio/some-file-name/ into a mash-together composite of..
				design/header.html + portfolio/Some File Name.mp3 + design/footer.html
				
		3.	Note that, with the above, we alter the <html> tag constantly with the ID (the URL).
			This is important, because CSS is versatile to change the design of a page radically
			just by changing the id tag.
				html#index div.onlyOnHomepage { display: block; }
				html div.onlyOnHomepage { display: none; }
				
		4.	.htaccess uses "Rewrite" to turn urls like...
			/some-section/this-is-some-page/ into a mash-together/composite of..
				design/header.html + pages/Some-section/This is some-page.html + design/footer.html
			
			**pages within a section are sorted by date-modified**
*/

define('CACHETIME',3600);
define('DROPCACHETIME',36000);

class cachedPage {
	public $path;
	public $content;
	protected $fileMan;
	public function __construct($path) {
		$this->path = $path;
		$this->setCachePath();
		if ($this->cachedOrNot()) {
			$this->content = file_get_contents('cache/' . $this->cachePath);
			return $this; //Cached
		}
		return false;
	}
	public function setCachePath() {
		$this->cachePath = str_replace('/','_',$this->path) . '.html';
		return true;
	}
	public function cachedOrNot() {
		return ((file_exists('cache/' . $this->cachePath )) && ((time() - filemtime('cache/' . $this->cachePath)) >= CACHETIME ) && ((time() - filemtime('cache/' . $this->cachePath)) >= DROPCACHETIME ) );
	}
	public function saveContent() {
		$this->fileMan = @fopen('./cache/' . $this->cachePath,'w+');
		return ((@fwrite($this->fileMan,$this->content) !== false) && (@fclose($this->fileMan) === true));
	}
	
}

class compiledPage extends cachedPage {
	public function __construct($path) {
		
		
		
	}
	
	
}

class dropSiteStatus {
	public $start;
	public $configsLoaded = false;
	public $curDirWritable = false;
	public $cacheThere = false;
	public $cacheWritable = false;
	public $pagesDir = false;
	
	public $toString = 'Uninitiated';
	public $allSwitches = array('configsLoaded','curDirWritable','cacheThere','cacheWritable','pagesDir');
	
	
	public function __construct($__input = array() ) {
		if (count($__input) > 0) {
			$this->iterateInput($__input);
		} else {
			$this->iterateInput( array($this->installConfig(),
						@is_writable('./'),
						(is_dir('./cache/') === true),
						@is_writable('./cache/'),
						(is_dir('./pages/') === true)
						));
		}
		$this->startTime = microtime(true);
	}
	public function iterateInput($__inBools) {
		for ($iter = 0; $iter < count($__inBools); $iter++)
			$this->{$this->allSwitches[$iter]} = $__inBools[$iter];
	}
	
	public function set($__which,$val) {
		if (property_exists('dropSiteStatus',$__which)) {
			$this->{$__which} = $val;
			return true;
		} return false;
	}
	public function isNow($__string) {
		$this->toString = $__string;
	}
	
	public function installConfig() {
		if (!file_exists('my-site.php') ? false : require('my-site.php') ) {
			return ((defined('DROPBOX_APP_KEY')) &&
					(defined('DROPBOX_APP_SECRET')) &&
					(defined('DROPBOX_SITE_SECRET')) &&
					(defined('DROPBOX_MY_KEY')) &&
					(defined('DROPBOX_MY_SECRET')) &&
					(defined('YOUR_NAME')) &&
					(defined('YOUR_MAIL')) &&
					(defined('SITE_TITLE')) );
		} 
		return false;
	}
	
	public function __toArray() {
		return array($this->configsLoaded,
			$this->curDirWritable,
			$this->cacheThere,
			$this->cacheWritable,
			$this->pagesDir );
	}
	public function __toString() {
		$__secs = (microtime(true) - $this->start);
		return '<p><span' . ( $__secs > 1.5 ? ' color="red"' : '') . '>' . $__secs . '</span>: ' . $this->toString . '</p>';
	}
}

class dropSite {
	public $dropBox;
	public $dropPage;
	
	public function __construct($dbStatus, $path) {
		//$this->status = new dropSiteStatus();
		//public $allSwitches = array('configsLoaded','curDirWritable','cacheThere','cacheWritable','pagesDir');
		switch($dbStatus->__toArray()) {
			case array(false,false,true,true,false):
			case array(false,false,true,false,false):
					//Just try to load the requested cached page.  That's all we can do in this case. 
					if ($path != 'install') {
						$this->dropPage = new cachedPage($path);
						if ($this->dropPage == false) {
							$this->dropPage = new cachedPage('four-o-four');
						}
						break;
					}
			case array(false,true,false,false,true):
					//We could import the old.. but.. eh. 
			
			break;
			case array(true,true,false,false,true): //Mend: Recreate the cache, re-sync Pages
			case array(true,true,false,false,false): //Mend: Recreate the cache, re-sync Pages.-
				$dbStatus->@mkdir('./cache'); 
			case array(true,true,true,false,true): //Mend: try chmod+w to the directory.
			case array(true,true,true,false,false): //Mend: Chmod+w, re-sync pages.
				$dbStatus->cacheWritable = chmod('./cache/', 777);
			case array(true,true,true,true,false): //Mend: re-sync Pages, Sold, Buy, Portfolio.
					//site is not OK. However, it can be mended.
					
					
					
			case array(true,true,true,true,true):
					//Site is A-OK, and ready to load pages.
					
					
					
				break;
				break;
			case array(false,true,false,false,false):
					//We can write the config-- no remainders looking suspicious. Go for installation.
					
				break;
			case array(true,true,false,true,true): //Impossible (CacheWritable can't exist w/o cacheThere)				
			case array(false,false,false,false,false):
			default:
				//The only time this should load is when 'curDirWritable' is false. This's a major breaker.
				
				break;
			
		}
		
		
		
		
	}
	
	public function __conffstruct($path) {
		$this->status->isNow("Configurations checked; now to act upon it");
		
		
		
		//We have a lot of checks to go through, so .. let's do it.
		if ($this->installConfig()) {  //Can we load the configuration files?
			$this->cStat(0,'Install config present.');
			//Config files loaded.
			if (@is_writable('./')) { //Can we write to folders and files here?
				$this->cStat(0,'Install dir is writable.');
				//Yes, we can add folders or files as necessary.
				if () {  //Is the cache there?
					$this->cStat(0,'Cache exists.');
					//OK.  Try to load from cache.
				} else {
					//There's no cache folder.
					$this->cStat(-1,'Uhoh.. no cache.');
				}
				$this->makePage($path);
			} else {
				$this->dropBox = false;
				$this->makePage($path);
				//TODO: Mail user about this problem.
			}
			//End "Folder's writable/not"
			
		} else {
			$this->cStat(-1,'No configuration installable. Shiiii--');
			//No configuration file!!
			if (@is_writable('./')) { //Can we at least continue with the configuration?
				$this->cStat(0,'We can at least continue with installation. Dir is writable.');
				if ((!is_null($_POST)) && (array_key_exists('something',$_POST))) {  //Is the user trying to install?
					$this->cStat(0,'All required data is there to complete the installation.');
					
					//TODO: ... Create the files as provided by the user.
					return $this;
				} else {
					$this->cStat(2,'Displaying the installation page for the user.');
					//TODO: ... the installation page should load here. :P
					return $this;
				}
				
			} else {
				//No. Disaster time.
				if ((is_dir('./cache/'))) {  //Is there a cached folder where we can pull content?
					$this->cStat(0,'Cache exists. Try to get the data..');
					//Yes, a cached folder to check for content.
					$this->dropBox = false;
					$this->makePage($path);
					
				} else {
					//We can't configure anything.  Only tell the user what to do if $pagePath is = 'install'.
					
					
				}
				
			}
			
			
		}
	}
	
	public function makePage($path) {
		$this->dropPage = new cachedPage ($path);
		if (($this->dropPage === false) && ($this->dropBox !== false)) { //Did the attempt to load the cached page fail?
			$this->cStat(0,'Trying to generate the page for viewing.');
			//There's no fresh cached version of this page.  Try to gen.
			
			$this->dropPage = new compiledPage($path, $this->dropBox);
			
			//TODO: Gen PHP script goes here.
		} else {
			$this->cStat(1,'Cached file for this page deemed appropriate.');
			return true;
		}
		//end "Cache is there, and writable"
	}
	
	public function cStat($_newInt, $_newStat) {
		$this->status = array($_newInt, $_newStat);
	}
	
}


/*
	Installation procedure:
	
	Check for the following in order:
		my-site.php --> None? Needs setup.   If getting a HTTP post, do setup methods based on that content.
		cache directory --> None?  Attempt to make it.   Can't make it?  Dispatch a warning e-mail.  error message.
		-- break --
		layout folder. -->  None?  Try to load the page from 'cache'.  Whether success or not, dispatch warning mail.
		pages / folio / sold / buy folder. -->  None?  Try to load page from 'cache'.  Whether success or not, dispatch warning.	
*/



/*
	Programming logic, here:
	Check for cached pages.
		1. If there are cached pages, check to see whether or not we have a dropbox config.
			1a. We have a dropbox config that connected within the last hour.
				1ai.  If an hour has passed, treat this page as if there was no cached version. (goto '2')
				1aii.  If an hour has not passed, deliver the cached version.
			1b. No dropbox config.
				1ai.  Without a dropbox config, we can't update the site.  Dispatch an e-mail to the user.
				1aii.  Deliver the cached version of this page.
					1aii1.  If no cached version, 404.
					1aii2.  If no cached 404, simply return the header of 404.
					
		2.  There is no cached version.
			2a. See if we have enough information to generate the site with.
				2ai.  If we have no Configuration data, check to see if we have any Dropbox Data.
					2ai1.  If we have no Dropbox Data, this is the initial setup.
					2ai2.  If we have Dropbox Data, this's still initial setup, but the Dropbox config part.
					2ai3.  If we have some split between some of A and some of B, work out the progress with the user.
					
				2aii.  If we have Configuration data & dropbox data, see if we can generate a page from this content.
					2aii1.  The necessary files exist to display the page. (EG: a portfolio folder with jpgs/mp3s, /pages/ files, layout files.)
						Do it!
					2aii2.  The file doesn't exist..
						2aii2I.  Try to load the four-o-four page.  
						2aii2II.  Hopefully _that_ goes well. If there's no content for the four-o-four page, 404 without content.
						
*/




function page_config() {
	return base64_decode('PCFET0NUWVBFIGh0bWw+DQo8aHRtbD4NCgk8aGVhZD48dGl0bGU+U2V0IHVwIFNvcmVUaHVtYidzIERyb3Bib3ggQ01TPC90aXRsZT4NCgk8bGluayBocmVmPSJodHRwOi8vZm9udHMuZ29vZ2xlYXBpcy5jb20vY3NzP2ZhbWlseT1BdmVyYWdlIiByZWw9InN0eWxlc2hlZXQiIHR5cGU9InRleHQvY3NzIj4NCjxzdHlsZT4NCg0KYm9keSB7IGZvbnQtZmFtaWx5OiAnQXZlcmFnZScsIHNlcmlmOyB9DQoNCi5zbW9vdGhCZyB7DQoJYmFja2dyb3VuZDogI2YwZjlmZjsNCgliYWNrZ3JvdW5kOiB1cmwoZGF0YTppbWFnZS9zdmcreG1sO2Jhc2U2NCxQRDk0Yld3Z2RtVnljMmx2YmowaU1TNHdJaUEvUGdvOGMzWm5JSGh0Ykc1elBTSm9kSFJ3T2k4dmQzZDNMbmN6TG05eVp5OHlNREF3TDNOMlp5SWdkMmxrZEdnOUlqRXdNQ1VpSUdobGFXZG9kRDBpTVRBd0pTSWdkbWxsZDBKdmVEMGlNQ0F3SURFZ01TSWdjSEpsYzJWeWRtVkJjM0JsWTNSU1lYUnBiejBpYm05dVpTSStDaUFnUEd4cGJtVmhja2R5WVdScFpXNTBJR2xrUFNKbmNtRmtMWFZqWjJjdFoyVnVaWEpoZEdWa0lpQm5jbUZrYVdWdWRGVnVhWFJ6UFNKMWMyVnlVM0JoWTJWUGJsVnpaU0lnZURFOUlqQWxJaUI1TVQwaU1DVWlJSGd5UFNJd0pTSWdlVEk5SWpFd01DVWlQZ29nSUNBZ1BITjBiM0FnYjJabWMyVjBQU0l3SlNJZ2MzUnZjQzFqYjJ4dmNqMGlJMll3WmpsbVppSWdjM1J2Y0MxdmNHRmphWFI1UFNJeElpOCtDaUFnSUNBOGMzUnZjQ0J2Wm1aelpYUTlJakV3TUNVaUlITjBiM0F0WTI5c2IzSTlJaU5oTVdSaVptWWlJSE4wYjNBdGIzQmhZMmwwZVQwaU1TSXZQZ29nSUR3dmJHbHVaV0Z5UjNKaFpHbGxiblErQ2lBZ1BISmxZM1FnZUQwaU1DSWdlVDBpTUNJZ2QybGtkR2c5SWpFaUlHaGxhV2RvZEQwaU1TSWdabWxzYkQwaWRYSnNLQ05uY21Ga0xYVmpaMmN0WjJWdVpYSmhkR1ZrS1NJZ0x6NEtQQzl6ZG1jKyk7DQoJYmFja2dyb3VuZDogLW1vei1saW5lYXItZ3JhZGllbnQodG9wLCAgI2YwZjlmZiAwJSwgI2ExZGJmZiAxMDAlKTsNCgliYWNrZ3JvdW5kOiAtd2Via2l0LWdyYWRpZW50KGxpbmVhciwgbGVmdCB0b3AsIGxlZnQgYm90dG9tLCBjb2xvci1zdG9wKDAlLCNmMGY5ZmYpLCBjb2xvci1zdG9wKDEwMCUsI2ExZGJmZikpOw0KCWJhY2tncm91bmQ6IC13ZWJraXQtbGluZWFyLWdyYWRpZW50KHRvcCwgICNmMGY5ZmYgMCUsI2ExZGJmZiAxMDAlKTsNCgliYWNrZ3JvdW5kOiAtby1saW5lYXItZ3JhZGllbnQodG9wLCAgI2YwZjlmZiAwJSwjYTFkYmZmIDEwMCUpOw0KCWJhY2tncm91bmQ6IC1tcy1saW5lYXItZ3JhZGllbnQodG9wLCAgI2YwZjlmZiAwJSwjYTFkYmZmIDEwMCUpOw0KCWJhY2tncm91bmQ6IGxpbmVhci1ncmFkaWVudCh0byBib3R0b20sICAjZjBmOWZmIDAlLCNhMWRiZmYgMTAwJSk7DQoJZmlsdGVyOiBwcm9naWQ6RFhJbWFnZVRyYW5zZm9ybS5NaWNyb3NvZnQuZ3JhZGllbnQoIHN0YXJ0Q29sb3JzdHI9JyNmMGY5ZmYnLCBlbmRDb2xvcnN0cj0nI2ExZGJmZicsR3JhZGllbnRUeXBlPTAgKTsNCn0NCi5yb3VuZENvcm5lcnMgew0KCS1tb3otYm9yZGVyLXJhZGl1czogMTVweDsNCglib3JkZXItcmFkaXVzOiAxNXB4Ow0KCXBhZGRpbmc6IDEwcHg7DQoJbWFyZ2luOiAxMHB4Ow0KfQ0KaDEsIGgyLCBoMywgaDQsIGg1IHsNCgljb2xvcjogIzE4NjVCMjsgcGFkZGluZzogMHB4OyBtYXJnaW46IDBweDsNCn0NCi5zbW9vdGhCZyBoMSwuc21vb3RoQmcgIGgyLC5zbW9vdGhCZyAgaDMsLnNtb290aEJnICBoNCwuc21vb3RoQmcgIGg1IHsNCgljb2xvcjogIzAwMzU3MjsgcGFkZGluZzogMHB4OyBtYXJnaW46IDBweDsNCglmb250LXdlaWdodDogbm9ybWFsOw0KfQ0KLm5vRmxvYXQgeyBjbGVhcjogYm90aDsgfQ0KLmZsb2F0UmlnaHQgeyBmbG9hdDogcmlnaHQ7IH0NCg0KPC9zdHlsZT4NCgk8L2hlYWQ+DQoJPGJvZHk+DQoJCTxoMT5TZXQgdXAgeW91ciBEcm9wYm94LWJhc2VkIEdhbGxlcnkgQXJ0U2l0ZTwvaDE+DQoJCTxoNT4qY291Z2ggY291Z2ggbWFkZSBieSA8YSBocmVmPSJodHRwOi8vc29yZXRodW1iLm1lIj5Tb3JlVGh1bWI8L2E+LCBjb3VnaCo8L2g1Pg0KCQk8Zm9ybSBtZXRob2Q9IlBPU1QiIGFjdGlvbj0iIj4NCgkJCTxkaXYgY2xhc3M9InNtb290aEJnIHJvdW5kQ29ybmVycyBub0Zsb2F0Ij4NCgkJCQk8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ieW91ck5hbWUiIGlkPSJ5b3VyTmFtZSIgdmFsdWU9IiUlX3lvdXJOYW1lXyUlIiBjbGFzcz0icmVxdWlyZWQgZmxvYXRSaWdodCIgLz4NCgkJCQk8aDM+PGxhYmVsIGZvcj0ieW91ck5hbWUiPllvdXIgTmFtZTo8L2xhYmVsPjwvaDM+DQoJCQkJPHAgY2xhc3M9Im5vRmxvYXQiPkFzIGEgbm90ZSwgYW55IHNwYWNlcyBpbiB5b3VyIG5hbWUgaGVscCB0aGlzIHNpdGUgZmlndXJlIG91dCB5b3VyICdmaXJzdCcgYW5kICdsYXN0JyBuYW1lLjwvcD4NCgkJCTwvZGl2Pg0KCQkJDQoJCQk8ZGl2IGNsYXNzPSJzbW9vdGhCZyByb3VuZENvcm5lcnMgbm9GbG9hdCI+DQoJCQkJPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InlvdXJNYWlsIiBpZD0ieW91ck1haWwiIHZhbHVlPSIlJV95b3VyTWFpbF8lJSIgY2xhc3M9ImVtYWlsIHJlcXVpcmVkIGZsb2F0UmlnaHQiLz4NCgkJCQk8aDM+PGxhYmVsIGZvcj0ieW91ck1haWwiPllvdXIgZU1haWw6PC9sYWJlbD48L2gzPg0KCQkJPC9kaXY+DQoJCQkNCgkJCTxkaXYgY2xhc3M9InNtb290aEJnIHJvdW5kQ29ybmVycyBub0Zsb2F0Ij4NCgkJCQk8aW5wdXQgdHlwZT0idGV4dCIgbmFtZT0ic2l0ZVRpdGxlIiBpZD0ic2l0ZVRpdGxlIiB2YWx1ZT0iJSVfc2l0ZVRpdGxlXyUlIiBjbGFzcz0icmVxdWlyZWQgZmxvYXRSaWdodCIgc3R5bGU9Im1pbi13aWR0aDogMjAwcHg7IiAvPg0KCQkJCTxoMz48bGFiZWwgZm9yPSJzaXRlVGl0bGUiPllvdXIgc2l0ZSdzIHRpdGxlOjwvbGFiZWw+PC9oMz4NCgkJCTwvZGl2Pg0KCQkJDQoJCQk8YnV0dG9uIGNsYXNzPSJzbW9vdGhCZyByb3VuZENvcm5lcnMgbm9GbG9hdCIgdHlwZT0ic3VibWl0Ij4NCgkJCQk8Yj5TdWJtaXQhPC9iPg0KCQkJPC9idXR0b24+DQoJCTwvZm9ybT4NCgkJDQo8c2NyaXB0IHNyYz0iaHR0cHM6Ly9hamF4Lmdvb2dsZWFwaXMuY29tL2FqYXgvbGlicy9qcXVlcnkvMS43LjIvanF1ZXJ5Lm1pbi5qcyI+PC9zY3JpcHQ+DQo8c2NyaXB0IHNyYz0iaHR0cDovL2FqYXguYXNwbmV0Y2RuLmNvbS9hamF4L2pxdWVyeS52YWxpZGF0ZS8xLjkvanF1ZXJ5LnZhbGlkYXRlLm1pbi5qcyI+PC9zY3JpcHQ+DQo8c2NyaXB0IHR5cGU9InRleHQvamF2YXNjcmlwdCI+DQoNCjwvc2NyaXB0Pg0KPC9ib2R5PjwvaHRtbD4=');
}
function file_configged() {
	return base64_decode('PD9waHANCi8qDQoJTmF0aGFuL1NvcmVUaHVtYidzIEV4cGxvc2l2ZSBEcm9wYm94IENNUy4NCgkJVGhpcyBpcyBhIGNvbmZpZ3VyYXRpb24gZm9yICUlX3lvdXJOYW1lXyUlJ3Mgc2l0ZS4NCgkJc29yZXRodW1iQGdtYWlsLmNvbQ0KCQlodHRwOi8vdGhlbmF0ZXdheS5uZXQgLCBodHRwOi8vdGhlbmF0ZXdvcmsuY29tICwgaHR0cDovL3NvcmV0aHVtYi5tZQ0KCQ0KCUZPUiBSRUZFUkVOQ0U6DQoJCURvdWJsZSB0aGUgJXMgaW4gdGhlIGJlbG93IGtleSB3b3JkcyB0byBtYWtlIHRoYXQgc2hvdyB1cC4uLg0KCQktICVfeW91ck5hbWVfJSB0dXJucyBpbnRvIHlvdXIgbmFtZSwgd2hpY2ggd2FzICUlX3lvdXJOYW1lXyUlIHdoZW4gSSBzZXQgdXAgdGhlIHNpdGUuDQoJCS0gJV95b3VyTWFpbF8lIGlzIHRoZSBlLW1haWwgZm9yIHlvdXIgd2ViIGZvcm0sIHdoaWNoIHdhcyAlJV95b3VyTWFpbF8lJSB3aGVuIEkgc2V0IHVwIHRoZSBzaXRlLg0KCQktICVfc2l0ZVRpdGxlXyUgaXMgdGhlIHRpdGxlIG9mIHlvdXIgc2l0ZSwgd2hpY2ggd2FzICUlX3NpdGVUaXRsZV8lJSB3aGVuIEkgc2V0IHVwIHRoZSBzaXRlLg0KCQkNCiovDQoNCi8vJV95b3VyTmFtZV8lDQpkZWZpbmUoJ1lPVVJfTkFNRScsPDw8J05PV0RPQycNCiUlX3lvdXJOYW1lXyUlDQpOT1dET0M7DQopOw0KDQovLyVfeW91ck1haWxfJQ0KZGVmaW5lKCdZT1VSX01BSUwnLDw8PCdOT1dET0MnDQolJV95b3VyTWFpbF8lJQ0KTk9XRE9DOw0KKTsNCg0KLy8lX3NpdGVUaXRsZV8lDQpkZWZpbmUoJ1NJVEVfVElUTEUnLDw8PCdOT1dET0MnDQolJV9zaXRlVGl0bGVfJSUNCk5PV0RPQzsNCik7DQoNCj8+');
}













?>