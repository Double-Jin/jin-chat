<?php
/**
 * Created by PhpStorm.
 * User: runs
 * Date: 19-2-27
 * Time: 下午4:22
 * copy https://github.com/SoftCreatR/php-mime-detector
 */

namespace EasySwoole\Utility\Mime;

/**
 * Class MimeDetector
 *
 * @package EasySwoole\Utility\Mime
 */
class MimeDetector
{
    /**
     * Cached first X bytes of the given file
     *
     * @var array
     */
    private $byteCache = [];

    /**
     * Number of cached bytes
     *
     * @var integer
     */
    private $byteCacheLen = 0;

    /**
     * Maximum number of bytes to cache
     *
     * @var integer
     */
    private $maxByteCacheLen = 4096;

    /**
     * Path to the given file
     *
     * @var string|null
     */
    private $stream = null;

    /**
     * @var null|array
     */
    private $fileType = null;

    /**
     * Setter for the file to be checked.
     *
     * @param   string $filePath
     * @return  MimeDetector
     * @throws  MimeDetectorException
     */
    public function setFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new MimeDetectorException("File '" . $filePath . "' does not exist.");
        }

        $this->stream = $this->readFile($filePath);
        $this->createByteCache();
        return $this;
    }

    /**
     * setStream
     *
     * @param string $stream
     * @return MimeDetector
     * @throws MimeDetectorException
     */
    public function setStream(string $stream): self
    {
        $this->stream = $stream;
        $this->createByteCache();
        return $this;
    }

    /**
     * 获取文件类型
     *
     * @return array|null
     */
    public function getFileType(): ?array
    {
        if (empty($this->byteCache)) {
            return null;
        }

        /** cache */
        if (is_null($this->fileType)) {
            $this->fileType = $this->checkFileType();
        }

        return $this->fileType;
    }

    /**
     * 获取文件后缀
     *
     * @return string|null
     */
    public function getFileExtension(): ?string
    {
        $fileType = $this->getFileType();

        if (!empty($fileType['ext'])) {
            return $fileType['ext'];
        }

        return null;
    }

    /**
     * 获取文件Mime类型
     *
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        $fileType = $this->getFileType();

        if (!empty($fileType['mime'])) {
            return $fileType['mime'];
        }

        return null;
    }

    /**
     * Tries to determine the correct mime type of the given file by using "magic numbers".
     *
     * @return  array
     */
    protected function checkFileType(): array
    {
        // Perform check
        if ($this->checkForBytes([0xFF, 0xD8, 0xFF])) {
            return [
                'ext'  => 'jpg',
                'mime' => 'image/jpeg'
            ];
        }

        if ($this->checkForBytes([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A])) {
            return [
                'ext'  => 'png',
                'mime' => 'image/png'
            ];
        }

        if ($this->checkForBytes([0x47, 0x49, 0x46])) {
            return [
                'ext'  => 'gif',
                'mime' => 'image/gif'
            ];
        }

        if ($this->checkForBytes([0x57, 0x45, 0x42, 0x50], 8)) {
            return [
                'ext'  => 'webp',
                'mime' => 'image/webp'
            ];
        }

        if ($this->checkForBytes([0x46, 0x4C, 0x49, 0x46])) {
            return [
                'ext'  => 'flif',
                'mime' => 'image/flif'
            ];
        }

        // Needs to be before `tif` check
        if ((
                $this->checkForBytes([0x49, 0x49, 0x2A, 0x0]) ||
                $this->checkForBytes([0x4D, 0x4D, 0x0, 0x2A])
            ) && $this->checkForBytes([0x43, 0x52], 8)
        ) {
            return [
                'ext'  => 'cr2',
                'mime' => 'image/x-canon-cr2'
            ];
        }

        if ($this->checkForBytes([0x49, 0x49, 0x2A, 0x0]) ||
            $this->checkForBytes([0x4D, 0x4D, 0x0, 0x2A])
        ) {
            return [
                'ext'  => 'tif',
                'mime' => 'image/tiff'
            ];
        }

        if ($this->checkForBytes([0x42, 0x4D])) {
            return [
                'ext'  => 'bmp',
                'mime' => 'image/bmp'
            ];
        }

        if ($this->checkForBytes([0x49, 0x49, 0xBC])) {
            return [
                'ext'  => 'jxr',
                'mime' => 'image/vnd.ms-photo'
            ];
        }

        if ($this->checkForBytes([0x38, 0x42, 0x50, 0x53])) {
            return [
                'ext'  => 'psd',
                'mime' => 'image/vnd.adobe.photoshop'
            ];
        }

        // Zip-based file formats
        // Need to be before the `zip` check
        if ($this->checkForBytes([0x50, 0x4B, 0x3, 0x4])) {
            if ($this->checkForBytes([
                0x6D, 0x69, 0x6D, 0x65, 0x74, 0x79, 0x70,
                0x65, 0x61, 0x70, 0x70, 0x6C, 0x69, 0x63,
                0x61, 0x74, 0x69, 0x6F, 0x6E, 0x2F, 0x65,
                0x70, 0x75, 0x62, 0x2B, 0x7A, 0x69, 0x70
            ], 30)) {
                return [
                    'ext'  => 'epub',
                    'mime' => 'application/epub+zip'
                ];
            }

            // Assumes signed `.xpi` from addons.mozilla.org
            if ($this->checkString('META-INF/mozilla.rsa', 30)) {
                return [
                    'ext'  => 'xpi',
                    'mime' => 'application/x-xpinstall'
                ];
            }

            if ($this->checkString('mimetypeapplication/vnd.oasis.opendocument.text', 30)) {
                return [
                    'ext'  => 'odt',
                    'mime' => 'application/vnd.oasis.opendocument.text'
                ];
            }

            if ($this->checkString('mimetypeapplication/vnd.oasis.opendocument.spreadsheet', 30)) {
                return [
                    'ext'  => 'ods',
                    'mime' => 'application/vnd.oasis.opendocument.spreadsheet'
                ];
            }

            if ($this->checkString('mimetypeapplication/vnd.oasis.opendocument.presentation', 30)) {
                return [
                    'ext'  => 'odp',
                    'mime' => 'application/vnd.oasis.opendocument.presentation'
                ];
            }

            // The docx, xlsx and pptx file types extend the Office Open XML file format:
            // https://en.wikipedia.org/wiki/Office_Open_XML_file_formats
            $zipHeaderIndex = 0; // The first zip header was already found at index 0
            $oxmlFound = false;
            $type = null;
            $oxmlCTypes = $this->toBytes('[Content_Types].xml');
            $oxmlRels = $this->toBytes('_rels/.rels');

            do {
                $offset = $zipHeaderIndex + 30;

                if (!$oxmlFound) {
                    $oxmlFound = $this->checkForBytes($oxmlCTypes, $offset) || $this->checkForBytes($oxmlRels, $offset);
                }

                if (!$type) {
                    if ($this->checkString('word/', $offset)) {
                        $type = [
                            'ext'  => 'docx',
                            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ];
                    } elseif ($this->checkString('ppt/', $offset)) {
                        $type = [
                            'ext'  => 'pptx',
                            'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                        ];
                    } elseif ($this->checkString('xl/', $offset)) {
                        $type = [
                            'ext'  => 'xlsx',
                            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ];
                    }
                }

                if ($oxmlFound && $type) {
                    return $type;
                }

                $zipHeaderIndex = $this->searchForBytes([0x50, 0x4B, 0x03, 0x04], $offset);
            } while ($zipHeaderIndex !== -1);

            // No more zip parts available in the buffer, but maybe we are almost certain about the type?
            if ($type) {
                return $type;
            }

            return [
                'ext'  => 'zip',
                'mime' => 'application/zip'
            ];
        }

        if ($this->checkForBytes([0x75, 0x73, 0x74, 0x61, 0x72], 257)) {
            return [
                'ext'  => 'tar',
                'mime' => 'application/x-tar'
            ];
        }

        if ($this->checkForBytes([0x52, 0x61, 0x72, 0x21, 0x1A, 0x7]) &&
            (
                $this->byteCache[6] === 0x0 ||
                $this->byteCache[6] === 0x1
            )
        ) {
            return [
                'ext'  => 'rar',
                'mime' => 'application/x-rar-compressed'
            ];
        }

        if ($this->checkForBytes([0x1F, 0x8B, 0x8])) {
            return [
                'ext'  => 'gz',
                'mime' => 'application/gzip'
            ];
        }

        if ($this->checkForBytes([0x42, 0x5A, 0x68])) {
            return [
                'ext'  => 'bz2',
                'mime' => 'application/x-bzip2'
            ];
        }

        if ($this->checkForBytes([0x37, 0x7A, 0xBC, 0xAF, 0x27, 0x1C])) {
            return [
                'ext'  => '7z',
                'mime' => 'application/x-7z-compressed'
            ];
        }

        if ($this->checkForBytes([0x78, 0x01])) {
            return [
                'ext'  => 'dmg',
                'mime' => 'application/x-apple-diskimage'
            ];
        }

        if ($this->checkForBytes([0x33, 0x67, 0x70, 0x35]) || // 3gp5
            (
                $this->checkForBytes([0x0, 0x0, 0x0]) &&
                $this->checkForBytes([0x66, 0x74, 0x79, 0x70], 4) &&
                (
                    $this->checkForBytes([0x6D, 0x70, 0x34, 0x31], 8) || // MP41
                    $this->checkForBytes([0x6D, 0x70, 0x34, 0x32], 8) || // MP42
                    $this->checkForBytes([0x69, 0x73, 0x6F, 0x6D], 8) || // ISOM
                    $this->checkForBytes([0x69, 0x73, 0x6F, 0x32], 8) || // ISO2
                    $this->checkForBytes([0x6D, 0x6D, 0x70, 0x34], 8) || // MMP4
                    $this->checkForBytes([0x4D, 0x34, 0x56], 8) || // M4V
                    $this->checkForBytes([0x64, 0x61, 0x73, 0x68], 8) // DASH
                )
            )
        ) {
            return [
                'ext'  => 'mp4',
                'mime' => 'video/mp4'
            ];
        }

        if ($this->checkForBytes([0x4D, 0x54, 0x68, 0x64])) {
            return [
                'ext'  => 'mid',
                'mime' => 'audio/midi'
            ];
        }

        // https://github.com/threatstack/libmagic/blob/master/magic/Magdir/matroska
        if ($this->checkForBytes([0x1A, 0x45, 0xDF, 0xA3])) {
            $idPos = $this->searchForBytes([0x42, 0x82]);

            if ($idPos !== -1) {
                if ($this->checkString('matroska', $idPos + 3)) {
                    return [
                        'ext'  => 'mkv',
                        'mime' => 'video/x-matroska'
                    ];
                }

                if ($this->checkString('webm', $idPos + 3)) {
                    return [
                        'ext'  => 'webm',
                        'mime' => 'video/webm'
                    ];
                }
            }
        }

        if ($this->checkForBytes([0x0, 0x0, 0x0, 0x14, 0x66, 0x74, 0x79, 0x70, 0x71, 0x74, 0x20, 0x20]) ||
            $this->checkForBytes([0x66, 0x72, 0x65, 0x65], 4) ||
            $this->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x71, 0x74, 0x20, 0x20], 4) ||
            $this->checkForBytes([0x6D, 0x64, 0x61, 0x74], 4) || // MJPEG
            $this->checkForBytes([0x6D, 0x6F, 0x6F, 0x76], 4) || // Moov
            $this->checkForBytes([0x77, 0x69, 0x64, 0x65], 4)
        ) {
            return [
                'ext'  => 'mov',
                'mime' => 'video/quicktime'
            ];
        }

        // RIFF file format which might be AVI, WAV, QCP, etc
        if ($this->checkForBytes([0x52, 0x49, 0x46, 0x46])) {
            if ($this->checkForBytes([0x41, 0x56, 0x49], 8)) {
                return [
                    'ext'  => 'avi',
                    'mime' => 'video/vnd.avi'
                ];
            }

            if ($this->checkForBytes([0x57, 0x41, 0x56, 0x45], 8)) {
                return [
                    'ext'  => 'wav',
                    'mime' => 'audio/vnd.wave'
                ];
            }

            // QLCM, QCP file
            if ($this->checkForBytes([0x51, 0x4C, 0x43, 0x4D], 8)) {
                return [
                    'ext'  => 'qcp',
                    'mime' => 'audio/qcelp'
                ];
            }

            // animated cursors
            // mime type might be wrong, but there's not much information about the "correct" one
            if ($this->checkForBytes([0x41, 0x43, 0x4F, 0x4E], 8)) {
                return [
                    'ext'  => 'ani',
                    'mime' => 'application/x-navi-animation'
                ];
            }
        }

        if ($this->checkForBytes([0x30, 0x26, 0xB2, 0x75, 0x8E, 0x66, 0xCF, 0x11, 0xA6, 0xD9])) {
            return [
                'ext'  => 'wmv',
                'mime' => 'video/x-ms-wmv'
            ];
        }

        if ($this->checkForBytes([0x0, 0x0, 0x1, 0xBA]) || $this->checkForBytes([0x0, 0x0, 0x1, 0xB3])) {
            return [
                'ext'  => 'mpg',
                'mime' => 'video/mpeg'
            ];
        }

        if ($this->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x33, 0x67], 4)) {
            return [
                'ext'  => '3gp',
                'mime' => 'video/3gpp'
            ];
        }

        // Check for MPEG header at different starting offsets
        for ($offset = 0; ($offset < 2 && $offset < ($this->byteCacheLen - 16)); $offset++) {
            if ($this->checkForBytes([0x49, 0x44, 0x33], $offset) || // ID3 header
                $this->checkForBytes([0xFF, 0xE2], $offset, [0xFF, 0xE2]) // MPEG 1 or 2 Layer 3 header
            ) {
                return [
                    'ext'  => 'mp3',
                    'mime' => 'audio/mpeg'
                ];
            }

            // MPEG 1 or 2 Layer 2 header
            if ($this->checkForBytes([0xFF, 0xE4], $offset, [0xFF, 0xE4])) {
                return [
                    'ext'  => 'mp2',
                    'mime' => 'audio/mpeg'
                ];
            }

            // MPEG 2 layer 0 using ADTS
            if ($this->checkForBytes([0xFF, 0xF8], $offset, [0xFF, 0xFC])) {
                return [
                    'ext'  => 'mp2',
                    'mime' => 'audio/mpeg'
                ];
            }

            // MPEG 4 layer 0 using ADTS
            if ($this->checkForBytes([0xFF, 0xF0], $offset, [0xFF, 0xFC])) {
                return [
                    'ext'  => 'mp4',
                    'mime' => 'audio/mpeg'
                ];
            }
        }

        if ($this->checkForBytes([0x66, 0x74, 0x79, 0x70, 0x4D, 0x34, 0x41], 4) ||
            $this->checkForBytes([0x4D, 0x34, 0x41, 0x20])
        ) {
            return [ // MPEG-4 layer 3 (audio)
                'ext'  => 'm4a',
                'mime' => 'audio/mp4' // RFC 4337
            ];
        }

        // Needs to be before `ogg` check
        if ($this->checkForBytes([0x4F, 0x70, 0x75, 0x73, 0x48, 0x65, 0x61, 0x64], 28)) {
            return [
                'ext'  => 'opus',
                'mime' => 'audio/opus'
            ];
        }

        // If 'OggS' in first  bytes, then OGG container
        if ($this->checkForBytes([0x4F, 0x67, 0x67, 0x53])) {
            // This is a OGG container

            // If ' theora' in header.
            if ($this->checkForBytes([0x80, 0x74, 0x68, 0x65, 0x6F, 0x72, 0x61], 28)) {
                return [
                    'ext'  => 'ogv',
                    'mime' => 'video/ogg'
                ];
            }

            // If '\x01video' in header.
            if ($this->checkForBytes([0x01, 0x76, 0x69, 0x64, 0x65, 0x6F, 0x00], 28)) {
                return [
                    'ext'  => 'ogm',
                    'mime' => 'video/ogg'
                ];
            }

            // If ' FLAC' in header  https://xiph.org/flac/faq.html
            if ($this->checkForBytes([0x7F, 0x46, 0x4C, 0x41, 0x43], 28)) {
                return [
                    'ext'  => 'oga',
                    'mime' => 'audio/ogg'
                ];
            }

            // 'Speex  ' in header https://en.wikipedia.org/wiki/Speex
            if ($this->checkForBytes([0x53, 0x70, 0x65, 0x65, 0x78, 0x20, 0x20], 28)) {
                return [
                    'ext'  => 'spx',
                    'mime' => 'audio/ogg'
                ];
            }

            // If '\x01vorbis' in header
            if ($this->checkForBytes([0x01, 0x76, 0x6F, 0x72, 0x62, 0x69, 0x73], 28)) {
                return [
                    'ext'  => 'ogg',
                    'mime' => 'audio/ogg'
                ];
            }

            // Default OGG container https://www.iana.org/assignments/media-types/application/ogg
            // @codeCoverageIgnoreStart
            return [
                'ext'  => 'ogx',
                'mime' => 'application/ogg'
            ];
            // @codeCoverageIgnoreEnd
        }

        if ($this->checkForBytes([0x66, 0x4C, 0x61, 0x43])) {
            return [
                'ext'  => 'flac',
                'mime' => 'audio/x-flac'
            ];
        }

        // 'MAC '
        if ($this->checkForBytes([0x4D, 0x41, 0x43, 0x20])) {
            return [
                'ext'  => 'ape',
                'mime' => 'audio/ape'
            ];
        }

        // 'wvpk'
        if ($this->checkForBytes([0x77, 0x76, 0x70, 0x6B])) {
            return [
                'ext'  => 'wv',
                'mime' => 'audio/wavpack'
            ];
        }

        if ($this->checkForBytes([0x23, 0x21, 0x41, 0x4D, 0x52, 0x0A])) {
            return [
                'ext'  => 'amr',
                'mime' => 'audio/amr'
            ];
        }

        if ($this->checkForBytes([0x25, 0x50, 0x44, 0x46])) {
            return [
                'ext'  => 'pdf',
                'mime' => 'application/pdf'
            ];
        }

        if ($this->checkForBytes([0x4D, 0x5A])) {
            return [
                'ext'  => 'exe',
                'mime' => 'application/x-msdownload'
            ];
        }

        if ((
                $this->byteCache[0] === 0x43 || $this->byteCache[0] === 0x46
            ) &&
            $this->checkForBytes([0x57, 0x53], 1)
        ) {
            return [
                'ext'  => 'swf',
                'mime' => 'application/x-shockwave-flash'
            ];
        }

        if ($this->checkForBytes([0x7B, 0x5C, 0x72, 0x74, 0x66])) {
            return [
                'ext'  => 'rtf',
                'mime' => 'application/rtf'
            ];
        }

        if ($this->checkForBytes([0x00, 0x61, 0x73, 0x6D])) {
            return [
                'ext'  => 'wasm',
                'mime' => 'application/wasm'
            ];
        }

        if ($this->checkForBytes([0x77, 0x4F, 0x46]) &&
            (
                $this->checkForBytes([0x00, 0x01, 0x00, 0x00], 4) ||
                $this->checkForBytes([0x4F, 0x54, 0x54, 0x4F], 4)
            )
        ) {
            if ($this->byteCache[3] === 0x46) {
                return [
                    'ext'  => 'woff',
                    'mime' => 'font/woff'
                ];
            }

            if ($this->byteCache[3] === 0x32) {
                return [
                    'ext'  => 'woff2',
                    'mime' => 'font/woff2'
                ];
            }
        }

        if ($this->checkForBytes([0x4C, 0x50], 34) &&
            (
                $this->checkForBytes([0x00, 0x00, 0x01], 8) ||
                $this->checkForBytes([0x01, 0x00, 0x02], 8) ||
                $this->checkForBytes([0x02, 0x00, 0x02], 8)
            )
        ) {
            return [
                'ext'  => 'eot',
                'mime' => 'application/vnd.ms-fontobject'
            ];
        }

        if ($this->checkForBytes([0x00, 0x01, 0x00, 0x00, 0x00])) {
            return [
                'ext'  => 'ttf',
                'mime' => 'font/ttf'
            ];
        }

        if ($this->checkForBytes([0x4F, 0x54, 0x54, 0x4F, 0x00])) {
            return [
                'ext'  => 'otf',
                'mime' => 'font/otf'
            ];
        }

        if ($this->checkForBytes([0x00, 0x00, 0x01, 0x00])) {
            return [
                'ext'  => 'ico',
                'mime' => 'image/x-icon'
            ];
        }

        if ($this->checkForBytes([0x00, 0x00, 0x02, 0x00])) {
            return [
                'ext'  => 'cur',
                'mime' => 'image/x-icon'
            ];
        }

        if ($this->checkForBytes([0x46, 0x4C, 0x56, 0x01])) {
            return [
                'ext'  => 'flv',
                'mime' => 'video/x-flv'
            ];
        }

        if ($this->checkForBytes([0x25, 0x21])) {
            return [
                'ext'  => 'ps',
                'mime' => 'application/postscript'
            ];
        }

        if ($this->checkForBytes([0xFD, 0x37, 0x7A, 0x58, 0x5A, 0x00])) {
            return [
                'ext'  => 'xz',
                'mime' => 'application/x-xz'
            ];
        }

        if ($this->checkForBytes([0x53, 0x51, 0x4C, 0x69])) {
            return [
                'ext'  => 'sqlite',
                'mime' => 'application/x-sqlite3'
            ];
        }

        if ($this->checkForBytes([0x4E, 0x45, 0x53, 0x1A])) {
            return [
                'ext'  => 'nes',
                'mime' => 'application/x-nintendo-nes-rom'
            ];
        }

        if ($this->checkForBytes([0x43, 0x72, 0x32, 0x34])) {
            return [
                'ext'  => 'crx',
                'mime' => 'application/x-google-chrome-extension'
            ];
        }

        if ($this->checkForBytes([0x4D, 0x53, 0x43, 0x46]) ||
            $this->checkForBytes([0x49, 0x53, 0x63, 0x28])
        ) {
            return [
                'ext'  => 'cab',
                'mime' => 'application/vnd.ms-cab-compressed'
            ];
        }

        // Needs to be before `ar` check
        if ($this->checkForBytes([
            0x21, 0x3C, 0x61, 0x72, 0x63, 0x68, 0x3E,
            0x0A, 0x64, 0x65, 0x62, 0x69, 0x61, 0x6E,
            0x2D, 0x62, 0x69, 0x6E, 0x61, 0x72, 0x79
        ])) {
            return [
                'ext'  => 'deb',
                'mime' => 'application/x-deb'
            ];
        }

        if ($this->checkForBytes([0x21, 0x3C, 0x61, 0x72, 0x63, 0x68, 0x3E])) {
            return [
                'ext'  => 'ar',
                'mime' => 'application/x-unix-archive'
            ];
        }

        if ($this->checkForBytes([0xED, 0xAB, 0xEE, 0xDB])) {
            return [
                'ext'  => 'rpm',
                'mime' => 'application/x-rpm'
            ];
        }

        if ($this->checkForBytes([0x1F, 0xA0]) || $this->checkForBytes([0x1F, 0x9D])) {
            return [
                'ext'  => 'z',
                'mime' => 'application/x-compress'
            ];
        }

        if ($this->checkForBytes([0x4C, 0x5A, 0x49, 0x50])) {
            return [
                'ext'  => 'lz',
                'mime' => 'application/x-lzip'
            ];
        }

        if ($this->checkForBytes([0xD0, 0xCF, 0x11, 0xE0, 0xA1, 0xB1, 0x1A, 0xE1])) {
            // MS Visio
            if ($this->checkForBytes([
                0x56, 0x00, 0x69, 0x00, 0x73,
                0x00, 0x69, 0x00, 0x6F, 0x00,
                0x44, 0x00, 0x6F, 0x00, 0x63
            ], 1664)) {
                return [
                    'ext'  => 'vsd',
                    'mime' => 'application/vnd.visio'
                ];
            }

            return [
                'ext'  => 'msi',
                'mime' => 'application/x-msi'
            ];
        }

        if ($this->checkForBytes([
            0x06, 0x0E, 0x2B, 0x34, 0x02, 0x05, 0x01,
            0x01, 0x0D, 0x01, 0x02, 0x01, 0x01, 0x02
        ])) {
            return [
                'ext'  => 'mxf',
                'mime' => 'application/mxf'
            ];
        }

        if ($this->checkForBytes([0x47], 4) &&
            (
                $this->checkForBytes([0x47], 192) ||
                $this->checkForBytes([0x47], 196)
            )
        ) {
            return [
                'ext'  => 'mts',
                'mime' => 'video/mp2t'
            ];
        }

        if ($this->checkForBytes([0x42, 0x4C, 0x45, 0x4E, 0x44, 0x45, 0x52])) {
            return [
                'ext'  => 'blend',
                'mime' => 'application/x-blender'
            ];
        }

        if ($this->checkForBytes([0x42, 0x50, 0x47, 0xFB])) {
            return [
                'ext'  => 'bpg',
                'mime' => 'image/bpg'
            ];
        }

        if ($this->checkForBytes([0x00, 0x00, 0x00, 0x0C, 0x6A, 0x50, 0x20, 0x20, 0x0D, 0x0A, 0x87, 0x0A])) {
            // JPEG-2000 family
            if ($this->checkForBytes([0x6A, 0x70, 0x32, 0x20], 20)) {
                return [
                    'ext'  => 'jp2',
                    'mime' => 'image/jp2'
                ];
            }

            if ($this->checkForBytes([0x6A, 0x70, 0x78, 0x20], 20)) {
                return [
                    'ext'  => 'jpx',
                    'mime' => 'image/jpx'
                ];
            }

            if ($this->checkForBytes([0x6A, 0x70, 0x6D, 0x20], 20)) {
                return [
                    'ext'  => 'jpm',
                    'mime' => 'image/jpm'
                ];
            }

            if ($this->checkForBytes([0x6D, 0x6A, 0x70, 0x32], 20)) {
                return [
                    'ext'  => 'mj2',
                    'mime' => 'image/mj2'
                ];
            }
        }

        if ($this->checkForBytes([0x46, 0x4F, 0x52, 0x4D, 0x00])) {
            return [
                'ext'  => 'aif',
                'mime' => 'audio/aiff'
            ];
        }

        if ($this->checkForBytes([0x42, 0x4F, 0x4F, 0x4B, 0x4D, 0x4F, 0x42, 0x49], 60)) {
            return [
                'ext'  => 'mobi',
                'mime' => 'application/x-mobipocket-ebook'
            ];
        }

        // File Type Box (https://en.wikipedia.org/wiki/ISO_base_media_file_format)
        if ($this->checkForBytes([0x66, 0x74, 0x79, 0x70], 4)) {
            if ($this->checkForBytes([0x6D, 0x69, 0x66, 0x31], 8)) {
                return [
                    'ext'  => 'heic',
                    'mime' => 'image/heif'
                ];
            }

            if ($this->checkForBytes([0x6D, 0x73, 0x66, 0x31], 8)) {
                return [
                    'ext'  => 'heic',
                    'mime' => 'image/heif-sequence'
                ];
            }

            if ($this->checkForBytes([0x68, 0x65, 0x69, 0x63], 8) ||
                $this->checkForBytes([0x68, 0x65, 0x69, 0x78], 8)
            ) {
                return [
                    'ext'  => 'heic',
                    'mime' => 'image/heic'
                ];
            }

            // @codeCoverageIgnoreStart
            if ($this->checkForBytes([0x68, 0x65, 0x76, 0x63], 8) ||
                $this->checkForBytes([0x68, 0x65, 0x76, 0x78], 8)
            ) {
                return [
                    'ext'  => 'heic',
                    'mime' => 'image/heic-sequence'
                ];
            }
            // @codeCoverageIgnoreEnd
        }

        if ($this->checkForBytes([0xAB, 0x4B, 0x54, 0x58, 0x20, 0x31, 0x31, 0xBB, 0x0D, 0x0A, 0x1A, 0x0A])) {
            return [
                'ext'  => 'ktx',
                'mime' => 'image/ktx'
            ];
        }

        if ($this->checkForBytes([0x44, 0x49, 0x43, 0x4D], 128)) {
            return [
                'ext'  => 'dcm',
                'mime' => 'application/dicom'
            ];
        }

        if ($this->checkForBytes([0x1B, 0x4C, 0x75, 0x61])) {
            return [
                'ext'  => 'luac',
                'mime' => 'application/x-lua-bytecode'
            ];
        }

        if ($this->checkForBytes([0x64, 0x6E, 0x73, 0x2E]) || $this->checkForBytes([0x2E, 0x73, 0x6E, 0x64])) {
            return [
                'ext'  => 'au',
                'mime' => 'audio/basic'
            ];
        }

        // unfortunately, these formats don't have a proper mime type, but they are worth detecting
        if ($this->checkForBytes([0x67, 0x33, 0x64, 0x72, 0x65, 0x6D])) {
            return [
                'ext'  => 'g3drem',
                'mime' => 'application/octet-stream'
            ];
        }

        if ($this->checkForBytes([0x73, 0x69, 0x6c, 0x68, 0x6f, 0x75, 0x65, 0x74, 0x74, 0x65, 0x30, 0x35])) {
            return [
                'ext'  => 'studio3',
                'mime' => 'application/octet-stream'
            ];
        }

        // this class is intended to detect binary files, only. But there's nothing wrong in
        // trying to detect text files aswell.
        if ($this->checkString('<?xml ')) {
            if ($this->searchForBytes($this->toBytes('<!doctype svg'), 6) !== -1 ||
                $this->searchForBytes($this->toBytes('<!DOCTYPE svg'), 6) !== -1 ||
                $this->searchForBytes($this->toBytes('<svg'), 6) !== -1
            ) {
                return [
                    'ext'  => 'svg',
                    'mime' => 'image/svg+xml'
                ];
            }

            if ($this->searchForBytes($this->toBytes('<!doctype html'), 6) !== -1 ||
                $this->searchForBytes($this->toBytes('<!DOCTYPE html'), 6) !== -1 ||
                $this->searchForBytes($this->toBytes('<html'), 6) !== -1
            ) {
                return [
                    'ext'  => 'html',
                    'mime' => 'text/html'
                ];
            }

            if ($this->searchForBytes($this->toBytes('<rdf:RDF'), 6) !== -1) {
                return [
                    'ext'  => 'rdf',
                    'mime' => 'application/rdf+xml'
                ];
            }

            if ($this->searchForBytes($this->toBytes('<rss version="2.0"'), 6) !== -1) {
                return [
                    'ext'  => 'rss',
                    'mime' => 'application/rss+xml'
                ];
            }

            return [
                'ext'  => 'xml',
                'mime' => 'application/xml'
            ];
        }

        if ($this->checkString('<!doctype html') ||
            $this->checkString('<!DOCTYPE html') ||
            $this->checkString('<html')
        ) {
            return [
                'ext'  => 'html',
                'mime' => 'text/html'
            ];
        }

        return [];
    }

    /**
     * Returns the byte sequence of a given string.
     *
     * @param   string $str
     * @return  array
     */
    protected function toBytes(string $str): array
    {
        return array_values(unpack('C*', $str));
    }

    /**
     * Checks the byte sequence of a given string.
     *
     * @param   string $str
     * @param   int    $offset
     * @return  bool
     */
    protected function checkString(string $str, int $offset = 0): bool
    {
        return $this->checkForBytes($this->toBytes($str), $offset);
    }

    /**
     * Returns the offset to the next position of the given byte sequence.
     * Returns -1 if the sequence was not found.
     *
     * @param   array $bytes
     * @param   int   $offset
     * @param   array $mask
     * @return  int
     */
    protected function searchForBytes(array $bytes, int $offset = 0, array $mask = []): int
    {
        $limit = $this->byteCacheLen - count($bytes);

        for ($i = $offset; $i < $limit; $i++) {
            if ($this->checkForBytes($bytes, $i, $mask)) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Returns true, if a given byte sequence is found at the given offset within the given file.
     *
     * @param   array $bytes
     * @param   int   $offset
     * @param   array $mask
     * @return  bool
     */
    protected function checkForBytes(array $bytes, int $offset = 0, array $mask = []): bool
    {
        if (empty($bytes) || empty($this->byteCache)) {
            return false;
        }

        // make sure we have nummeric indices
        $bytes = array_values($bytes);

        foreach ($bytes as $i => $byte) {
            if (!empty($mask)) {
                if (!isset($this->byteCache[$offset + $i]) ||
                    !isset($mask[$i]) ||
                    $byte !== ($mask[$i] & $this->byteCache[$offset + $i])
                ) {
                    return false;
                }
            } elseif (!isset($this->byteCache[$offset + $i]) || $this->byteCache[$offset + $i] != $byte) {
                return false;
            }
        }

        return true;
    }

    /**
     * Caches the first X bytes (4096 by default) of the given file,
     * so we don't have to read the whole file on every iteration.
     *
     * @return  void
     * @throws  MimeDetectorException
     */
    protected function createByteCache(): void
    {
        if (!empty($this->byteCache)) {
            return;
        }

        if (empty($this->stream)) {
            throw new MimeDetectorException('No file provided.');
        }

        foreach (str_split($this->stream) as $i => $char) {
            $this->byteCache[$i] = ord($char);
        }

        $this->byteCacheLen = count($this->byteCache);
    }

    /**
     * readFile
     *
     * @param string $filePath
     * @return string
     */
    protected function readFile(string $filePath): string
    {
        /**
         * read bin
         */
        $handle = fopen($filePath, 'rb');
        $stream = fread($handle, $this->maxByteCacheLen);
        fclose($handle);
        return $stream;
    }
}