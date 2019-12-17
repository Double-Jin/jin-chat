<?php

namespace EasySwoole\Utility;

/**
 * 文件助手类
 * Class File
 * @author  : evalor <master@evalor.cn>
 * @package EasySwoole\Utility
 */
class File
{

    /**
     * 创建目录
     * @author : evalor <master@evalor.cn>
     * @param string  $dirPath     需要创建的目录
     * @param integer $permissions 目录权限
     * @return bool
     */
    static function createDirectory($dirPath, $permissions = 0755)
    {
        if (!is_dir($dirPath)) {
            try {
                return mkdir($dirPath, $permissions, true) && chmod($dirPath, $permissions);
            } catch (\Throwable $throwable) {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * 清空一个目录
     * @param string $dirPath       需要创建的目录
     * @param bool   $keepStructure 是否保持目录结构
     * @author : evalor <master@evalor.cn>
     * @return bool
     */
    static function cleanDirectory($dirPath, $keepStructure = false)
    {
        $scanResult = static::scanDirectory($dirPath);
        if (!$scanResult) return false;

        try {
            foreach ($scanResult['files'] as $file) unlink($file);
            if (!$keepStructure) {
                krsort($scanResult['dirs']);
                foreach ($scanResult['dirs'] as $dir) rmdir($dir);
            }
            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * 删除一个目录
     * @param $dirPath
     * @author : evalor <master@evalor.cn>
     * @return bool
     */
    static function deleteDirectory($dirPath)
    {
        $dirPath = realpath($dirPath);
        if (!is_dir($dirPath)) return false;
        if (!static::cleanDirectory($dirPath)) return false;
        return rmdir(realpath($dirPath));
    }

    /**
     * 复制目录
     * @param string $source    源位置
     * @param string $target    目标位置
     * @param bool   $overwrite 是否覆盖目标文件
     * @return bool
     * @author : evalor <master@evalor.cn>
     */
    static function copyDirectory($source, $target, $overwrite = true)
    {
        $scanResult = static::scanDirectory($source);
        if (!$scanResult) return false;
        if (!is_dir($target)) self::createDirectory($target);

        try {
            $sourceRealPath = realpath($source);
            foreach ($scanResult['files'] as $file) {
                $targetRealPath = realpath($target) . '/' . ltrim(substr($file, strlen($sourceRealPath)), '/');
                static::copyFile($file, $targetRealPath, $overwrite);
            }
            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * 移动目录到另一位置
     * @param string $source    源位置
     * @param string $target    目标位置
     * @param bool   $overwrite 是否覆盖目标文件
     * @return bool
     * @author : evalor <master@evalor.cn>
     */
    static function moveDirectory($source, $target, $overwrite = true)
    {
        $scanResult = static::scanDirectory($source);
        if (!$scanResult) return false;
        if (!is_dir($target)) self::createDirectory($target);

        try {
            $sourceRealPath = realpath($source);
            foreach ($scanResult['files'] as $file) {
                $targetRealPath = realpath($target) . '/' . ltrim(substr($file, strlen($sourceRealPath)), '/');
                static::moveFile($file, $targetRealPath, $overwrite);
            }
            static::deleteDirectory($sourceRealPath);
            return true;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * 复制文件
     * @author : evalor <master@evalor.cn>
     * @param string $source    源位置
     * @param string $target    目标位置
     * @param bool   $overwrite 是否覆盖目标文件
     * @return bool
     */
    static function copyFile($source, $target, $overwrite = true)
    {
        if (!file_exists($source)) return false;
        if (file_exists($target) && $overwrite == false) return false;
        elseif (file_exists($target) && $overwrite == true) {
            if (!unlink($target)) return false;
        }
        $targetDir = dirname($target);
        if (!self::createDirectory($targetDir)) return false;
        return copy($source, $target);
    }

    /**
     * 创建一个空文件
     * @param $filePath
     * @param $overwrite
     * @author : evalor <master@evalor.cn>
     * @return bool
     */
    static function touchFile($filePath, $overwrite = true)
    {
        if (file_exists($filePath) && $overwrite == false) {
            return false;
        } elseif (file_exists($filePath) && $overwrite == true) {
            if (!unlink($filePath)) {
                return false;
            }
        }
        $aimDir = dirname($filePath);
        if (self::createDirectory($aimDir)) {
            try {
                return touch($filePath);
            } catch (\Throwable $throwable) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 创建一个有内容的文件
     * @param      $filePath
     * @param      $content
     * @param bool $overwrite
     * @author : evalor <master@evalor.cn>
     * @return bool
     */
    static function createFile($filePath, $content, $overwrite = true)
    {
        if (static::touchFile($filePath, $overwrite)) {
            return (bool)file_put_contents($filePath, $content);
        } else {
            return false;
        }
    }

    /**
     * 移动文件到另一位置
     * @param string $source    源位置
     * @param string $target    目标位置
     * @param bool   $overwrite 是否覆盖目标文件
     * @return bool
     * @author : evalor <master@evalor.cn>
     */
    static function moveFile($source, $target, $overwrite = true)
    {
        if (!file_exists($source)) return false;
        if (file_exists($target) && $overwrite == false) return false;
        elseif (file_exists($target) && $overwrite == true) {
            if (!unlink($target)) return false;
        }
        $targetDir = dirname($target);
        if (!self::createDirectory($targetDir)) return false;
        return rename($source, $target);
    }

    /**
     * 遍历目录
     * @param string $dirPath
     * @return array|bool
     * @author : evalor <master@evalor.cn>
     */
    static function scanDirectory($dirPath)
    {
        if (!is_dir($dirPath)) return false;
        $dirPath = rtrim($dirPath,'/') . '/';
        $dirs = array( $dirPath );

        $fileContainer = array();
        $dirContainer = array();

        try {
            do {
                $workDir = array_pop($dirs);
                $scanResult = scandir($workDir);
                foreach ($scanResult as $files) {
                    if ($files == '.' || $files == '..') continue;
                    $realPath = $workDir . $files;
                    if (is_dir($realPath)) {
                        array_push($dirs, $realPath . '/');
                        $dirContainer[] = $realPath;
                    } elseif (is_file($realPath)) {
                        $fileContainer[] = $realPath;
                    }
                }
            } while ($dirs);
        } catch (\Throwable $throwable) {
            return false;
        }

        return [ 'files' => $fileContainer, 'dirs' => $dirContainer ];
    }
}