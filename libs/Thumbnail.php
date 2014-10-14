<?php

class Thumbnail {
    private $module;
    private $moduleName;
    private $uid;
    private $configWidth;
    private $configHeight;
    private $originalWidth;
    private $originalHeight;
    private $cropWidth;
    private $cropHeight;
    private $cropX;
    private $cropY;
    private $filename;
    private function GetProperty($property, $int = true) {
        $ret = $this->$property;
        if ($int !== false) $ret = (int)$ret;
        return $ret;
    }
    public function __construct(NetDesign $module, $uid, $width = 200, $height = 120) {
        $this->module = $module;
        $this->moduleName = get_class($module);
        $this->uid = $uid;
        $this->configWidth = (int)$width;
        $this->configHeight = (int)$height;
        // Load database entry
        $te = ThumbnailEditor::GetInstance();
        $db = $te->db;
        $table = $te->GetTable();
        $ret = $db->GetArray("SELECT * FROM `$table` WHERE `site_id` = ? AND `module` = ? AND `uid` = ? LIMIT 1", array(
            $te->GetSiteId(), $this->GetModuleName(), $this->GetUid()
        ));
        if (empty($ret)) return;
        foreach($ret[0] as $prop => $value) {
            $string = array('filename');
            $int = array('originalWidth', 'originalHeight', 'cropWidth', 'cropHeight', 'cropX', 'cropY');
            if (!in_array($prop, $string) && !in_array($prop, $int)) continue;
            if (in_array($prop, $int)) $this->$prop = (int)$value;
            else $this->$prop = $value;
        }
    }
    public function GetModule() {
        return $this->GetProperty('module', false);
    }
    public function GetModuleName() {
        return $this->GetProperty('moduleName', false);
    }
    public function GetFileName() {
        return $this->GetProperty('filename', false);
    }
    public function GetUid() {
        return $this->GetProperty('uid', false);
    }
    public function GetConfigWidth() {
        return $this->GetProperty('configWidth');
    }
    public function GetConfigHeight() {
        return $this->GetProperty('configHeight');
    }
    public function GetOriginalWidth() {
        return $this->GetProperty('originalWidth');
    }
    public function GetOriginalHeight() {
        return $this->GetProperty('originalHeight');
    }
    public function GetCropWidth() {
        return $this->GetProperty('cropWidth');
    }
    public function GetCropHeight() {
        return $this->GetProperty('cropHeight');
    }
    public function GetCropX() {
        return $this->GetProperty('cropX');
    }
    public function GetCropY() {
        return $this->GetProperty('cropY');
    }
    public function Crop($width, $height, $x, $y) {
        $this->cropWidth = (int)$width;
        $this->cropHeight = (int)$height;
        $this->cropX = (int)$x;
        $this->cropY = (int)$y;
        // Save to the database
        $te = ThumbnailEditor::GetInstance();
        $db = $te->db;
        $table = $te->GetTable();
        $db->Execute("REPLACE INTO `$table` VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(
            $te->GetSiteId(), $this->GetModuleName(), $this->GetUid(), $this->GetConfigWidth(), $this->GetConfigHeight(),
            $this->GetFileName(), $this->GetOriginalWidth(), $this->GetOriginalHeight(), $this->GetCropWidth(),
            $this->GetCropHeight(), $this->GetCropX(), $this->GetCropY()
        ));
        // Create the thumbnail
        $img = $this->GetOriginalPath();
        list($width, $height) = getimagesize($img);
        $nwidth = $this->GetCropWidth();
        $nheight = $this->GetCropHeight();
        $im = imagecreatefromstring(file_get_contents($img));
        $im2 = imagecreatetruecolor((int)$nwidth, (int)$nheight);
        #printf("Resizing to %dx%d\n", $nwidth, $nheight);
        imagecopyresampled($im2, $im, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);
        #printf("Cropping at %d,%d to a size of %dx%d\n", $this->GetCropX(), $this->GetCropY(), $this->GetConfigWidth(), $this->GetConfigHeight());
        if (function_exists('imagecrop')) {
            $im3 = imagecrop($im2, array('x' => $this->GetCropX(), 'y' => $this->GetCropY(), 'width' => $this->GetConfigWidth(), 'height' => $this->GetConfigHeight()));
        } else {
            $im3 = imagecreatetruecolor($this->GetConfigWidth(), $this->GetConfigHeight());
            imagecopy($im3, $im2, 0, 0, $this->x, $this->y, $this->GetConfigWidth(), $this->GetConfigHeight());
        }
        $fn = $this->GetThumbnailPath();
        @mkdir(dirname($fn), 0755, true);
        @unlink($fn);
        imagejpeg($im3, $fn);
    }
    public function CropAuto() {
        $width = $this->GetOriginalWidth();
        $height = $this->GetOriginalHeight();
        $ratio = max($this->GetConfigWidth() / $width, $this->GetConfigHeight() / $height);
        $width = (int)($width * $ratio);
        $height = (int)($height * $ratio);
        $x = (int)(($width - $this->GetConfigWidth()) / 2);
        $y = (int)(($height - $this->GetConfigHeight()) / 2);
        $this->Crop($width, $height, $x, $y);
    }
    public function GetOriginalPath() {
        return cms_join_path(ThumbnailEditor::GetInstance()->GetModuleUploadsPath(), $this->GetModuleName(), $this->GetUid(), 'original', $this->GetFileName());
    }
    public function GetThumbnailPath() {
        return cms_join_path(ThumbnailEditor::GetInstance()->GetModuleUploadsPath(), $this->GetModuleName(), $this->GetUid(), 'thumbnail', $this->GetFileName());
    }
    public function GetOriginalUrl() {
        if (!is_file($this->GetOriginalPath())) return '';
        return cms_join_path(ThumbnailEditor::GetInstance()->GetModuleUploadsUrl(), $this->GetModuleName(), $this->GetUid(), 'original', $this->GetFileName()) . '?dt=' . (int)(microtime(true) * 1000);
    }
    public function GetThumbnailUrl() {
        if (!is_file($this->GetThumbnailPath())) return '';
        return cms_join_path(ThumbnailEditor::GetInstance()->GetModuleUploadsUrl(), $this->GetModuleName(), $this->GetUid(), 'thumbnail', $this->GetFileName()) . '?dt=' . (int)(microtime(true) * 1000);
    }
    public function Upload($source, $filename = null) {
        // Remove previous files
        $or = $this->GetOriginalPath();
        $th = $this->GetThumbnailPath();
        if (is_file($or)) unlink($or);
        if (is_file($th)) unlink($th);
        // Upload new files
        if (empty($filename)) $filename = basename($source);
        $this->filename = $filename;
        $path = $this->GetOriginalPath();
        @mkdir(dirname($path), 0755, true);
        copy($source, $path);
        list($this->originalWidth, $this->originalHeight) = getimagesize($path);
        // Create the default thumbnail
        $this->CropAuto();
    }
}