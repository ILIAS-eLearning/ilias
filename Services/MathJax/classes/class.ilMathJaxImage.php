<?php declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */

use ILIAS\DI\Container;

/**
 * Rendered MathJax image
 * Supports image types SVG or PNG
 * Files are stored in the web file system of ilias
 */
class ilMathJaxImage
{
    const TYPE_PNG = 'png';
    const TYPE_SVG = 'svg';

    /**
     * @var string relative path from the ilias web directory
     */
    protected string $basepath = '/temp/tex';

    /**
     * @var string Given latex code
     */
    protected string $tex;

    /**
     * @var string file suffix for the given type
     */
    protected string $suffix;

    /**
     * @var string salt for the filename generation, depending on the dpi parameter
     */
    protected string $salt;


    /**
     * 
     * @param string $a_tex     latex code
     * @param string $a_type    image type (png or svg)
     * @param int $a_dpi        dpi of rendered image
     */
    public function __construct(string $a_tex, string $a_type, int $a_dpi)
    {
        $this->tex = $a_tex;

        switch ($a_type) {
            case self::TYPE_PNG:
                $this->suffix = '.png';
                break;
            case self::TYPE_SVG:
                $this->suffix = '.svg';
                break;
            default:
                throw new ilMathJaxException('imagetype not supported');
        }

        $this->salt = '#' . $a_dpi;
    }

    /**
     * Get the ILIAS DI container
     */
    protected function di(): Container
    {
        global $DIC;
        return $DIC;
    }

    /**
     * Get the webspace file system
     * @return \ILIAS\Filesystem\Filesystem
     */
    protected function fs()
    {
        return $this->di()->filesystem()->web();
    }

    /**
     * Create the relative file path of the image
     */
    public function filepath()
    {
        $hash = md5($this->tex . $this->salt);
        return $this->basepath
            . '/' . substr($hash, 0, 4)
            . '/' . substr($hash, 4, 4)
            . '/' . $hash . $this->suffix;
    }

    /**
     * Get the absolute path of the image
     */
    public function absolutePath()
    {
        return CLIENT_WEB_DIR . $this->filepath();
    }

    /**
     * Check if an image is cached
     */
    public function exists(): bool
    {
        return $this->fs()->has($this->filepath());
    }

    /**
     * Read the content of a cached image
     */
    public function read(): string
    {
        return $this->fs()->read($this->filepath());
    }
    
    
    /**
     * Save the content of a cached image
     * @param string $a_content image content
     */
    public function write(string $a_content)
    {
        $this->fs()->put($this->filepath(), $a_content);
    }
    

    /**
     * Get the total size of the cache with an appropriate unit for display
     */
    public function getCacheSize(): string
    {
        $size = 0;
        if ($this->fs()->hasDir($this->basepath)) {
            foreach ($this->fs()->finder()->in([$this->basepath])->files() as $meta) {
                $size += $this->fs()->getSize($meta->getPath(), 1)->inBytes();
            }
        }

        $type = array("K", "M", "G", "T", "P", "E", "Z", "Y");
        $size = $size / 1000;
        $counter = 0;
        while ($size >= 1000) {
            $size = $size / 1000;
            $counter++;
        }

        return (round($size, 1) . " " . $type[$counter] . "B");
    }

    /**
     * Delete all files from the cache
     */
    public function clearCache()
    {
        $this->fs()->deleteDir($this->basepath);
    }
}