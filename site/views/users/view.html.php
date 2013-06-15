<?php
/*------------------------------------------------------------------------

# TZ Portfolio Extension

# ------------------------------------------------------------------------

# author    DuongTVTemPlaza

# copyright Copyright (C) 2012 templaza.com. All Rights Reserved.

# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL

# Websites: http://www.templaza.com

# Technical Support:  Forum - http://templaza.com/Forum

-------------------------------------------------------------------------*/
 
//no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class TZ_PortfolioViewUsers extends JViewLegacy
{
    protected $item = null;
    function __construct($config = array()){
        $this -> item   = new stdClass();
        parent::__construct($config);
    }
    function display($tpl = null){
        $doc    = JFactory::getDocument();

        $state  = $this -> get('State');
        $params = $state -> params;
        $list   = $this -> get('Users');

        $csscompress    = null;
        if($params -> get('css_compression',0)){
            $csscompress    = '.min';
        }

        $jscompress         = new stdClass();
        $jscompress -> extfile  = null;
        $jscompress -> folder   = null;
        if($params -> get('js_compression',1)){
            $jscompress -> extfile  = '.min';
            $jscompress -> folder   = '/packed';
        }

        if($list){
            if($params -> get('tz_show_count_comment',1) == 1){
                require_once(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'HTTPFetcher.php');
                require_once(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'readfile.php');
                $fetch       = new Services_Yadis_PlainHTTPFetcher();
            }
            
            //Get Plugins Model
            $pmodel = JModelLegacy::getInstance('Plugins','TZ_PortfolioModel',array('ignore_request' => true));

            foreach($list as $row){
                $tzRedirect = $params -> get('tz_portfolio_redirect','p_article'); //Set params for $tzRedirect
                $itemParams = new JRegistry($row -> attribs); //Get Article's Params
                //Check redirect to view article
                if($itemParams -> get('tz_portfolio_redirect')){
                    $tzRedirect = $itemParams -> get('tz_portfolio_redirect');
                }

                if($tzRedirect == 'p_article'){
                    $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getPortfolioArticleRoute($row -> slug,$row -> catid), true ,-1);
                }
                else{
                    $contentUrl =JRoute::_(TZ_PortfolioHelperRoute::getArticleRoute($row -> slug,$row -> catid), true ,-1);
                }

                if($params -> get('tz_comment_type','disqus') == 'facebook'){
                    if($params -> get('tz_show_count_comment',1) == 1){

                        $url    = 'http://graph.facebook.com/?ids='.$contentUrl;

                        $content    = $fetch -> get($url);

                        if($content)
                            $content    = json_decode($content -> body);

                        if(isset($content -> $contentUrl -> comments))
                            $row -> commentCount   = $content -> $contentUrl  -> comments;
                        else
                            $row -> commentCount   = 0;
                    }
                }
                if($params -> get('tz_comment_type','disqus') == 'disqus'){
                    if($params -> get('tz_show_count_comment',1) == 1){
                        $url        = 'https://disqus.com/api/3.0/threads/listPosts.json?api_secret='.$params -> get('disqusApiSecretKey')
                                      .'&forum='.$params -> get('disqusSubDomain','templazatoturials')
                                      .'&thread=link:'.$contentUrl
                                      .'&include=approved';

                        $content    = $fetch -> get($url);

                        if($content)
                            $content    = json_decode($content -> body);
                        if($content){
                            $content    = $content -> response;
                            if(is_array($content)){
                                $row -> commentCount	= count($content);
                            }
                            else{
                                $row -> commentCount   = 0;
                            }
                        }
                    }
                }

                if ($params->get('show_intro', 1)==1) {
                    $row -> text = $row -> introtext;
                }

                JPluginHelper::importPlugin('content');

                $dispatcher	= JDispatcher::getInstance();

                    //
                // Process the content plugins.
                //

                $row->event = new stdClass();
                $results = $dispatcher->trigger('onContentPrepare', array ('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));

                $results = $dispatcher->trigger('onContentAfterTitle', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->afterDisplayTitle = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentBeforeDisplay', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->beforeDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentAfterDisplay', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->afterDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onContentTZPortfolioVote', array('com_tz_portfolio.users', &$row, &$params, $state -> get('offset')));
                $row->event->TZPortfolioVote = trim(implode("\n", $results));

                //Get plugin Params for this article
                $pmodel -> setState('filter.contentid',$row -> id);
                $pluginItems            = $pmodel -> getItems();
                $pluginParams           = $pmodel -> getParams();
                $row -> pluginparams    = clone($pluginParams);

                JPluginHelper::importPlugin('tz_portfolio');
                $results   = $dispatcher -> trigger('onTZPluginPrepare',array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));

                $results = $dispatcher->trigger('onTZPluginAfterTitle', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZafterDisplayTitle = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onTZPluginBeforeDisplay', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZbeforeDisplayContent = trim(implode("\n", $results));

                $results = $dispatcher->trigger('onTZPluginAfterDisplay', array('com_tz_portfolio.users', &$row, &$params,&$pluginParams, $state -> get('offset')));
                $row->event->TZafterDisplayContent = trim(implode("\n", $results));
            }
        }
        
        $this -> assign('listsUsers',$list);
        $this -> assign('authorParams',$params);
        $this -> assign('params',$params);
        $this -> assign('mediaParams',$params);
        $this -> assign('pagination',$this -> get('Pagination'));
        $author = JModelLegacy::getInstance('User','TZ_PortfolioModel');
        $author = $author -> getUserId(JRequest::getInt('created_by'));
        $this -> assign('listAuthor',$author);
        $params = $this -> get('state') -> params;

        $model  = JModelLegacy::getInstance('Portfolio','TZ_PortfolioModel',array('ignore_request' => true));
        $model -> setState('params',$params);
        $model -> setState('filter.userId',$state -> get('users.id'));
        $this -> assign('char',$state -> get('char'));
        $this -> assign('availLetter',$model -> getAvailableLetter());
        
        if($params -> get('tz_use_image_hover',1) == 1):
            $doc -> addStyleDeclaration('
                .tz_image_hover{
                    opacity: 0;
                    position: absolute;
                    top:0;
                    left: 0;
                    transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                   -moz-transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                   -webkit-transition: opacity '.$params -> get('tz_image_timeout',0.35).'s ease-in-out;
                }
                .tz_image_hover:hover{
                    opacity: 1;
                    margin: 0;
                }
            ');
        endif;

        if($params -> get('tz_use_lightbox',1) == 1){
            $doc -> addCustomTag('<script type="text/javascript" src="components/com_tz_portfolio/js'.
                $jscompress -> folder.'/jquery.fancybox.pack'.$jscompress -> extfile.'.js"></script>');
            $doc -> addStyleSheet('components/com_tz_portfolio/css/fancybox'.$csscompress.'.css');

            $width      = null;
            $height     = null;
            $autosize   = null;
            if($params -> get('tz_lightbox_width')){
                if(preg_match('/%|px/',$params -> get('tz_lightbox_width'))){
                    $width  = 'width:\''.$params -> get('tz_lightbox_width').'\',';
                }
                else
                    $width  = 'width:'.$params -> get('tz_lightbox_width').',';
            }
            if($params -> get('tz_lightbox_height')){
                if(preg_match('/%|px/',$params -> get('tz_lightbox_height'))){
                    $height  = 'height:\''.$params -> get('tz_lightbox_height').'\',';
                }
                else
                    $height  = 'height:'.$params -> get('tz_lightbox_height').',';
            }
            if($width || $height){
                $autosize   = 'fitToView: false,autoSize: false,';
            }
            $doc -> addCustomTag('<script type="text/javascript">
                jQuery(\'.fancybox\').fancybox({
                    type:\'iframe\',
                    openSpeed:'.$params -> get('tz_lightbox_speed',350).',
                    openEffect: "'.$params -> get('tz_lightbox_transition','elastic').'",
                    '.$width.$height.$autosize.'
		            helpers:  {
                        title : {
                            type : "inside"
                        },
                        overlay : {
                            opacity:'.$params -> get('tz_lightbox_opacity',0.75).',
                        }
                    }
                });
                </script>
            ');
        }

        $doc -> addStyleSheet('components/com_tz_portfolio/css/tzportfolio'.$csscompress.'.css');

        // Add feed links
		if ($params->get('show_feed_link', 1)) {
			$link = '&format=feed&limitstart=';
			$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
			$doc->addHeadLink(JRoute::_($link . '&type=rss'), 'alternate', 'rel', $attribs);
			$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
			$doc->addHeadLink(JRoute::_($link . '&type=atom'), 'alternate', 'rel', $attribs);
		}
        
        parent::display($tpl);

    }


    protected function FindItemId($_userid=null){
        $app		= JFactory::getApplication();
        $menus		= $app->getMenu('site');
        $active     = $menus->getActive();
        if($_userid){
            $userid    = intval($_userid);
        }

        $component	= JComponentHelper::getComponent('com_tz_portfolio');
        $items		= $menus->getItems('component_id', $component->id);

        if($this -> params -> get('menu_active') && $this -> params -> get('menu_active') != 'auto'){
            return $this -> params -> get('menu_active');
        }

        foreach ($items as $item)
        {
            if (isset($item->query) && isset($item->query['view'])) {
                $view = $item->query['view'];

                if (isset($item -> query['created_by'])) {
                    if ($item->query['created_by'] == $userid) {
                        return $item -> id;
                    }
                }
                else{
                    if($item -> home == 1){
                        $homeId = $item -> id;
                    }
                }
            }
        }

        if(!isset($active -> id)){
            return $homeId;
        }

        return $active -> id;
    }
}