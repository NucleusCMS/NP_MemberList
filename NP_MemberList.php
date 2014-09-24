<?php

/**
  * 
	Usage: <%MemberList(blogname)%> or <%MemberList(blogid)%>
	- blogname = display the members of the specified short blogname
	- blogid = display the members of the specified blogid
		Example:
		To display only the team members of myblog: <%MemberList(myblog)%>
		To display only the team members of blogid = 1: <%MemberList(1)%>
		To display the team members of the default blog: <%MemberList(default)%>
		To display the member list of the current blog: <%MemberList(current)%>

	Not using parameters:
	- To display all members regarless of team: <%MemberList%>
	
	add second parameter for options
	     valid values
		 - noteam (lists members not on specified blog team, ie <%MemberList(myblog,noteam)%>)

	Options:
	- Header
	- Formatting for each member which is displayed
	- Footer

	History:
	v1.0 - initial release by Legolas | http://www.legolasweb.nl/
	released 9 November 2005
	v1.5b - modified by PiyoPiyoNaku | http://www.renege.net
	released 28 January 2007
	- Made to show only team members of the specified blog id.
	v1.6 - PiyoPiyoNaku | http://www.renege.net
	released 3 February 2007
	- can choose between FancyURL or normal URL
	v1.7 - PiyoPiyoNaku | http://www.renege.net
	released 4 February 2007
	- compatibility with NP_Alias
	- <%MemberList(current)%> will display the member list of the current blog
	v1.8 - PiyoPiyoNaku | http://www.renege.net
	released 2 March 2007
	- using Nucleus createMemberLink function
	- delete FancyURL or normal URL function and skinvar parameter
	- can use short blogname instead of blogid for skinvar parameter
	- option to put header/footer for memberlist
	- can change how the member list is displayed
	v1.81 - PiyoPiyoNaku | http://www.renege.net
	released 11 March 2007
	 - code cleaning regarding compatibility with NP_Alias [Must use the new NP_Alias v1.3 to make the compatibility works]
	v1.82 - PiyoPiyoNaku
	released 12 March 2007
	- language support for Japanese-utf8
	v1.83 - PiyoPiyoNaku <http://mixi.jp/show_friend.pl?id=16761236>
	released 13 March 2007
	- %avatar to get avatar from NP_Profile (thanks ftruscott!) (^_^)
	v1.84
	released 29 December 2007
	- %pm to get PM link from NP_PrivateMessaging (Armon Toubman)
	v1.85 - ftruscot 
	released 6 May 2010
	- add second parameter noteam option to list all members not on given team
  */
 
class NP_MemberList extends NucleusPlugin {
 
   function getEventList() { return array(); }
   function getName() { return 'MemberList'; }
   function getAuthor()  { return 'Legolas | PiyoPiyoNaku'; }
   function getURL()  { return 'http://www.renege.net/'; }
   function getVersion() { return '1.86'; }
   function getDescription() {
      return _MLIST_DESC;
   }
   function supportsFeature($feature) { return in_array ($feature, array ('SqlTablePrefix', 'SqlApi'));}
   function getMinNucleusVersion()    { return '350'; }

	function init() {
		$language = str_replace( array('\\','/'), '', getLanguageName());
		if ($language == "japanese-utf8")
		{
			define(_MLIST_DESC,				"メンバリスト。 スキンへの記述： &lt;%MemberList%&gt; 詳細を指定： &lt;%MemberList(ブログモード)%&gt; ブログモード: current, default, ブログの短縮名, ブログID");
			define(_MLIST_OPT1,				"一覧のヘッダ");
			define(_MLIST_OPT2,				"一覧の本体");
			define(_MLIST_OPT3,				"一覧のフッタ");
		}
		else
		{
			define(_MLIST_DESC,				"Member list. Skinvar： &lt;%MemberList%&gt; More specific: &lt;%MemberList(BlogMode)%&gt; BlogMode: current, default, BlogShortname, BlogID");
			define(_MLIST_OPT1,				"Header");
			define(_MLIST_OPT2,				"List Formatting");
			define(_MLIST_OPT3,				"Footer");
		}
	}
 
   function install() {
		$this->createOption('header',_MLIST_OPT1,'textarea','<ul>');
		$this->createOption('format',_MLIST_OPT2,'textarea','<li>%avatar <a href="%memberlink" title="Member: %realname">%name</a></li>');
		$this->createOption('footer',_MLIST_OPT3,'textarea','</ul>');
   }

