<?php

/*
   ____  __      __    ___  _  _  ___    __   ____     ___  __  __  ___
  (  _ \(  )    /__\  / __)( )/ )/ __)  /__\ (_  _)   / __)(  \/  )/ __)
   ) _ < )(__  /(__)\( (__  )  (( (__  /(__)\  )(    ( (__  )    ( \__ \
  (____/(____)(__)(__)\___)(_)\_)\___)(__)(__)(__)    \___)(_/\/\_)(___/

   @author          Black Cat Development
   @copyright       2016 Black Cat Development
   @link            http://blackcat-cms.org
   @license         http://www.gnu.org/licenses/gpl.html
   @category        CAT_Core
   @package         CAT_Core

*/

if ( ! class_exists( 'CAT_Helper_Assets' ) )
{

    if ( ! class_exists( 'CAT_Object', false ) ) {
	    @include dirname(__FILE__).'/../Object.php';
	}

	class CAT_Helper_Assets extends CAT_Object
	{

        protected static $loglevel  = \Monolog\Logger::EMERGENCY;
        protected static $mime_map = array(
            'css'   => 'text/css',
            'fonts' => 'application/font-woff',
            'js'    => 'text/javascript',
        );
        protected static $suffix_mime_map = array(
            'woff'  => 'application/font-woff',
            'ttf'   => 'application/font-ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'svg'   => 'image/svg+xml',
            'otf'   => 'application/font-otf',
            'jpg'   => 'image/jpg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
        );
        protected static $allowed_subdirs = array(
            'modules',
            'templates',
            'css',
            'js',
        );
        protected static $suffix_route_map = array(
            'eot'   => 'fonts',
            'woff'  => 'fonts',
            'ttf'   => 'fonts',
        );

        /**
         * serves assets like js, css
         *
         * creates a Content-Type header and echo's the contents of the file(s)
         *
         * @param  array   $params
         * @return void
         **/
//---------------- TODO: minify ------------------------------------------------
        public static function assets()
        {
            $output = '';
            $query  = CAT_Object::router()->getQuery();
            list($ignore,$files) = explode('=',$query);
            $files = explode(',',$files);

            foreach($files as $file) {
#echo "file -$file-\n<br />";
                // remove appendix from $file
                $file    = preg_replace('~\?.*$~','',$file);
                foreach(array(CAT_PATH,CAT_ENGINE_PATH) as $base) {
#echo "base -$base-\n<br />";
                    $fullpath = CAT_Helper_Directory::sanitizePath($base.'/'.$file);
#echo "full1 -$fullpath-<br />";
                    if(!file_exists($fullpath))
                    {
                        foreach(array_values(self::$allowed_subdirs) as $dir) {
                            $fullpath = CAT_Helper_Directory::sanitizePath($base.'/'.$dir.'/'.$file);
#echo "full2 -$fullpath-<br />";
                            if(file_exists($fullpath)) break;
                        }
                    }
                    if(!file_exists($fullpath)) continue;
#echo "full3 -$fullpath-\n<br /><br />";

                    $pathinfo = pathinfo($file);
                    $basedir  = $pathinfo['dirname'];

                    $contents = file_get_contents($fullpath);
                    $pattern = '~\burl\(([^\)].+?)\)~i';
                    #$pattern = '#\burl\(["\']?((?=[.\w])(?!\w+:))#';
                    preg_match_all($pattern,$contents,$matches,PREG_SET_ORDER);

#print_r($matches);
#$string = preg_replace('#\burl\(["\']?(?=[.\w])(?!\w+:)#', '$0' . substr($newDir, strlen($tmp)) . '/', $string);

                    if(count($matches))
                    {
                        for($i=0;$i<count($matches);$i++)
                        {
                             // fix quotes
                             $matches[$i][1] = str_replace(array('"',"'"),array('',''),$matches[$i][1]);
                             // find filename
                             // note: we add a ? to the end to catch notices in case there is no querystring in the path
                             list($filename,$querystring) = explode('?',$matches[$i][1].'?');
                             $suffix   = pathinfo($filename,PATHINFO_EXTENSION);
                             $route    = ( isset(self::$suffix_route_map[$suffix]) ? self::$suffix_route_map[$suffix] : $suffix );
                             $path     = CAT_URL.'/'.$route.'?files='.CAT_Helper_Directory::sanitizePath($basedir.'/'.$matches[$i][1]);
                             $contents = str_replace($matches[$i][0],'src:url('.$path.')',$contents);
                        }
                    }

                    $output  .= "\n".$contents;
                    $suffix   = pathinfo($file,PATHINFO_EXTENSION);
#echo "suffix: $suffix<br />";
                    $mime     = ( isset(self::$suffix_mime_map[$suffix])
                                ? self::$suffix_mime_map[$suffix]
                                : self::$mime_map[$suffix]
                                );
                }
            }
#echo 'Content-Type: '.$mime.'; charset='.CAT_Registry::get('default_charset'),"\n\n";
#exit;
            header('Content-Type: '.$mime.'; charset='.CAT_Registry::get('default_charset'));
            echo $output;
        }   // end function assets()
    }
}