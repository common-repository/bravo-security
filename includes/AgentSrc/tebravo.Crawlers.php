<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_crawlers' ) )
{
    class tebravo_crawlers
    {
        protected static $listCrawlers = array(
            '.*Java.*outbrain',
            '008\/',
            '192\.comAgent',
            '2ip\.ru',
            '404checker',
            '^bluefish ',
            '^Calypso v\/',
            '^COMODO DCV',
            '^DangDang',
            '^DavClnt',
            '^FDM ',
            '^git\/',
            '^Goose\/',
            '^HTTPClient\/',
            '^Java\/',
            '^Jeode\/',
            '^Jetty\/',
            '^Mget',
            '^Microsoft URL Control',
            '^NG\/[0-9\.]',
            '^NING\/',
            '^PHP\/[0-9]',
            '^RMA\/',
            '^Ruby|Ruby\/[0-9]',
            '^scrutiny\/',
            '^VSE\/[0-9]',
            '^WordPress\.com',
            '^XRL\/[0-9]',
            '^ZmEu',
            'a3logics\.in',
            'A6-Indexer',
            'a\.pr-cy\.ru',
            'Abonti\/',
            'Aboundex',
            'aboutthedomain',
            'Accoona-AI-Agent',
            'acoon',
            'acrylicapps\.com\/pulp',
            'adbeat',
            'AddThis',
            'ADmantX',
            'adressendeutschland',
            'Advanced Email Extractor v',
            'agentslug',
            'AHC',
            'aihit',
            'aiohttp\/',
            'Airmail',
            'akula\/',
            'alertra',
            'alexa site audit',
            'Alibaba\.Security\.Heimdall',
            'allloadin\.com',
            'alyze\.info',
            'amagit',
            'AndroidDownloadManager',
            'Anemone',
            'Ant\.com',
            'Anturis Agent',
            'AnyEvent-HTTP\/',
            'ApacheBench\/',
            'Apache-HttpAsyncClient\/',
            'Apache-HttpClient\/',
            'AportWorm\/[0-9]',
            'AppEngine-Google',
            'Arachmo',
            'arachnode',
            'Arachnophilia',
            'aria2',
            'asafaweb.com',
            'AskQuickly',
            'Astute',
            'asynchttp',
            'autocite',
            'Autonomy',
            'B-l-i-t-z-B-O-T',
            '^b0t$',
            'Backlink-Ceck\.de',
            'Bad-Neighborhood',
            'baidu\.com',
            'baypup\/[0-9]',
            'baypup\/colbert',
            'BazQux',
            'BCKLINKS',
            'BDFetch',
            'BegunAdvertising\/',
            'BigBozz',
            'biglotron',
            'BingLocalSearch',
            'BingPreview',
            'binlar',
            'biNu image cacher',
            'biz_Directory',
            'Blackboard Safeassign',
            'Bloglovin',
            'BlogPulseLive',
            'BlogSearch',
            'Blogtrottr',
            'boitho\.com-dc',
            'BPImageWalker',
            'Braintree-Webhooks',
            'Branch Metrics API',
            'Branch-Passthrough',
            'Brodie\/',
            'Browsershots',
            'BUbiNG',
            'Butterfly\/',
            'BuzzSumo',
            'CAAM\/[0-9]',
            'CakePHP',
            'CapsuleChecker',
            'CaretNail',
            'catexplorador',
            'cb crawl',
            'CC Metadata Scaper',
            'Cerberian Drtrs',
            'CERT\.at-Statistics-Survey',
            'cg-eye',
            'changedetection',
            'ChangesMeter\/',
            'Charlotte',
            'CheckHost',
            'checkprivacy',
            'chkme\.com',
            'CirrusExplorer\/',
            'CISPA Vulnerability Notification',
            'CJNetworkQuality',
            'Clarsentia',
            'clips\.ua\.ac\.be',
            'Cloud mapping experiment',
            'CloudEndure',
            'CloudFlare-AlwaysOnline',
            'Cloudinary\/[0-9]',
            'cmcm\.com',
            'coccoc',
            'CommaFeed',
            'Commons-HttpClient',
            'Comodo SSL Checker',
            'contactbigdatafr',
            'convera',
            'copyright sheriff',
            'Covario-IDS',
            'CrawlForMe\/[0-9]',
            'cron-job\.org',
            'Crowsnest',
            'curb',
            'Curious George',
            'curl',
            'cuwhois\/[0-9]',
            'cybo\.com',
            'DareBoost',
            'DataparkSearch',
            'dataprovider',
            'Daum(oa)?[ \/][0-9]',
            'DeuSu',
            'developers\.google\.com\/\+\/web\/snippet\/',
            'Digg',
            'Dispatch\/',
            'dlvr',
            'DMBrowser',
            'DNS-Tools Header-Analyzer',
            'DNSPod-reporting',
            'docoloc',
            'Dolphin http client\/',
            'DomainAppender',
            'Donuts Content Explorer',
            'dotSemantic',
            'downforeveryoneorjustme',
            'downnotifier\.com',
            'DowntimeDetector',
            'Dragonfly File Reader',
            'drupact',
            'Drupal \(\+http:\/\/drupal\.org\/\)',
            'dubaiindex',
            'EARTHCOM',
            'Easy-Thumb',
            'ec2linkfinder',
            'eCairn-Grabber',
            'ECCP',
            'echocrawl',
            'eContext\/',
            'ElectricMonk',
            'elefent',
            'EMail Exractor',
            'EmailWolf',
            'Embed PHP Library',
            'Embedly',
            'europarchive\.org',
            'evc-batch\/[0-9]',
            'EventMachine HttpClient',
            'Evidon',
            'Evrinid',
            'ExactSearch',
            'ExaleadCloudview',
            'Excel\/',
            'Exif Viewer',
            'Exploratodo',
            'ezooms',
            'facebookexternalhit',
            'facebookplatform',
            'fairshare',
            'Faraday v',
            'fasthttp',
            'Faveeo',
            'Favicon downloader',
            'FavOrg',
            'Feed Wrangler',
            'Feedbin',
            'FeedBooster',
            'FeedBucket',
            'FeedBunch\/[0-9]',
            'FeedBurner',
            'FeedChecker',
            'Feedly',
            'Feedspot',
            'Feedwind\/[0-9]',
            'feeltiptop',
            'Fetch API',
            'Fetch\/[0-9]',
            'Fever\/[0-9]',
            'findlink',
            'findthatfile',
            'FlipboardBrowserProxy',
            'FlipboardProxy',
            'FlipboardRSS',
            'fluffy',
            'flynxapp',
            'forensiq',
            'FoundSeoTool\/[0-9]',
            'free thumbnails',
            'FreeWebMonitoring SiteChecker',
            'Funnelback',
            'G-i-g-a-b-o-t',
            'g00g1e\.net',
            'GAChecker',
            'ganarvisitas\/[0-9]',
            'geek-tools',
            'Genderanalyzer',
            'Genieo',
            'GentleSource',
            'GetLinkInfo',
            'getprismatic\.com',
            'GetURLInfo\/[0-9]',
            'Ghost Inspector',
            'GigablastOpenSource',
            'GIS-LABS',
            'github\.com\/',
            'Go [\d\.]* package http',
            'Go-http-client',
            'gofetch',
            'GomezAgent',
            'gooblog',
            'Goodzer\/[0-9]',
            'Google favicon',
            'Google Keyword Suggestion',
            'Google Keyword Tool',
            'Google Page Speed Insights',
            'Google PP Default',
            'Google Search Console',
            'Google Web Preview',
            'Google-Adwords',
            'Google-Apps-Script',
            'Google-Calendar-Importer',
            'Google-HotelAdsVerifier',
            'Google-HTTP-Java-Client',
            'Google-Publisher-Plugin',
            'Google-SearchByImage',
            'Google-Site-Verification',
            'Google-Structured-Data-Testing-Tool',
            'Google-Youtube-Links',
            'google_partner_monitoring',
            'GoogleDocs',
            'GoogleHC\/',
            'GoogleProducer',
            'Gookey',
            'GoScraper',
            'GoSpotCheck',
            'GoSquared-Status-Checker',
            'gosquared-thumbnailer',
            'GotSiteMonitor',
            'grabify',
            'Grammarly',
            'grouphigh',
            'grub-client',
            'GTmetrix',
            'GuzzleHttp',
            'gvfs\/',
            'HAA(A)?RTLAND http client',
            'Hatena',
            'hawkReader',
            'HEADMasterSEO',
            'HeartRails_Capture',
            'heritrix',
            'historious\/',
            'hledejLevne\.cz\/[0-9]',
            'Holmes',
            'HootSuite Image proxy',
            'Hootsuite-WebFeed\/[0-9]',
            'HostTracker',
            'ht:\/\/check',
            'htdig',
            'HTMLParser\/',
            'http-get',
            'HTTP-Header-Abfrage',
            'http-kit',
            'http-request\/',
            'HTTP-Tiny',
            'HTTP_Compression_Test',
            'http_request2',
            'http_requester',
            'HttpComponents',
            'httphr',
            'HTTPMon',
            'PEAR HTTPRequest',
            'httpscheck',
            'httpssites_power',
            'httpunit',
            'HttpUrlConnection',
            'httrack',
            'hosterstats',
            'huaweisymantec',
            'HubPages.*crawlingpolicy',
            'HubSpot Connect',
            'HubSpot Marketing Grader',
            'HyperZbozi.cz Feeder',
            'i2kconnect\/',
            'ichiro',
            'IdeelaborPlagiaat',
            'IDG Twitter Links Resolver',
            'IDwhois\/[0-9]',
            'Iframely',
            'igdeSpyder',
            'IlTrovatore',
            'ImageEngine\/',
            'Imagga',
            'InAGist',
            'inbound\.li parser',
            'InDesign%20CC',
            'infegy',
            'infohelfer',
            'InfoWizards Reciprocal Link System PRO',
            'Instapaper',
            'inpwrd\.com',
            'Integrity',
            'integromedb',
            'internet_archive',
            'InternetSeer',
            'internetVista monitor',
            'intraVnews',
            'IODC',
            'IOI',
            'iplabel',
            'IPS\/[0-9]',
            'ips-agent',
            'IPWorks HTTP\/S Component',
            'iqdb\/',
            'Irokez',
            'isitup\.org',
            'iskanie',
            'iZSearch',
            'janforman',
            'Jaunt\/',
            'Jigsaw',
            'Jobboerse',
            'Jobg8 URL Monitor',
            'jobo',
            'Jobrapido',
            'JS-Kit',
            'Kaspersky Lab CFR link resolver',
            'KeepRight OpenStreetMap Checker',
            'KeyCDN',
            'Keywords Research',
            'KickFire',
            'KimonoLabs\/',
            'Kml-Google',
            'knows\.is',
            'KOCMOHABT',
            'kouio',
            'kulturarw3',
            'KumKie',
            'L\.webis',
            'Larbin',
            'LayeredExtractor',
            'letsencrypt',
            'LibVLC',
            'libwww',
            'Licorne Image Snapshot',
            'Liferea\/',
            'link checker',
            'Link Valet',
            'link_thumbnailer',
            'LinkAlarm\/',
            'linkCheck',
            'linkdex',
            'LinkExaminer',
            'linkfluence',
            'linkpeek',
            'LinkTiger',
            'LinkWalker',
            'Lipperhey',
            'livedoor ScreenShot',
            'LoadImpactPageAnalyzer',
            'LoadImpactRload',
            'LongURL API',
            'looksystems\.net',
            'ltx71',
            'lua-resty-http',
            'lwp-trivial',
            'lycos',
            'LYT\.SR',
            'mabontland',
            'MagpieRSS',
            'Mail.Ru',
            'MailChimp',
            'Mandrill',
            'MapperCmd',
            'marketinggrader',
            'masscan\/[0-9]',
            'Mediapartners-Google',
            'MegaIndex\.ru',
            'Melvil Rawi\/',
            'MergeFlow-PageReader',
            'Metaspinner',
            'MetaURI',
            'Microsearch',
            'Microsoft-WebDAV-MiniRedir',
            'Microsoft Data Access Internet Publishing Provider Protocol',
            'Microsoft Office ',
            'Microsoft Windows Network Diagnostics',
            'Mindjet',
            'Miniflux',
            'mixdata dot com',
            'mixed-content-scan',
            'Mnogosearch',
            'mogimogi',
            'Mojolicious \(Perl\)',
            'monitis',
            'Monitority\/[0-9]',
            'montastic',
            'MonTools',
            'Moreover',
            'Morning Paper',
            'mowser',
            'MovableType',
            'Mrcgiguy',
            'mShots',
            'MxToolbox\/',
            'MVAClient',
            'nagios',
            'Najdi\.si\/',
            'Needle\/',
            'NETCRAFT',
            'NetLyzer FastProbe',
            'Netpursual',
            'netresearch',
            'NetShelter ContentScan',
            'Netsparker',
            'NetTrack',
            'Netvibes',
            'Neustar WPM',
            'NeutrinoAPI',
            'NewsBlur .*Finder',
            'NewsGator',
            'newsme',
            'newspaper\/',
            'NG-Search',
            'nineconnections\.com',
            'NLNZ_IAHarvester',
            'Nmap Scripting Engine',
            'node-superagent',
            'node\.io',
            'nominet\.org\.uk',
            'Norton-Safeweb',
            'Notifixious',
            'notifyninja',
            'nuhk',
            'nutch',
            'Nuzzel',
            'nWormFeedFinder',
            'Nymesis',
            'Ocelli\/[0-9]',
            'oegp',
            'okhttp',
            'Omea Reader',
            'omgili',
            'OMSC',
            'Online Domain Tools',
            'OpenCalaisSemanticProxy',
            'Openstat\/',
            'OpenVAS',
            'Optimizer',
            'Orbiter',
            'OrgProbe\/[0-9]',
            'Owler',
            'ow\.ly',
            'ownCloud News',
            'OxfordCloudService\/[0-9]',
            'Page Analyzer',
            'Page Valet',
            'page2rss',
            'page_verifier',
            'PagePeeker',
            'Pagespeed\/[0-9]',
            'Panopta',
            'panscient',
            'parsijoo',
            'PayPal IPN',
            'Pcore-HTTP',
            'Pearltrees',
            'peerindex',
            'Peew',
            'PhantomJS\/',
            'PhantomJS Screenshoter',
            'Photon\/',
            'phpcrawl',
            'phpservermon',
            'Pi-Monster',
            'ping\.blo\.gs\/',
            'Pingdom',
            'Pingoscope',
            'PingSpot',
            'pinterest\.com',
            'Pizilla',
            'Ploetz \+ Zeller',
            'Plukkie',
            'PocketParser',
            'Pompos',
            'Porkbun',
            'Port Monitor',
            'postano',
            'PostPost',
            'postrank',
            'PowerPoint\/',
            'Priceonomics Analysis Engine',
            'PritTorrent\/[0-9]',
            'Prlog',
            'probethenet',
            'Project 25499',
            'Promotion_Tools_www.searchenginepromotionhelp.com',
            'prospectb2b',
            'Protopage',
            'proximic',
            'pshtt, https scanning',
            'PTST ',
            'PTST\/[0-9]+',
            'Pulsepoint XT3 web scraper',
            'Python-httplib2',
            'python-requests',
            'Python-urllib',
            'Qirina Hurdler',
            'QQDownload',
            'Qseero',
            'Qualidator.com SiteAnalyzer',
            'Quora Link Preview',
            'Qwantify',
            'Radian6',
            'RankSonicSiteAuditor',
            'Readability',
            'RealPlayer%20Downloader',
            'RebelMouse',
            'redback\/',
            'Redirect Checker Tool',
            'ReederForMac',
            'request\.js',
            'ResponseCodeTest\/[0-9]',
            'RestSharp',
            'RetrevoPageAnalyzer',
            'Riddler',
            'Rival IQ',
            'Robosourcer',
            'Robozilla\/[0-9]',
            'ROI Hunter',
            'RPT-HTTPClient',
            'RSSOwl',
            'safe-agent-scanner',
            'SalesIntelligent',
            'SauceNAO',
            'SBIder',
            'scalaj-http',
            'Scoop',
            'scooter',
            'ScoutJet',
            'ScoutURLMonitor',
            'Scrapy',
            'ScreenShotService\/[0-9]',
            'Scrubby',
            'search\.thunderstone',
            'SearchSight',
            'Seeker',
            'semanticdiscovery',
            'semanticjuice',
            'Semiocast HTTP client',
            'SEO Browser',
            'Seo Servis',
            'seo-nastroj.cz',
            'Seobility',
            'SEOCentro',
            'SeoCheck',
            'SeopultContentAnalyzer',
            'Server Density Service Monitoring',
            'servernfo\.com',
            'Seznam screenshot-generator',
            'Shelob',
            'Shoppimon Analyzer',
            'ShoppimonAgent\/[0-9]',
            'ShopWiki',
            'ShortLinkTranslate',
            'shrinktheweb',
            'SilverReader',
            'SimplePie',
            'SimplyFast',
            'Site-Shot\/',
            'Site24x7',
            'SiteBar',
            'SiteCondor',
            'siteexplorer\.info',
            'SiteGuardian',
            'Siteimprove\.com',
            'Sitemap(s)? Generator',
            'Siteshooter B0t',
            'SiteTruth',
            'sitexy\.com',
            'SkypeUriPreview',
            'Slack\/',
            'slider\.com',
            'slurp',
            'SMRF URL Expander',
            'SMUrlExpander',
            'Snappy',
            'SniffRSS',
            'sniptracker',
            'Snoopy',
            'sogou web',
            'SortSite',
            'sovereign\.ai',
            'spaziodati',
            'Specificfeeds',
            'speedy',
            'SPEng',
            'Spinn3r',
            'spray-can',
            'Sprinklr ',
            'sqlmap',
            'spyonweb',
            'Sqworm',
            'SSL Labs',
            'ssl-tools',
            'StackRambler',
            'Statastico\/',
            'StatusCake',
            'Stratagems Kumo',
            'Stroke.cz',
            'StudioFACA',
            'suchen',
            'summify',
            'Super Monitoring',
            'Surphace Scout',
            'SwiteScraper',
            'Symfony2 BrowserKit',
            'SynHttpClient-Built',
            'Sysomos',
            'Symfony BrowserKit',
            'T0PHackTeam',
            'Tarantula\/',
            'Taringa UGC',
            'Tenon\.io',
            'teoma',
            'terrainformatica\.com',
            'Test Certificate Info',
            'Tetrahedron\/[0-9]',
            'The Drop Reaper',
            'The Expert HTML Source Viewer',
            'theinternetrules',
            'theoldreader\.com',
            'Thumbshots',
            'ThumbSniper',
            'TinEye',
            'Tiny Tiny RSS',
            'TLSProbe\/',
            'topster',
            'touche.com',
            'Traackr.com',
            'TrapitAgent',
            'truwoGPS',
            'TulipChain',
            'tweetedtimes\.com',
            'Tweetminster',
            'Tweezler\/',
            'Twikle',
            'Twingly',
            'ubermetrics-technologies',
            'uclassify',
            'uCrawlr\/',
            'UdmSearch',
            'UniversalFeedParser',
            'Untiny',
            'UnwindFetchor',
            'updated',
            'Upflow',
            'Uptimia',
            'URLChecker',
            'URLitor.com',
            'urlresolver',
            'Urlstat',
            'UrlTrends Ranking Updater',
            'Vagabondo',
            'vBSEO',
            'via ggpht\.com GoogleImageProxy',
            'VidibleScraper\/',
            'visionutils',
            'vkShare',
            'voltron',
            'voyager\/',
            'VSAgent\/[0-9]',
            'VSB-TUO\/[0-9]',
            'VYU2',
            'w3af\.org',
            'W3C-checklink',
            'W3C-mobileOK',
            'W3C_I18n-Checker',
            'W3C_Unicorn',
            'wangling',
            'WatchMouse',
            'WbSrch\/',
            'web-capture\.net',
            'Web-Monitoring',
            'Web-sniffer',
            'Webauskunft',
            'WebCapture',
            'WebClient\/',
            'webcollage',
            'WebCookies',
            'WebCorp',
            'WebDoc',
            'WebFetch',
            'WebImages',
            'WebIndex',
            'webkit2png',
            'webmastercoffee',
            'webmon ',
            'webscreenie',
            'Webshot',
            'Website Analyzer\/',
            'websitepulse agent',
            'websitepulse[+ ]checker',
            'Websnapr\/',
            'Webthumb\/[0-9]',
            'WebThumbnail',
            'WeCrawlForThePeace',
            'WeLikeLinks',
            'WEPA',
            'WeSEE',
            'wf84',
            'wget',
            'WhatsApp',
            'WhatsMyIP',
            'WhatWeb',
            'WhereGoes\?',
            'Whibse',
            'Whynder Magnet',
            'Windows-RSS-Platform',
            'WinHttpRequest',
            'wkhtmlto',
            'wmtips',
            'Woko',
            'Word\/',
            'WordPress\/',
            'wotbox',
            'WP Engine Install Performance API',
            'wprecon\.com survey',
            'WPScan',
            'wscheck',
            'WWW-Mechanize',
            'www\.monitor\.us',
            'XaxisSemanticsClassifier',
            'Xenu Link Sleuth',
            'XING-contenttabreceiver\/[0-9]',
            'XmlSitemapGenerator',
            'xpymep([0-9]?)\.exe',
            'Y!J-(ASR|BSC)',
            'Yaanb',
            'yacy',
            'Yahoo Ad monitoring',
            'Yahoo Link Preview',
            'YahooCacheSystem',
            'YahooYSMcm',
            'YandeG',
            'Yandex(?!Search)',
            'yanga',
            'yeti',
            ' YLT',
            'Yo-yo',
            'Yoleo Consumer',
            'yoogliFetchAgent',
            'YottaaMonitor',
            'yourls\.org',
            'Zao',
            'Zemanta Aggregator',
            'Zend\\\\Http\\\\Client',
            'Zend_Http_Client',
            'zgrab',
            'ZnajdzFoto',
            'ZyBorg',
            '[a-z0-9\-_]*((?<!cu)bot|crawler|archiver|transcoder|spider|uptime|validator|fetcher)',
        );
    }
        
}
?>