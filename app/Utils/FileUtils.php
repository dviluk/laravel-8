<?php

namespace App\Utils;

use API;
use App\Utils\Objects\ImageObject;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Illuminate\Http\UploadedFile;
use Log;
use Storage;
use Str;

class FileUtils
{
    /**
     * Indica el tamaño de los thumbnail.
     * 
     * Debe ser menor que `DEFAULT_IMAGE_SIZE` para que se vean los resultados.
     */
    private const DEFAULT_THUMBNAIL_SIZE = 512;

    /**
     * Indica la calidad con la que se guardaran los thumbnails.
     * 
     * `100 = 100%`
     */
    private const DEFAULT_THUMBNAIL_QUALITY = 50;

    /**
     * Indica el tamaño de las imágenes almacenadas.
     */
    private const DEFAULT_IMAGE_SIZE = 1024;

    /**
     * Indica la calidad con la que se guardaran las imágenes.
     */
    private const DEFAULT_IMAGE_QUALITY = 80;

    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    private $disk;

    public function __construct($disk = 'public')
    {
        $this->disk = Storage::disk($disk);
    }

    /**
     * Guarda un archivo en el directorio indicado.
     *
     * @param UploadedFile $file Instancia del archivo
     * @param string $saveTo Directorio relativo a `storage/app/`
     * @param string $fileName Nombre del archivo
     * @return string
     */
    public function saveFile(UploadedFile $file, $saveTo, $fileName = null)
    {
        try {
            if ($fileName === null) {
                $fileName = Str::random(16);
            }

            $fileName = $fileName . '.' . strtolower($file->getClientOriginalExtension());

            $this->disk->putFile($saveTo, $file);

            return $fileName;
        } catch (\Throwable $e) {
            Log::error($e);

            return null;
        }
    }

