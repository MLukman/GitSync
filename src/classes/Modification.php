<?php

namespace GitSync;

class Modification implements \JsonSerializable
{
    public $filename;
    public $status;
    public $mtime;
    public $obj;

    public function __construct(Context $context,
                                \GitElephant\Status\StatusFile $statusFile,
                                $subpath = null)
    {
        $this->obj      = $statusFile;
        $this->filename = $statusFile->getName();
        if ($subpath) {
            $this->filename = $subpath.$this->filename;
        }
        $this->status = $statusFile->getWorkingTreeStatus();
        $fullfilename = $context->getPath().'/'.$this->filename;
        if (file_exists($fullfilename)) {
            $this->mtime = \DateTime::createFromFormat('U',
                    filemtime($fullfilename));
            $this->mtime->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
        }
    }

    public function jsonSerialize()
    {
        return array(
            'filename' => $this->filename,
            'status' => $this->status,
            'modtime' => $this->mtime ? $this->mtime->format('Y-m-d H:i') : '-',
        );
    }
}