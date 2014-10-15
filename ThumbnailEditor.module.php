<?php

require_once(__DIR__ . '/../NetDesign/NetDesign.module.php');

class ThumbnailEditor extends NetDesign {
    const OPERATION_NONE = -1;
    const OPERATION_CANCELLED = 0;
    const OPERATION_UPLOADED = 1;
    const OPERATION_APPLIED = 2;
    const OPERATION_SUBMITTED = 3;
    private $init = array();
    /** @var Thumbnail */
    private $thumb;

    public function __construct() {
        $this->RegisterClassDirectory(cms_join_path($this->GetModulePath(), 'libs'), 'Thumbnail*');
        parent::__construct();
    }

    function GetVersion() {
        return '1.0.0';
    }

    function HasAdmin() {
        return false;
    }

    function Install() {
        // Create database table
        $db = $this->db;
        $table = $this->GetTable();
        $query = "
        CREATE TABLE IF NOT EXISTS `$table` (
            `site_id` varchar(50) NOT NULL,
            `module` varchar(50) NOT NULL,
            `uid` varchar(100) NOT NULL,
            `configWidth` integer(11),
            `configHeight` integer(11),
            `filename` varchar(255) NOT NULL,
            `originalWidth` integer(11),
            `originalHeight` integer(11),
            `cropWidth` integer(11),
            `cropHeight` integer(11),
            `cropX` integer(11),
            `cropY` integer(11),
            PRIMARY KEY (`site_id`, `module`, `uid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $db->Execute($query);
        return false;
    }

    function Uninstall() {
        // Remove database table
        $db = $this->db;
        $dict = NewDataDictionary($db);
        $sql = $dict->DropTableSQL($this->GetTable());
        $ret = $dict->ExecuteSQLArray($sql);
        return false;
    }

    public function EditorInit(NetDesign $module, NetDesign $owner, $action, $actionid, $uid, $width = 200, $height = 120, $params = array()) {
        $this->init = array(
            'module' => $module,
            'owner' => $owner,
            'action' => $action,
            'actionid' => $actionid,
            'uid' => $uid,
            'width' => (int)$width,
            'height' => (int)$height,
            'params' => (array)$params
        );
        $this->thumb = new Thumbnail($owner, $this->init['uid'], $this->init['width'], $this->init['height']);
    }

    public function EditorProcess() {
        $params = $this->init['params'];
        if (array_key_exists('cancel', $params)) {
            return ThumbnailEditor::OPERATION_CANCELLED;
        } elseif (array_key_exists('upload', $params)) {
            $key = $this->init['actionid'] . 'image';
            $file = $_FILES[$key];
            if ($file['error'] == UPLOAD_ERR_OK) {
                $this->thumb->Upload($file['tmp_name'], $file['name']);
                return ThumbnailEditor::OPERATION_UPLOADED;
            }
        } elseif (array_key_exists('apply', $params) || array_key_exists('submit', $params)) {
            list($width, $height, $x, $y) = explode(',', $params['config']);
            $this->thumb->Crop($width, $height, $x, $y);
            if (array_key_exists('submit', $params)) return ThumbnailEditor::OPERATION_SUBMITTED;
            else return ThumbnailEditor::OPERATION_APPLIED;
        }
        return ThumbnailEditor::OPERATION_NONE;
    }

    public function EditorDisplay() {
        cms_module_Lang($this);
        /** @var NetDesign $module */
        $module = $this->init['module'];
        $action = $this->init['action'];
        $actionid = $this->init['actionid'];
        $params = $this->init['params'];
        unset($params['submit']);
        unset($params['apply']);
        unset($params['cancel']);
        unset($params['config']);
        unset($params['upload']);
        $thumb = $this->thumb;
        $this->AssignLang();
        $this->smarty->assign('thumb', $thumb);
        // Calculate minimum and maximum dimensions
        $width = $thumb->GetOriginalWidth();
        $height = $thumb->GetOriginalHeight();
        $ratio = max($thumb->GetConfigWidth() / $width, $thumb->GetConfigHeight() / $height);
        $this->smarty->assign('image', array('minwidth' => (int)($width * $ratio), 'minheight' => (int)($height * $ratio), 'maxwidth' => $width, 'maxheight' => $height));
        // Form
        $this->smarty->assign('form', array(
            'start' => $module->CreateFormStart($actionid, $action, '', 'post', 'multipart/form-data', false, '', $params),
            'submit' => $module->CreateInputSubmit($actionid, 'submit', $this->Lang('submit')),
            'cancel' => $module->CreateInputSubmit($actionid, 'cancel', $this->Lang('cancel')),
            'apply' => $module->CreateInputSubmit($actionid, 'apply', $this->Lang('apply')),
            'input' => null,
            'input' => $module->CreateInputHidden($actionid, 'config', implode(',', array($thumb->GetCropWidth(), $thumb->GetCropHeight(), $thumb->GetCropX(), $thumb->GetCropY()))),
            'upload' => $module->CreateInputSubmit($actionid, 'upload', $this->Lang('upload')),
            'file' => $module->CreateInputFile($actionid, 'image'),
            'end' => $module->CreateFormEnd()
        ));
        // Stylesheets and javascript
        $css = array('assets/lightbox/css/lightbox.css', 'assets/thumbnail.admin.css');
        $js = array('assets/thumbnail.admin.js', 'assets/lightbox/js/lightbox.min.js');
        $ret = "\n\n";
        foreach($css as $fn) {
            $ret .= sprintf('<link rel="stylesheet" type="text/css" href="%s/%s"></script>', $this->GetModuleURLPath(), $fn);
            $ret .= "\n";
        }
        foreach($js as $fn) {
            $ret .= sprintf('<script type="text/javascript" src="%s/%s"></script>', $this->GetModuleURLPath(), $fn);
            $ret .= "\n";
        }
        $ret .= "\n";
        echo $ret;
        echo $this->smarty->fetch($this->GetFileResource('edit.tpl'));
    }

    /**
     * @param NetDesign $module
     * @param string $uid
     * @return Thumbnail
     */
    public function Load(NetDesign $module, $uid) {
        return new Thumbnail($module, $uid, 0, 0);
    }
}