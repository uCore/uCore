<?php

class tabledef_NewsTags extends uTableDef implements iLinkTable {
	public function SetupFields() {
		$this->AddField('link_id',ftNUMBER);
		$this->AddField('news_id',ftNUMBER);
		$this->AddField('tag',ftVARCHAR,150);

		$this->SetPrimaryKey('link_id');
		$this->SetIndexField('news_id');
	}
}

class tabledef_NewsTable extends uTableDef {
	public $tablename = 'news';
	public function SetupFields() {
		$this->AddField('news_id',ftNUMBER);
		$this->AddField('time',ftDATE);
		$this->AddField('heading',ftVARCHAR,150);
		$this->AddField('description',ftTEXT);
		$this->AddField('text',ftLONGTEXT);
		$this->AddField('image',ftIMAGE);
		$this->AddField('archive',ftBOOL);
		
		$this->AddField('featured',ftBOOL);

		$this->SetPrimaryKey('news_id');
	}
}

class module_NewsAdmin extends uListDataModule implements iAdminModule {
	public function GetSortOrder() { return -8800; }
	public function SetupParents() {
		$this->AddParent('/');
	}
	public function GetTitle() { return 'Articles'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->AddField('time','time','news','Posted');
		$this->AddField('heading','heading','news','Title');
		$this->AddField('featured','featured','news','Featured',itCHECKBOX);
		
		$this->AddFilter('time',ctGTEQ,itDATE);
		$this->AddFilter('time',ctLTEQ,itDATE);
	}
	public function RunModule() {
		$this->ShowData();
	}
}

class module_NewsAdminDetail extends uSingleDataModule implements iAdminModule {
	public function SetupParents() {
		$this->AddParent('module_NewsAdmin','news_id','*');
	}
	public function GetTitle() { return 'Edit Article'; }
	public function GetOptions() { return ALLOW_DELETE | ALLOW_FILTER | ALLOW_ADD | NO_NAV | ALLOW_EDIT; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->CreateTable('tags','tabledef_NewsTags','news','news_id');
		$this->AddField('time','time','news','Post Date',itDATE);
		$this->AddField('heading','heading','news','Title',itTEXT);
		$this->AddField('description','description','news','Description',itTEXT);
		$this->FieldStyles_Set('description',array('width'=>'60%'));
		$this->AddField('tags','tag','tags','tags',itTEXT);
		$this->AddPreProcessCallback('tags',array($this,'ppTag'));
		$this->FieldStyles_Set('tags',array('width'=>'60%'));
		
		$this->AddField('featured','featured','news','Featured',itCHECKBOX);
		
		$this->AddField('text','text','news','Content',itHTML);
		$this->FieldStyles_Set('text',array('width'=>'100%','height'=>'10em'));
		$this->AddField('curr_image','image','news','Current Image');
		$this->FieldStyles_Set('curr_image',array('height'=>100));
		$this->AddField('image','image','news','Image',itFILE);
		$this->AddField('archive','archive','news','Archive',itCHECKBOX);
	}
	public function ppTag($v) {
		if (!is_array($v)) return $v;
		sort($v);
		return implode(',',$v);
	}
	public function UpdateField($fieldAlias,$newValue,&$pkVal=NULL) {
		if ($fieldAlias == 'tags') {
			$newValue = explode(',',$newValue);
			foreach ($newValue as $k=>$v) $newValue[$k] = trim($v);
		}
		parent::UpdateField($fieldAlias,$newValue,$pkVal);
	}
	public function RunModule() {
		$this->ShowData();
	}
}

class module_NewsRSS extends uDataModule {
	public function SetupParents() { 
		$this->SetRewrite(true);
	}

	public static $uuid = 'news-rss';
	public function GetTitle() { return 'Articles RSS'; }
	public function GetOptions() { return ALLOW_FILTER; }
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->CreateTable('tags','tabledef_NewsTags','news','news_id');
		$this->AddField('time','time','news','Date',itDATE);
		$this->AddField('heading','heading','news','Heading',itTEXT);
		$this->AddField('description','description','news','Description',itTEXT);
		$this->AddField('tags','tag','tags','tags',itTEXT);
		$this->AddPreProcessCallback('tags',array($this,'ppTag'));
		$this->AddField('text','text','news','Content',itHTML);
		$this->AddField('image','image','news','Image',itFILE);
		$this->AddField('archive','archive','news','Archive',itCHECKBOX);
		$this->AddField('featured','featured','news','Featured');
		$this->AddOrderBy('time','desc');
	}
	public function ppTag($v) {
		if (!is_array($v)) return $v;
		sort($v);
		return implode(',',$v);
	}
	public function RunModule() {
		utopia::CancelTemplate();
		$dom = utopia::GetDomainName();
		$siteName = modOpts::GetOption('site_name');

		$items = '';
		$obj = utopia::GetInstance('module_NewsDisplay');
		$pubDate = null;
		$dataset = $this->GetDataset();
		while (($row = $dataset->fetch())) {
			$crop = (strlen($row['text']) > 100) ? substr($row['text'],0,100).'...' : '';
			$link = htmlentities('http://'.$dom.$obj->GetURL(array('news_id'=>$row['news_id'])));
			$img = '';
			if ($row['image']) $img = "\n".'  <media:thumbnail width="150" height="150" url="'.htmlentities('http://'.$dom.uBlob::GetLink(get_class($this),'image',$row['news_id']).'?w=150&h=150').'"/>';
			$updated = date('r',strtotime($row['time']));
			if (!$pubDate || (strtotime($row['time']) > $pubDate)) $pubDate = strtotime($row['time']);
			$items .= <<<FIN
 <item>
  <title>{$row['heading']}</title>
  <description>{$row['description']}</description>
  <link>{$link}</link>
  <guid isPermaLink="false">{$link}</guid>
  <pubDate>{$updated}</pubDate>{$img}
 </item>
FIN;
		}
		$pubDate = date('r',$pubDate);

		header('Content-Type: application/rss+xml',true);
		$self = htmlentities('http://'.$dom.$_SERVER['REQUEST_URI']);
		echo <<<FIN
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
 <atom:link href="{$self}" rel="self" type="application/rss+xml" />
 <title>{$siteName} News Feed</title>
 <description>Latest news from {$siteName}</description>
 <link>http://{$dom}</link>
 <lastBuildDate>{$pubDate}</lastBuildDate>
 <language>en-gb</language>
 <ttl>15</ttl>
{$items}
</channel></rss>
FIN;
	}
}

