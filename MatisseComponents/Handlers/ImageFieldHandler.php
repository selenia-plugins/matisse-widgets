<?php
namespace Selenia\Plugins\MatisseComponents\Handlers;

use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\UploadedFileInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;
use Selenia\FileServer\Lib\FileUtil;
use Selenia\Interfaces\ModelControllerExtensionInterface;
use Selenia\Interfaces\ModelControllerInterface;
use Selenia\Plugins\MatisseComponents\ImageField;
use Selenia\Plugins\MatisseComponents\Models\File;

class ImageFieldHandler implements ModelControllerExtensionInterface
{
  /** @var string */
  private $fileArchivePath;

  public function __construct (Application $app)
  {
    $this->fileArchivePath = $app->fileArchivePath;
  }

  /*
   * Detect if the request has fields that were generated by this component; if so, register an handler for saving
   * them.
   */
  function modelControllerExtension (ModelControllerInterface $modelController)
  {
    $request = $modelController->getRequest ();
    $files   = $request->getUploadedFiles ();
    $uploads = [];

    // Check if extension is applicable to the current request.
    foreach ($files as $fieldName => $file)
      if (str_endsWith ($fieldName, ImageField::FILE_FIELD_SUFFIX)) {
        // Note: slashes are converted to dots, which delimit path segments to nested fields. See the Field component.
        $fieldName           = str_replace ('/', '.', str_stripLastSegments ($fieldName, '_'));
        $uploads[$fieldName] = $file;
      }

    if ($uploads)
      $modelController->onSave (1, function () use ($uploads, $modelController) {
        $model = $modelController->getModel ();
        /** @var UploadedFileInterface $file */
        foreach ($uploads as $fieldName => $file) {
          $z = explode ('.', $fieldName);
          $prop = array_pop ($z);
          $path = implode('.', $z);
          $target = getAt($model, $path);

          $err = $file->getError ();
          if ($err == UPLOAD_ERR_OK)
            static::newUpload ($target, $prop, $file);
          else if ($err == UPLOAD_ERR_NO_FILE)
            static::noUpload ($target, $prop);
          else throw new FlashMessageException ("Error $err", FlashType::ERROR, "Error uploading file");
        }
      });
  }

  /**
   * Remove a physical file and the respective database file record.
   * ><p>Non-existing records or physical files are ignored.
   *
   * @param string $filePath A folder1/folder1/UID.ext path.
   * @throws \Exception If the file could not be deleted.
   */
  private function deleteFile ($filePath)
  {
    $id   = str_lastSegments ($filePath, '/');
    $id   = str_stripLastSegments ($id, '.');
    $file = File::find ($id);
    if ($file)
      $file->delete ();
  }

  /**
   * Handle the case where a file has been uploaded for a field, possibly replacing another already set on the field.
   *
   * @param Model                 $model
   * @param string                $fieldName
   * @param UploadedFileInterface $file
   */
  private function newUpload (Model $model, $fieldName, UploadedFileInterface $file)
  {
    $filename = $file->getClientFilename ();
    $ext      = strtolower (str_lastSegments ($filename, '.'));
    $name     = str_stripLastSegments ($filename, '.');
    $id       = uniqid ();
    $mime     = FileUtil::getUploadedFileMimeType ($file);
    $isImage  = FileUtil::isImageType ($mime);

    $fileModel = $model->files ()->create ([
      'id'    => $id,
      'name'  => $name,
      'ext'   => $ext,
      'mime'  => $mime,
      'image' => $isImage,
      'group' => str_lastSegments ($fieldName, '.'),
    ]);

    // Save the uploaded file.
    $path = "$this->fileArchivePath/$fileModel->path";
    $dir  = dirname ($path);
    if (!file_exists ($dir))
      mkdir ($dir, 0777, true);
    $file->moveTo ($path);

    // Delete the previous file for this field, if one exists.
    $prevFilePath = $model->getOriginal ($fieldName);
    if (exists ($prevFilePath))
      $this->deleteFile ($prevFilePath);

    $model->$fieldName = $fileModel->path;
  }

  /**
   * Handle the case where no file has been uploaded for a field, but the field may have been cleared.
   *
   * @param Model  $model
   * @param string $fieldName
   */
  private function noUpload (Model $model, $fieldName)
  {
    $prevFilePath = $model->getOriginal ($fieldName);
    if (!exists ($model->$fieldName) && exists ($prevFilePath))
      $this->deleteFile ($prevFilePath);
  }

}
