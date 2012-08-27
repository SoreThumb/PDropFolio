PDropFolio
==========

A simple PHP-based portfolio website that uses the PHP Dropbox API to sync galleries, MP3s, and pages. Supports Twitter RSS feed plugin.

ENVISIONED USE
==========

I. INSTALLATION 

Someone who has never built a site before, is given .htaccess, index.php, and the 'required' folder from 'At Site'.
After that, they access http://THEIR/INSTALL/LOCATION/install/ which prompts the PDropBox to install.
PDropBox asks for three things:
1.  To create an app at the user's dropbox account, and to bring the dropbox-provided keys to PDropBox,
2.  To initially configure the site using the user's settings,
3.  Patience for the user to edit the layout files.

II. RESULTS

After the above, the user's Dropbox sandbox folder has a few folders created to represent parts of the site,
as well as files within those folders to represent the entities.  EG: pages/ folder contains Page Files.htm or
This Is A Page Title.txt .  Portfolio loads JPGs for EXIF data, MP3s for ID3 tag data, or can figure out more
if there's a matching .txt file matching the JPG or MP3.  Additional support will show how to include videos.

Furthermore, the site is regularly cached and checked VS dropbox... but ONLY when the cache has expired via a
constant.  Each time the index.php file is loaded, it checks for previous installs, and then syncs to dropbox
via those installs.  SYNCING IS ONLY ONE-WAY.  Files are never deleted unless the Dropbox delta says so.  The
downside is that images may lag behind HTML and pages, but only slightly.  This shouldn't matter to crawlers,
though.

Finally, there should be a few pre-packaged template codes that get turned into pre-created layout files (EG
Making a contact form show up) or dynamically-created parts, such as a Twitter feed.

III. CAVEATS

I have no idea if Dropbox will hate me or you after this. :(


~Nathan B
nate@thenateway.net