class module_NewsTags extends uDataModule {
	public function GetTabledef() { return 'tabledef_NewsTags'; }
	public function GetOptions() { return DISTINCT_ROWS; }
	public function SetupFields() {
		$this->CreateTable('tags');
		$this->AddField('tag','tag','tags','Tag');
		$this->grouping = array('tag');
	}
	public function SetupParents() {}
	public function RunModule() {}
}

class module_NewsDisplay extends uDataModule {
	public function GetTitle() { return 'Latest News'; }
	public function SetupParents() {
		$this->SetRewrite('{heading}-{news_id}',true);
		modOpts::AddOption('news_per_page','News Articles Per Page','News',2);
		modOpts::AddOption('news_widget_archive','News Archive Widget','News','news-archive');
		modOpts::AddOption('news_widget_article','News Article Widget','News','news-article');
		
		uSearch::AddSearchRecipient(__CLASS__,array('heading','text'),'heading','text');
	}
	public function GetTabledef() { return 'tabledef_NewsTable'; }
	public function SetupFields() {
		$this->CreateTable('news');
		$this->CreateTable('tags','tabledef_NewsTags','news','news_id');
		$this->AddField('time','time','news','time');
		$this->SetFieldType('time',ftDATE);
		$this->AddField('heading','heading','news','heading');
		$this->AddField('text','text','news','text');
		$this->AddField('description','description','news','description');
		$this->AddField('image','image','news','image');
		$this->AddField('featured','featured','news','Featured');
		
		$this->AddField('tags','tag','tags','tags',itTEXT);
		$this->AddPreProcessCallback('tags',array($this,'ppTag'));
		
		$this->AddFilter('news_id',ctEQ,itNONE);
		if (isset($_GET['news_id'])) {
			$this->AddFilter('news_id',ctEQ,itNONE,$_GET['news_id']);
		}
		
		$this->AddFilter('{time} <= NOW()',ctCUSTOM);
		
		if (isset($_GET['tags']))
			$this->AddFilter('tags',ctEQ,itNONE,$_GET['tags']);
		$this->AddOrderBy('time','desc');
	}
	public static $uuid = 'news';
	public function RunModule() {
		uEvents::AddCallback('ProcessDomDocument',array($this,'ProcessDomDocument'));
		if (isset($_GET['news_id'])) {
			$rec = $this->LookupRecord($_GET['news_id']);
			if (!$rec) utopia::PageNotFound();
			utopia::SetTitle($rec['heading']);
			utopia::SetDescription($rec['description']);

			echo '{widget.'.modOpts::GetOption('news_widget_article').'}';
			return;
		}
		if (isset($_GET['tags'])) utopia::SetTitle('Latest '.ucwords($_GET['tags']).' News');
		echo '{widget.'.modOpts::GetOption('news_widget_archive').'}';
	}
	public function ppTag($v) {
		if (!is_array($v)) return $v;
		sort($v);
		return implode(', ',$v);
	}
	public function ProcessDomDocument($o,$e,$doc) {
		$head = $doc->getElementsByTagName('head')->item(0);
		
		// add OG protocol
		if (isset($_GET['news_id'])) {
			$rec = $this->LookupRecord($_GET['news_id']);
			$img = 'http://'.utopia::GetDomainName().uBlob::GetLink(get_class($this),'image',$rec['news_id']);
			$meta = $doc->createElement('meta'); $meta->setAttribute('property','og:title'); $meta->setAttribute('content',$rec['heading']); $head->appendChild($meta);
			$meta = $doc->createElement('meta'); $meta->setAttribute('property','og:url'); $meta->setAttribute('content','http://'.utopia::GetDomainName().$_SERVER['REQUEST_URI']); $head->appendChild($meta);
			$meta = $doc->createElement('meta'); $meta->setAttribute('property','og:image'); $meta->setAttribute('content',$img); $head->appendChild($meta);
			$meta = $doc->createElement('meta'); $meta->setAttribute('property','og:site_name'); $meta->setAttribute('content',modOpts::GetOption('site_name')); $head->appendChild($meta);
			$meta = $doc->createElement('meta'); $meta->setAttribute('property','og:description'); $meta->setAttribute('content',$rec['description']); $head->appendChild($meta);
		}

		// add RSS link
		$rssobj = utopia::GetInstance('module_NewsRSS');
		$link = $doc->createElement('link');
		$link->setAttribute('rel','alternate');
		$link->setAttribute('type','application/atom+xml');
		$link->setAttribute('title',modOpts::GetOption('site_name').' News Feed');
		$link->setAttribute('href',$rssobj->GetURL());
		$head->appendChild($link);
	}
}
