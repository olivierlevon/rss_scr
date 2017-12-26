<?php
require_once(dirname(__FILE__).'/zp-core/global-definitions.php');
require_once(ZENFOLDER . "/template-functions.php");
require_once(dirname(__FILE__). '/'.ZENFOLDER.'/functions-rss.php');
require_once(ZENFOLDER .'/'.PLUGIN_FOLDER . "/image_album_statistics.php");

$host = getRSSHost();
$channeltitle = "ZenPhoto 1.4 RSS Generator for Google ScreenSaver (from Picasa) by Olivier Levon [www.photopara.net/galerie]"; //getRSSChanneltitle();
$protocol = SERVER_PROTOCOL;
if ($protocol == 'https_admin') {
	$protocol = 'http';
}
$locale = getRSSLocale();
$validlocale = getRSSLocaleXML();
$modrewritesuffix = getRSSImageAndAlbumPaths("modrewritesuffix");
require_once(ZENFOLDER .  "/lib-MimeTypes.php");
header('Content-Type: application/xml');
$rssmode = getRSSAlbumsmode();
$albumfolder = getRSSAlbumnameAndCollection("albumfolder");
$collection = getRSSAlbumnameAndCollection("collection");
$albumname = getRSSAlbumTitle();
$albumpath = getRSSImageAndAlbumPaths("albumpath");
$imagepath = getRSSImageAndAlbumPaths("imagepath");
$size = getRSSImageSize();
$items = getOption('feed_items'); // # of Items displayed on the feed
$gallery = new Gallery();
?>
<?php echo '<'.'?xml version="1.0" encoding="utf-8"'.'?>'; ?>
<rss version="2.0"      
                        xmlns:photo="http://www.pheed.com/pheed/"
                        > 
<channel>

<title><?php echo html_encode($channeltitle.' '.strip_tags($albumname)); ?></title>
<link><?php echo $protocol."://".$host.WEBPATH; ?></link>

