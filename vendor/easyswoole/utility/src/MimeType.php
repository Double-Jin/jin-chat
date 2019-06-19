<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-10
 * Time: 20:30
 */

namespace EasySwoole\Utility;


use EasySwoole\Utility\Mime\MimeDetector;

class MimeType
{
    /*
     * 常见的拓展
     */
    const EXTENSION_MAP = [
        'audio/wav'                                                                 => '.wav',
        'audio/x-ms-wma'                                                            => '.wma',
        'video/x-ms-wmv'                                                            => '.wmv',
        'video/mp4'                                                                 => '.mp4',
        'audio/mpeg'                                                                => '.mp3',
        'audio/amr'                                                                 => '.amr',
        'application/vnd.rn-realmedia'                                              => '.rm',
        'audio/mid'                                                                 => '.mid',
        'image/bmp'                                                                 => '.bmp',
        'image/gif'                                                                 => '.gif',
        'image/png'                                                                 => '.png',
        'image/tiff'                                                                => '.tiff',
        'image/jpeg'                                                                => '.jpg',
        'application/pdf'                                                           => '.pdf',
        'application/msword'                                                        => '.doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => '.docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template'   => '.dotx',
        'application/vnd.ms-word.document.macroEnabled.12'                          => '.docm',
        'application/vnd.ms-word.template.macroEnabled.12'                          => '.dotm',
        'application/vnd.ms-excel'                                                  => '.xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => '.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template'      => '.xltx',
        'application/vnd.ms-excel.sheet.macroEnabled.12'                            => '.xlsm',
        'application/vnd.ms-excel.template.macroEnabled.12'                         => '.xltm',
        'application/vnd.ms-excel.addin.macroEnabled.12'                            => '.xlam',
        'application/vnd.ms-excel.sheet.binary.macroEnabled.12'                     => '.xlsb',
        'application/vnd.ms-powerpoint'                                             => '.ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
        'application/vnd.openxmlformats-officedocument.presentationml.template'     => '.potx',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow'    => '.ppsx',
        'application/vnd.ms-powerpoint.addin.macroEnabled.12'                       => '.ppam',
    ];

    /**
     * 通过文件流获取文件Mime类型
     *
     * @param string|null $stream   文件流
     * @return string|null          Mime类型|无法判断
     * @throws Mime\MimeDetectorException
     */
    public static function getMimeTypeFromStream(?string $stream)
    {
        $mimeDetector = new MimeDetector();
        return $mimeDetector->setStream($stream)->getMimeType();
    }

    /**
     * 通过文件流获取文件后缀名
     *
     * @param string|null $stream   文件流
     * @return string|null          后缀名|无法判断
     * @throws Mime\MimeDetectorException
     */
    public static function getExtFromStream(?string $stream): ?string
    {
        $mimeDetector = new MimeDetector();
        return $mimeDetector->setStream($stream)->getFileExtension();
    }

    /**
     * 通过文件方式获取文件Mime类型
     *
     * @param string $filePath  文件路径
     * @return string|null      Mime类型|无法判断
     * @throws Mime\MimeDetectorException
     */
    public static function getMimeTypeFromFile(string $filePath)
    {
        $mimeDetector = new MimeDetector();
        return $mimeDetector->setFile($filePath)->getMimeType();
    }

    /**
     * 通过文件方式获取文件后缀名
     *
     * @param string $filePath  文件路径
     * @return string|null      后缀类型|无法判断
     * @throws Mime\MimeDetectorException
     */
    public static function getExtFromFile(string $filePath): ?string
    {
        $mimeDetector = new MimeDetector();
        return $mimeDetector->setFile($filePath)->getFileExtension();
    }

    /**
     * 通过Mime类型获取后缀名
     *
     * @param string $mineInfo  Mime类型
     * @return string|null
     */
    public static function getExtByMimeType(string $mineInfo) : ? string
    {
        if (isset(self::EXTENSION_MAP[$mineInfo])) {
            return self::EXTENSION_MAP[$mineInfo];
        }
        return null;
    }

    /**
     * 通过后缀名获取Mime类型
     *
     * @param string $ext
     * @return string|null
     */
    public static function getMimeTypeByExt(string $ext) : ? string
    {
        if (strpos($ext, '.') === false) {
            $ext = ".{$ext}";
        }
        $mimeType = array_search($ext, self::EXTENSION_MAP);
        if ($mimeType === false) {
            return null;
        }
        return $mimeType;
    }
}