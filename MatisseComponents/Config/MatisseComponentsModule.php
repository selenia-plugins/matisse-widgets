<?php
namespace Electro\Plugins\MatisseComponents\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Plugins\MatisseComponents as C;
use Electro\Plugins\MatisseComponents\Handlers\ImageFieldHandler;
use Electro\Plugins\MatisseComponents\Models\File;
use League\Glide\Server;

class MatisseComponentsModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT,
      function (ModuleServices $module, Application $app, ModelControllerInterface $modelController,
                InjectorInterface $injector) {
        $module
          ->publishPublicDirAs ('modules/electro-modules/matisse-components')
          ->provideMacros ()
          ->registerComponents ([
            'Button'         => C\Button::class,
            'Checkbox'       => C\Checkbox::class,
            'DataGrid'       => C\DataGrid::class,
            'Dropzone'       => C\Dropzone::class,
            'Field'          => C\Field::class,
            'FileUpload'     => C\FileUpload::class,
            'HtmlEditor'     => C\HtmlEditor::class,
            'Image'          => C\Image::class,
            'ImageField'     => C\ImageField::class,
            'Input'          => C\Input::class,
            'Label'          => C\Label::class,
            'Link'           => C\Link::class,
            'MainMenu'       => C\MainMenu::class,
            'NavigationPath' => C\NavigationPath::class,
            'Paginator'      => C\Paginator::class,
            'RadioButton'    => C\RadioButton::class,
            'Select'         => C\Select::class,
            'Switch'         => C\Switch_::class,
            'Tab'            => C\Tab::class,
            'TabPage'        => C\TabPage::class,
            'Tabs'           => C\Tabs::class,
          ])
          ->registerAssets ([
            'dist/components.css',
          ]);

        $modelController
          ->registerExtension ($injector->makeFactory (ImageFieldHandler::class));

        File::deleting (function (File $model) use ($app, $injector) {
          if (exists ($model->path)) {
            $path = "$app->fileArchivePath/$model->path";
            if (file_exists ($path))
              unlink ($path);
            $glideServer = $injector->make (Server::class);
            $glideServer->deleteCache ($model->path);
          }
        });

      });
  }

}
