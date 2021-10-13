<?php

namespace App\Utils\Objects;

class ImageObject
{
    private $image;
    private $thumbnail;

    public function __construct(string $imageName, ?string $thumbnailName = null)
    {
        $this->image = $imageName;
        $this->thumbnail = $thumbnailName;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }
}
