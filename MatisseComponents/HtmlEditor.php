<?php
namespace Selenia\Plugins\MatisseComponents;

use Selenia\Matisse\Attributes\Base\VisualComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Base\VisualComponent;

//Note that the file fckeditor/editor/fckeditor.html should be changed from the default to:  <body style="visibility:hidden">

class HtmlEditorAttributes extends VisualComponentAttributes
{
  /**
   * @var bool
   */
  public $autofocus = false;
  /**
   * @var string
   */
  public $lang = '';
  /**
   * @var string
   */
  public $name = type::id;
  /**
   * @var string
   */
  public $value = '';
}

class HtmlEditor extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return HtmlEditorAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return HtmlEditorAttributes
   */
  public function newAttributes ()
  {
    return new HtmlEditorAttributes($this);
  }

  /**
   * @global Application $application
   */
  protected function render ()
  {
    global $application, $controller;
    $attr = $this->attrs ();

    if (!isset($attr->name))
      $attr->name = $attr->id;
    $lang           = property ($attr, 'lang', $controller->lang);
    $lang           = $lang === 'pt' ? 'pt_pt' : $lang;
    $addonURI       = "$application->addonsPath/components/redactor";
    $autofocus      = $attr->autofocus ? 'true' : 'false';
    $scriptsBaseURI = $application->framework;
    $initCode       = <<<JAVASCRIPT
var redactorToolbar = ['html', 'formatting', 'bold', 'italic',
'unorderedlist', 'orderedlist', 'outdent', 'indent',
'image', 'video', 'file', 'table', 'link',
'fontcolor', 'backcolor',
'alignment',
'horizontalrule', 'fullscreen'];
JAVASCRIPT;
    $code           = <<<JAVASCRIPT
$(document).ready(
  function() {
    $('#{$attr->id}_field').redactor({
      buttons: redactorToolbar,
      lang: '{$lang}',
      focus: $autofocus,
      resize: false,
      autoresize: false,
      minHeight: 220,
      plugins: ['video', 'table', 'fullscreen', 'fontcolor', 'imagemanager', 'filemanager'],
      imageUpload: '$scriptsBaseURI/imageUpload.php',
      fileUpload: '$scriptsBaseURI/fileUpload.php',
      imageGetJson: '$scriptsBaseURI/gallery.php',
      imageManagerJson: '$scriptsBaseURI/gallery.php',
      imageInsertCallback: onInlineImageInsert
    });
  }
);
JAVASCRIPT;
    $this->page->addScript ("$addonURI/redactor.min.js");
    $this->page->addScript ("$addonURI/langs/$lang.js");
    $this->page->addStylesheet ("$addonURI/css/redactor.css");
    $this->page->addScript ("$addonURI/plugins/fontcolor.js");
    $this->page->addScript ("$addonURI/plugins/video.js");
    $this->page->addScript ("$addonURI/plugins/table.js");
    $this->page->addScript ("$addonURI/plugins/fullscreen.js");
    $this->page->addScript ("$addonURI/plugins/imagemanager.js");
    $this->page->addInlineScript ($initCode, 'redactor');
    $this->page->addInlineScript ($code);

    $this->tag ('textarea', [
      'id'   => $attr->id . "_field",
      'name' => $attr->name,
    ], $attr->value);
  }
}