<generator>ZenPhoto 1.4 RSS Generator for Google ScreenSaver (from Picasa) by Olivier Levon [www.photopara.net/galerie]</generator>

	<?php
	if ($rssmode == "albums") {
		$result = getAlbumStatistic($items,getOption("feed_sortorder_albums"),$albumfolder);
	} else {
		$result = getImageStatistic($items,getOption("feed_sortorder"),$albumfolder,$collection);
	}
	foreach ($result as $item) {
		if($rssmode != "albums") {
			$ext = getSuffix($item->filename);
			$albumobj = $item->getAlbum();
			$itemlink = $host.WEBPATH.$albumpath.pathurlencode($albumobj->name).$imagepath.pathurlencode($item->filename).$modrewritesuffix;
			$fullimagelink = $host.WEBPATH."/albums/".pathurlencode($albumobj->name)."/".$item->filename;
			$imagefile = "albums/".$albumobj->name."/".$item->filename;
			$thumburl = '<img border="0" src="'.$protocol.'://'.$host.$item->getCustomImage($size, NULL, NULL, NULL, NULL, NULL, NULL, TRUE).'" alt="'.get_language_string(get_language_string($item->get("title"),$locale)) .'" /><br />';
			$itemcontent = '<![CDATA[<a title="'.html_encode(get_language_string($item->get("title"),$locale)).' in '.html_encode(get_language_string($albumobj->get("title"),$locale)).'" href="'.$protocol.'://'.$itemlink.'">'.$thumburl.'</a>' . get_language_string(get_language_string($item->get("desc"),$locale)) . ']]>';
			$videocontent = '<![CDATA[<a title="'.html_encode(get_language_string($item->get("title"),$locale)).' in '.html_encode(get_language_string($albumobj->getTitle(),$locale)).'" href="'.$protocol.'://'.$itemlink.'"><img src="'.$protocol.'://'.$host.$item->getThumb().'" alt="'.get_language_string(get_language_string($item->get("title"),$locale)) .'" /></a>' . get_language_string(get_language_string($item->get("desc"),$locale)) . ']]>';
			$datecontent = '<![CDATA[<br />Date: '.zpFormattedDate(DATE_FORMAT,$item->get('mtime')).']]>';
		} else {
			$galleryobj = new Gallery();
			$albumitem = new Album($galleryobj, $item['folder']);
			$totalimages = $albumitem->getNumImages();
			$itemlink = $host.WEBPATH.$albumpath.pathurlencode($albumitem->name);
			$thumb = $albumitem->getAlbumThumbImage();
			$thumburl = '<img border="0" src="'.$thumb->getCustomImage($size, NULL, NULL, NULL, NULL, NULL, NULL, TRUE).'" alt="'.html_encode(get_language_string($albumitem->get("title"),$locale)) .'" />';
			$title =  get_language_string($albumitem->get("title"),$locale);
			if(true || getOption("feed_sortorder_albums") == "latestupdated") {
				$filechangedate = filectime(ALBUM_FOLDER_SERVERPATH.internalToFilesystem($albumitem->name));
				$latestimage = query_single_row("SELECT mtime FROM " . prefix('images'). " WHERE albumid = ".$albumitem->getAlbumID() . " AND `show` = 1 ORDER BY id DESC");
				$count = db_count('images',"WHERE albumid = ".$albumitem->getAlbumID() . " AND mtime = ". $latestimage['mtime']);
				if($count == 1) {
					$imagenumber = sprintf(gettext('%s (1 new image)'),$title);
				} else {
					$imagenumber = sprintf(gettext('%1$s (%2$s new images)'),$title,$count);
				}
				$itemcontent = '<![CDATA[<a title="'.$title.'" href="'.$protocol.'://'.$itemlink.'">'.$thumburl.'</a>'.
						'<p>'.html_encode($imagenumber).'</p>'.html_encode(get_language_string($albumitem->get("desc"),$locale)).']]>';
				$videocontent = '';
				$datecontent = '<![CDATA['.sprintf(gettext("Last update: %s"),zpFormattedDate(DATE_FORMAT,$filechangedate)).']]>';
			} else {
				if($totalimages == 1) {
					$imagenumber = sprintf(gettext('%s (1 image)'),$title);
				} else {
					$imagenumber = sprintf(gettext('%1$s (%2$s images)'),$title,$totalimages);
				}
				$itemcontent = '<![CDATA[<a title="'.html_encode($title).'" href="'.$protocol.'://'.$itemlink.'">'.$thumburl.'</a>'.html_encode(get_language_string($albumitem->get("desc"),$locale)).']]>';
				$datecontent = '<![CDATA['.sprintf(gettext("Date: %s"),zpFormattedDate(DATE_FORMAT,$albumitem->get('mtime'))).']]>';
			}
			$ext = getSuffix($thumb->filename);
		}
		$mimetype = getMimeString($ext);
		?>
		
<item>
	<title>
<?php
if($rssmode != "albums") {
	html_encode(printf('%1$s (%2$s)', get_language_string($item->get("title"),$locale), get_language_string($albumobj->get("title"),$locale)));
} else {
	echo html_encode($imagenumber);
}
?>
	</title>

	<link><?php echo '<![CDATA['.$serverprotocol.'://'.$itemlink.']]>';?></link>

	<pubDate>
<?php
	if($rssmode != "albums") {
		echo date("r",strtotime($item->get('date')));
	} else {
		echo date("r",strtotime($albumitem->get('date')));
	}
?>
	</pubDate>
	<photo:imgsrc><?php echo '<![CDATA[http://'.$fullimagelink.' ]]>'; ?></photo:imgsrc>
	<author>Copyright 2008-2012 Olivier Levon</author>
</item>

<pubDate>
	<?php
	if($rssmode != "albums") {
		echo date("r",strtotime($item->get('date')));
	} else {
		echo date("r",strtotime($albumitem->get('date')));
	}
	?>
</pubDate>

<?php } ?>
</channel>
</rss>