   // skinvar plugin can have a blogname as second parameter
   function doSkinVar($skinType) {
		global $blog, $CONF, $manager;

		$tbl_team = sql_table('team');

		$parameters = func_get_args();

		if (!$parameters[1])
		{
			$blog_id = ""; $other = " ORDER by mnumber";
		}
		else if ($parameters[1] == "current")
		{
			if ($parameters[2] == "noteam") {
				$blog_id = " WHERE mnumber NOT IN(SELECT tmember FROM $tbl_team WHERE tblog=".$blog->getID().")";
				$other = " GROUP by mnumber"; 
			}
			else {
				$blog_id = " JOIN $tbl_team ON mnumber = tmember WHERE tblog=".$blog->getID();
				$other = " GROUP by mnumber"; 
			}
		}
		// show members in default blog's team
		else if ($parameters[1] == "default")
		{ 
			if ($parameters[2] == "noteam") {
				$blog_id = " WHERE mnumber NOT IN(SELECT tmember FROM $tbl_team WHERE tblog=".$CONF['DefaultBlog'].")";
				$other = " GROUP by mnumber"; 
			}
			else {
				$blog_id = " JOIN $tbl_team ON mnumber = tmember WHERE tblog=".$CONF['DefaultBlog'];        
				$other = " GROUP by mnumber";
			}
		}
		// show members from the selected blogid
		else if (is_numeric($parameters[1]))
		{ 
			if ($parameters[2] == "noteam") {
				$blog_id = " WHERE mnumber NOT IN(SELECT tmember FROM $tbl_team WHERE tblog=".$parameters[1].")";
				$other = " GROUP by mnumber"; 
			}
			else {
				$blog_id = " JOIN $tbl_team ON mnumber = tmember WHERE tblog=".$parameters[1];
				$other = " GROUP by mnumber"; 
			}
		}
		// show members from the selected blogname
		else {
			$selectedbid = getBlogIDFromName($parameters[1]);
			if ($parameters[2] == "noteam") {
				$blog_id = " WHERE mnumber NOT IN(SELECT tmember FROM $tbl_team WHERE tblog=".$selectedbid.")";
				$other = " GROUP by mnumber"; 
			}
			else {
				$blog_id = " JOIN $tbl_team ON mnumber = tmember WHERE tblog=".$selectedbid;
				$other = " GROUP by mnumber";
			}
		}
	 
		$tmpl = $this->getOption('format');

		echo $this->getOption('header'); //display header
		$query = sprintf('SELECT mnumber, mname, mrealname FROM %s %s %s', sql_table('member'),$blog_id,$other);
        $membersresult = sql_query($query);
		$out = "";
        while ($row = sql_fetch_object($membersresult)) {
			
			$link = createMemberLink($row->mnumber);
			$myname = $row->mname;

			$pluginName = 'NP_Alias';
			if ($manager->pluginInstalled($pluginName))
			{
				$pluginObject =& $manager->getPlugin($pluginName);
				if ($pluginObject) {
					$myname = $pluginObject->getAliasfromMemberName($myname);
				}
			}

			$out .= str_replace("%name", $myname, $tmpl);
			$out = str_replace("%memberlink", $link, $out);
			$out = str_replace("%realname", $row->mrealname, $out);
			$avatarlink = $this->getAvatar($row->mnumber);
			if ($avatarlink) {
				$out = str_replace("%avatar", "<img src=\"" . $avatarlink . "\" alt=\"" . $myname . "\" />", $out);
			} else {
				$out = str_replace("%avatar", "", $out);
			}
			$pmlink = $this->getPMLink($row->mnumber);
			if($pmlink) {
				$out = str_replace("%pm", "<a href=\"".$pmlink."\" alt=\"Send PM to ".$myname."\">PM</a>", $out);
			} else {
				$out = str_replace("%pm", "", $out);
			}
		}     
		echo $out;
		echo $this->getOption('footer'); //display footer
   }

   function getAvatar($fid) {
      global $manager, $CONF;
      $fid = intval($fid);
      if ($manager->pluginInstalled('NP_Profile')) {
         $plugin =& $manager->getPlugin('NP_Profile');
      }
      if (isset($plugin)) {
         if (version_compare("2.11",$plugin->getVersion())) {
            $variable = $plugin->getValue($fid,'avatar');
                if ($variable == '') {
                    $variable = $plugin->default['file']['default'];
                }
                else {
                    $variable = $CONF['MediaURL'].$variable;
                }
            return $variable;
         }
         else {
            return $plugin->getAvatar($fid);
         }
      }
      else return '';
   }
      
   function getPMLink($fid) {
      global $manager;
      $fid = intval($fid);
      if ($manager->pluginInstalled('NP_PrivateMessaging')) {
         $plugin =& $manager->getPlugin('NP_PrivateMessaging');
      }
      if (isset($plugin)) {
         $composeLink = $plugin->composeLink($fid);
         return $composeLink;
      }
      else return '';
   }
   
}
