<?php

namespace GitSync;

class Modification
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
}