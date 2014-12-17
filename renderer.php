<?php
/**
 * Render Plugin for jQuery Lazy Load
 *
 * @author duenni
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_INC . 'inc/parser/xhtml.php';

/**
 * The Renderer
 */
class renderer_plugin_lazyload extends Doku_Renderer_xhtml {

    function canRender($format) {
      return ($format=='xhtml');
    }

    /**
     * Renders internal and external media
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param string $src       media ID
     * @param string $title     descriptive text
     * @param string $align     left|center|right
     * @param int    $width     width of media in pixel
     * @param int    $height    height of media in pixel
     * @param string $cache     cache|recache|nocache
     * @param bool   $render    should the media be embedded inline or just linked
     * @return string
     */
    function _media($src, $title = null, $align = null, $width = null,
                    $height = null, $cache = null, $render = true) {

        $ret = '';

        list($ext, $mime) = mimetype($src);
        if(substr($mime, 0, 5) == 'image') {
            // first get the $title
            if(!is_null($title)) {
                $title = $this->_xmlEntities($title);
            } elseif($ext == 'jpg' || $ext == 'jpeg') {
                //try to use the caption from IPTC/EXIF
                require_once(DOKU_INC.'inc/JpegMeta.php');
                $jpeg = new JpegMeta(mediaFN($src));
                if($jpeg !== false) $cap = $jpeg->getTitle();
                if(!empty($cap)) {
                    $title = $this->_xmlEntities($cap);
                }
            }
            if(!$render) {
                // if the picture is not supposed to be rendered
                // return the title of the picture
                if(!$title) {
                    // just show the sourcename
                    $title = $this->_xmlEntities(utf8_basename(noNS($src)));
                }
                return $title;
            }
            //add image tag prepared for lazy loading
            $ret .= '<img src="'.DOKU_BASE.'lib/plugins/lazyload/small.gif" data-original="'.ml($src, array('w' => $width, 'h' => $height, 'cache' => $cache, 'rev'=>$this->_getLastMediaRevisionAt($src))).'"';
            $ret .= ' class="media'.$align.'"';

            if($title) {
                $ret .= ' title="'.$title.'"';
                $ret .= ' alt="'.$title.'"';
            } else {
                $ret .= ' alt=""';
            }

            if(!is_null($width))
                $ret .= ' width="'.$this->_xmlEntities($width).'"';

            if(!is_null($height))
                $ret .= ' height="'.$this->_xmlEntities($height).'"';

            $ret .= ' />';

        } elseif(media_supportedav($mime, 'video') || media_supportedav($mime, 'audio')) {
            // first get the $title
            $title = !is_null($title) ? $this->_xmlEntities($title) : false;
            if(!$render) {
                // if the file is not supposed to be rendered
                // return the title of the file (just the sourcename if there is no title)
                return $title ? $title : $this->_xmlEntities(utf8_basename(noNS($src)));
            }

            $att          = array();
            $att['class'] = "media$align";
            if($title) {
                $att['title'] = $title;
            }

            if(media_supportedav($mime, 'video')) {
                //add video
                $ret .= $this->_video($src, $width, $height, $att);
            }
            if(media_supportedav($mime, 'audio')) {
                //add audio
                $ret .= $this->_audio($src, $att);
            }

        } elseif($mime == 'application/x-shockwave-flash') {
            if(!$render) {
                // if the flash is not supposed to be rendered
                // return the title of the flash
                if(!$title) {
                    // just show the sourcename
                    $title = utf8_basename(noNS($src));
                }
                return $this->_xmlEntities($title);
            }

            $att          = array();
            $att['class'] = "media$align";
            if($align == 'right') $att['align'] = 'right';
            if($align == 'left') $att['align'] = 'left';
            $ret .= html_flashobject(
                ml($src, array('cache' => $cache), true, '&'), $width, $height,
                array('quality' => 'high'),
                null,
                $att,
                $this->_xmlEntities($title)
            );
        } elseif($title) {
            // well at least we have a title to display
            $ret .= $this->_xmlEntities($title);
        } else {
            // just show the sourcename
            $ret .= $this->_xmlEntities(utf8_basename(noNS($src)));
        }

        return $ret;
    }
}