    /**
     * Se encarga de eliminar un archivo
     *
     * @param string $fileName Nombre del archivo
     * @param string $path Directorio absoluto
     * @return boolean
     */
    public function deleteFile(string $fileName, string $path)
    {
        $filePath = $path . $fileName;
        try {
            if ($this->disk->exists($filePath)) {
                $this->disk->delete($filePath);
                return true;
            } else {
                Log::warning('Error al eliminar archivo, no se encontró: ' . $filePath);
            }
        } catch (\Throwable $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * Se encarga de generar la URL hacia el archivo indicado.
     *
     * @param string $fileName Nombre del archivo
     * @param string $path Directorio relativo a storage
     * @return void
     */
    public function getFileUrl(string $fileName, string $path)
    {
        $url = asset('storage/' . $path . $fileName);

        return $url;
    }

    /**
     * Guarda imágenes en el storage. Crea la carpeta si no existe.
     * 
     * Comprime las imágenes y las guarda en formato .jpg.
     * 
     * Retorna un objeto stdClass con las propiedades:
     * - image: imagen tamaño completo
     * - thumb: miniatura de la imagen
     * 
     * @param \Illuminate\Http\UploadedFile $image Instancia de la imagen subida
     * @param string|null $name Nombre de la nueva imagen, si es null se genera un string random
     * @param string $saveTo Ruta en donde se almacenara
     * @param boolean $thumbnail Si es true se genera una miniatura de la imagen
     * @return ImageObject|null
     */
    public function saveImage($image, $name, $saveTo, $thumbnail = false, $isDefaultSize = true): ImageObject
    {
        $imagesStored = [];
        try {
            $tempPath = $image->path();

            // crear directorio si no existe
            $this->disk->exists($saveTo) or $this->disk->makeDirectory($saveTo);

            $name = ($name ? $name : Str::random(128));
            // se generan los nombres de la imagen
            $imageName =  $name . '.jpg';
            $thumbnailName = null;

            $image = new ImageResize($tempPath);
            $image->resizeToBestFit(FileUtils::DEFAULT_IMAGE_SIZE, FileUtils::DEFAULT_IMAGE_SIZE);
            $image->gamma(false);

            $filepath = $this->disk->path($saveTo . $imageName);

            $image->save($filepath, IMAGETYPE_JPEG, FileUtils::DEFAULT_IMAGE_QUALITY);

            $imagesStored[] = $filepath;

            // se genera el thumbnail cuando se especifica
            if ($thumbnail) {
                $thumbFolder = $saveTo . 'thumbnails/';
                $this->disk->exists($thumbFolder) or $this->disk->makeDirectory($thumbFolder);

                $thumbWidth = $thumbHeight = FileUtils::DEFAULT_THUMBNAIL_SIZE;

                $thumbnailName = $name . ".jpg";

                if (is_numeric($thumbnail)) {
                    if (!$isDefaultSize) {
                        $thumbnailName = $name . "_{$thumbnail}x{$thumbnail}.jpg";
                    }

                    $thumbWidth = $thumbHeight = $thumbnail;
                } else if (is_array($thumbnail)) {
                    $thumbWidth = $thumbnail[0];
                    $thumbHeight = $thumbnail[1];

                    if (!$isDefaultSize) {
                        $thumbnailName = $name . "_{$thumbWidth}x{$thumbHeight}.jpg";
                    }
                }

                $image->crop($thumbWidth, $thumbHeight);

                $filepath = $this->disk->path($thumbFolder . $thumbnailName);
                $image->save($filepath, IMAGETYPE_JPEG, FileUtils::DEFAULT_THUMBNAIL_QUALITY);
                $imagesStored[] = $filepath;
            }

            // se retorna un objeto con el nombre de la imágenes generadas
            $img = new ImageObject($imageName, $thumbnailName);

            return $img;
        } catch (ImageResizeException $e) {
            foreach ($imagesStored as $img) {
                $this->disk->delete($img);
            }

            return null;
        }
    }

    /**
     * Retorna la imagen y el thumbnail.
     * 
     *
     * @param string $pathname directorio de la imagen
     * @param string $imageName nombre de la imagen
     * @param integer $size tamaño de la imagen a buscar
     * @return \App\Utils\Objects\ImageObject
     */
    public function getImage(string $pathname, string $imageName, int $size = 0)
    {
        $image = null;
        $thumbnail = null;

        if ($size === 0) {
            $image = $pathname . $imageName;
            $thumbnail = $pathname . 'thumbnails/' . $imageName;
        } else {
            $image = $pathname . $imageName;
            $thumbnail = $pathname . 'thumbnails/' . (str_replace('.jpg', "_{$size}x{$size}.jpg", $imageName));
        }

        return new ImageObject($image, $thumbnail);
    }

    /**
     * Utilizar cuando se requiere guardar una imagen y retornar los datos
     * usados (directorio, size, etc).
     * 
     */
    public function storeImage(?UploadedFile $image, string $path, $name = null, bool $thumbnail = false, $isDefaultSize = true)
    {
        if ($image !== null) {
            $img = $this->saveImage($image, $name, $path, $thumbnail, $isDefaultSize);

            if ($img !== null) {
                $imgInfo = [
                    'image' => $img->getImage(),
                    'thumbnail' => $img->getThumbnail(),
                    'path' => $path,
                ];

                return $imgInfo;
            }
        }

        return null;
    }

    /**
     * Eliminar una imagen y su thumbnail.
     *
     * @param string $pathName
     * @param string $img
     * @param integer $size
     * @return void
     */
    public function deleteImage(string $pathName, string $img, int $size = 0)
    {
        $fullPath = $pathName . $img;
        $thumbnailPath  = $this->generateThumbnailPath($pathName, $img, $size);

        try {
            if ($this->disk->exists($fullPath)) {
                $this->disk->delete($fullPath);
            }

            if ($this->disk->exists($thumbnailPath)) {
                $this->disk->delete($thumbnailPath);
            }
            return true;
        } catch (\Exception $e) {
            API::exceptionResponse($e);
            return false;
        }
    }

    /**
     * Elimina un arreglo de imágenes.
     * 
     * @param array $images 
     * @return void 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException 
     */
    public function deleteImagesStored(array $images)
    {
        foreach ($images as $img) {
            if ($img === null) {
                continue;
            }

            $path = $img['path'];
            $image = $img['image'];
            $size = $img['size'] ?? 0;

            $this->deleteImage($path, $image, $size);
        }
    }

    /**
     * Genera el path del thumbnail.
     * 
     * NOTA: Solo concatena la carpeta `thumbnail` a la ruta especificada.
     *
     * @param string $path directorio donde se encuentra la imagen
     * @param string $img nombre de la imagen original
     * @param integer $size tamaño de la imagen a consultar
     * @return string
     */
    public function generateThumbnailPath(string $path, string $img, int $size = 0)
    {
        return $path . '/thumbnails/' . $this->thumbnailName($img, $size);
    }

    /**
     * Genera el nombre del thumbnail de acuerdo al tamaño especificado.
     *
     * @param string $imageName
     * @param integer $size
     * @return void
     */
    public function thumbnailName(string $imageName, int $size = 0)
    {
        if ($size === 0) return $imageName;

        return str_replace('.jpg', "_{$size}x{$size}.jpg", $imageName);
    }

    /**
     * Retorna la url de la imagen especificada.
     *
     * @param string $path
     * @param string $imageName
     * @param integer $size
     * @return \stdClass
     */
    public function generateImageUrl(string $path, $imageName, $size = 0)
    {
        $image = null;
        $thumbnail = null;

        $imageName = $imageName ?? 'not-found.jpg';

        if ($size === 0) {
            $image = $path . $imageName;
            $thumbnail = $path . 'thumbnails/' . $imageName;
        } else {
            $image = $path . $imageName;
            $thumbnail = $path . 'thumbnails/' . (str_replace('.jpg', "_{$size}x{$size}.jpg", $imageName));
        }

        $image = asset('storage/' . $image);
        $thumbnail = asset('storage/' . $thumbnail);

        return new ImageObject($image, $thumbnail);
    }

    /**
     * Genera un nombre para archivo.
     * 
     * @param string $name 
     * @param array $options 
     * @return string 
     * @throws \Exception 
     */
    public function generateName(string $name = 'filename', array $options = [])
    {
        $prefix = isset($options['prefix']) ? $options['prefix'] . '_' : '';
        $postfix = '';

        if (isset($options['postfix'])) {
            $postfix = $options['postfix'];
            if (strpos($postfix, 'random') !== false) {
                $randomArr = explode('-', $postfix);
                $postfix = Str::random($randomArr[1] ?? 16);
            }
            $postfix = '_' . $postfix;
        }

        $filename = "{$prefix}{$name}{$postfix}";
        $filename = Str::limit($filename, 128);
        $filename = Str::slug($filename, '_');

        return $filename;
    }

    /**
     * Retorna el tipo de archivo según la extension del el nombre indicado.
     *
     * @param string $fileName
     * @return string
     */
    public function getFileType(string $fileName)
    {
        if (preg_match('/\.(gif|png|jpe?g)$/i', $fileName)) {
            return 'image';
        } else if (preg_match('/\.(htm|html)$/i', $fileName)) {
            return 'html';
        } else if (preg_match('/\.(rtf|docx?|xlsx?|pptx?|pps|potx?|ods|odt|pages|ai|dxf|ttf|tiff?|wmf|e?ps)$/i', $fileName)) {
            return 'doc';
        } else if (preg_match('/\.(txt|md|csv|nfo|php|ini)$/i', $fileName)) {
            return 'text';
        } else if (preg_match('/\.(og?|mp4|webm)$/i', $fileName)) {
            return 'video';
        } else if (preg_match('/\.(ogg|mp3|wav)$/i', $fileName)) {
            return 'audio';
        } else if (preg_match('/\.(pdf)$/i', $fileName)) {
            return 'pdf';
        } else {
            return 'other';
        }
    }
}
