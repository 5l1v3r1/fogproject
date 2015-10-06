<?php
class FOGPageManager Extends FOGBase {
    private $pageTitle;
    private $nodes = array();
    protected $classValue;
    protected $methodValue;
    private $arguments;
    private $plugin_checked;
    private function replaceVariable(&$value) {
        $value = trim(preg_replace('#[^\w]#','_',urldecode(trim($value))));
        return $value;
    }
    public function __construct() {
        parent::__construct();
        $this->classValue = $_REQUEST['node'] ? $this->replaceVariable($_REQUEST['node']) : 'home';
        $this->methodValue = $this->replaceVariable($_REQUEST['sub']);
        $this->HookManager->processEvent('SEARCH_PAGES',array('searchPages'=>&$this->searchPages));
    }
    public function getFOGPageClass() {
        return $this->nodes[$this->classValue];
    }
    public function getFOGPageName() {
        return (string)$this->getFOGPageClass()->name;
    }
    public function getFOGPageTitle() {
        return (string)$this->getFOGPageClass()->title;
    }
    public function isFOGPageTitleEnabled() {
        return (bool)$this->getFOGPageClass()->titleEnabled == true && !empty($this->FOGPageClass()->title);
    }
    public function getSideMenu() {
        if ($this->FOGUser instanceof User && $this->FOGUser->isLoggedIn()) {
            $class = $this->getFOGPageClass();
            $this->FOGSubMenu = $this->getClass('FOGSubMenu');
            foreach ((array)$class->menu AS $link => &$title) $this->FOGSubMenu->addItems($this->classValue,array((string)$title=>(string)$link));
            unset($title);
            if (is_object($class->obj)) {
                foreach ((array)$class->subMenu AS $link => &$title) $this->FOGSubMenu->addItems($this->classValue,array((string)$title=>(string)$link),$class->id,sprintf($this->foglang['SelMenu'],get_class($class->obj)));
                unset($title);
                foreach((array)$class->notes AS $title => $item) $this->FOGSubMenu->addNotes($this->classValue,array((string)$title => (string)$item),$class->id,sprintf($this->foglang[SelMenu],get_class($class->obj)));
                unset($item);
            }
            return sprintf('<div id="sidebar">%s</div>',$this->FOGSubMenu->get($this->classValue));
        }
    }
    public function render() {
        $toRender = in_array($_REQUEST['node'],array('client','schemaupdater')) || in_array($_REQUEST['sub'],array('configure','authorize')) || ($this->FOGUser instanceof User && $this->FOGUser->isLoggedIn());
        if ($toRender) {
            $this->loadPageClasses();
            try {
                $class = $this->getFOGPageClass();
                $method = $this->methodValue;
                if ($this->classValue == 'schemaupdater') $this->methodValue = 'index';
                if (empty($method) || !method_exists($class, $method)) $method = 'index';
                $displayScreen = trim(strtolower($_SESSION['FOG_VIEW_DEFAULT_SCREEN']));
                if (!array_key_exists($this->classValue, $this->nodes)) throw new Exception(_('No FOGPage Class found for this node'));
                if ($_REQUEST[$class->id]) $this->arguments = array('id'=>$_REQUEST[$class->id]);
                if ($this->post) $this->setRequest();
                else $this->resetRequest();
                if ($this->classValue != 'schemaupdater' && $method == 'index' && $displayScreen != 'list' && $this->methodValue != 'list' && method_exists($class, 'search') && in_array($class->node,$this->searchPages)) $method = 'search';
                if ($this->ajax && method_exists($class, $method.'_ajax')) $method = $this->methodValue.'_ajax';
                if ($this->post && method_exists($class, $method.'_post')) $method = $this->methodValue.'_post';
            } catch (Exception $e) {
                $this->debug(_('Failed to Render Page: Node: %s, Error: %s'),array(get_class($class),$e->getMessage()));
            }
            ob_start();
            call_user_func(array($class, $method));
            $this->resetRequest();
            return ob_get_clean();
        }
    }
    private function register($class) {
        if (!$class) die(_('No class value sent'));
        try {
            if (!($class instanceof FOGPage)) throw new Exception($this->foglang['NotExtended']);
            if (!$class->node) throw new Exception(_('No node associated'));
            $this->info('Adding FOGPage: %s, Node: %s',array(get_class($class),$class->node));
            $this->nodes[$class->node] = $class;
        } catch (Exception $e) {
            $this->debug('Failed to add Page: Node: %s, Page Class: %s, Error: $s',array($class->node,get_class($class),$e->getMessage()));
        }
        return $this;
    }
    private function loadPageClasses() {
        if ($this->isLoaded('PageClasses')) return;
        global $Init;
        foreach ($Init->PagePaths AS $i => &$path) {
            $className = null;
            if (file_exists($path)) {
                $iterator = new DirectoryIterator($path);
                foreach ($iterator AS $i => $fileInfo) {
                    $className = null;
                    if ($fileInfo->isDot() || !$fileInfo->isFile() || substr($fileInfo->getFilename(),-10) != '.class.php') continue;
                    $className = substr($fileInfo->getFilename(),0,-10);
                    if (!$className || in_array($className,get_declared_classes())) continue;
                    $vals = $this->getClass('ReflectionClass',$className)->getDefaultProperties();
                    if ($vals['node'] === $this->classValue) {
                        $className = $this->getClass($className);
                        $this->register($className);
                    }
                    unset($vals);
                }
            }
        }
        unset($path);
    }
}
