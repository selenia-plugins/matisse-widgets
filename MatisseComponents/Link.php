<?php
namespace Selenia\Plugins\MatisseComponents;

use Selenia\Matisse\Attributes\VisualComponentAttributes;
use Selenia\Matisse\Type;
use Selenia\Matisse\VisualComponent;

class LinkAttributes extends VisualComponentAttributes
{
  public $action;
  public $activeClass = 'active';
  public $disabled    = false;
  public $href;
  public $label;
  public $param;
  public $script;
  public $tooltip;
  public $wrapper;

  protected function typeof_action () { return Type::ID; }

  protected function typeof_active_class () { return Type::TEXT; }

  protected function typeof_disabled () { return Type::BOOL; }

  protected function typeof_href () { return Type::TEXT; }

  protected function typeof_label () { return Type::TEXT; }

  protected function typeof_param () { return Type::TEXT; }

  protected function typeof_script () { return Type::TEXT; }

  protected function typeof_tooltip () { return Type::TEXT; }

  protected function typeof_wrapper () { return Type::TEXT; }
}

class Link extends VisualComponent
{

  /** overriden */
  protected $containerTag = 'a';

  /**
   * Returns the component's attributes.
   * @return LinkAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return LinkAttributes
   */
  public function newAttributes ()
  {
    return new LinkAttributes($this);
  }

  protected function preRender ()
  {
    global $application;
    $attr = $this->attrs ();

    if ($application->VURI == $attr->href)
      $this->cssClassName = $attr->activeClass;

    if (!empty($attr->wrapper))
      $this->containerTag = $attr->wrapper;
    parent::preRender ();
  }

  protected function render ()
  {
    $attr = $this->attrs ();

    if (!empty($attr->wrapper))
      $this->begin ('a');

    $script = $attr->action ? "doAction('{$this->attrs()->action}','{$this->attrs()->param}')"
      : $attr->script;

    $this->attr ('title', $attr->tooltip);
    $this->attr ('href', $attr->disabled
      ? '#'
      :
      (isset($attr->href)
        ?
        $attr->href
        :
        "javascript:$script"
      )
    );
    $this->beginContent ();
    $this->setContent ($attr->label);

    if (!empty($attr->wrapper))
      $this->end ();
  }
}
