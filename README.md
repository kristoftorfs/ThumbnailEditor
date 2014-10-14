# Thumbnail Editor

This module can not be used by itself. It is meant to be used as a framework by other modules which require thumbnails.

There are three class methods required to implement this inside your module, and an extra one to load a thumbnail.

## Class methods

### Initialization

The method ```EditorInit``` is used to initialize the editor. Arguments for the method are the following:

- _module_: reference to the module implementing the editor (this must be a NetDesign module).
- _action_: the module action implementing the editor.
- _actionid_: the actionid ($id).
- _uid_: a unique identifier for the thumbnail currently being edited.
- _width_: the width the thumbnail should have.
- _height_: the height the thumbnail should have.
- _params_: a duplicate of the module action params ($params).

### Processing

The method ```EditorProcess``` will process the form submissions of file uploads/thumbnail saving. It will return one
of the ```ThumbnailEditor::OPERATION_*``` constants to let you know what has happened. Possible return values are:

- ```ThumbnailEditor::OPERATION_NONE```: No form data was processed, we simply need to show the editor.
- ```ThumbnailEditor::OPERATION_CANCELLED```: The user pressed the cancel button, in which case you probably want to redirect to another module action.
- ```ThumbnailEditor::OPERATION_UPLOADED```: The user has uploaded a new image.
- ```ThumbnailEditor::OPERATION_APPLIED```: The user has saved the thumbnail configuration, and pressed the 'Apply' button.
- ```ThumbnailEditor::OPERATION_SUBMITTED```: The user has saved the thumbnail configuration, and pressed the 'Submit' button, in which case you probably want to redirect to another module action.

### Display editor

The method ```EditorDisplay``` actually displays the editor.

### Loading

To simply load a thumbnail (with no intention of updating it), you can use the ```Load``` method. This will return a Thumbnail object. For more information about this
see the file [libs/Thumbnail.php](libs/Thumbnail.php).

## An example

A full-featured example is below. As you can see, not much code is required to implement thumbnails in your module.

### action.editthumbnail.php

This is the module action used to edit the thumbnail.

```php
$uid = sprintf('%s/%s', $params['page'], $params['name']);                                                  // Generate an uid
$editor = ThumbnailEditor::GetInstance();
$editor->EditorInit($this, 'editthumbnail', $id, $uid, $params['width'], $params['height'], $params);       // Initialize the editor
$ret = $editor->EditorProcess();                                                                            // Process the form
if (in_array($ret, array(ThumbnailEditor::OPERATION_CANCELLED, ThumbnailEditor::OPERATION_SUBMITTED))) {
    $this->Redirect($id, 'defaultadmin', '');                                                               // Redirect if 'Submit' or 'Cancel' buttons have been pressed
} else {
    $editor->EditorDisplay();                                                                               // Otherwise display the editor
}
```

### action.thumbnail.php

This is the module action used to output the (URL of the) thumbnails.

```php
$uid = sprintf('%s/%s', $params['page'], $params['name']);
$thumb = ThumbnailEditor::GetInstance()->Load($this, $uid);                                                 // Load the thumbnail
$default = array_key_exists('default', $params) ? $params['default'] : '';                                  // Allow for a default URL if no thumbnail is set
if (array_key_exists('original', $params)) $params['original'] = (bool)$params['original'];                 // Check if we should output the original image or the thumbnail
if ($params['original']) {
    $ret = $thumb->GetOriginalUrl();
} else {
    $ret = $thumb->GetThumbnailUrl();
}
if (empty($ret)) echo $default;                                                                             // No thubmnail uploaded, output default value
else echo $ret;                                                                                             // Thubmnail uploaded, output the correct URL to it
```