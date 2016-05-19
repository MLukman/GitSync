<?php

namespace GitSync;

class Modification
{
    public $filename;
    public $status;
    public $mtime;

    public function __construct(Context $context,
                                \GitElephant\Status\StatusFile $statusFile)
    {
        $this->filename = $statusFile->getName();
        $this->status   = $statusFile->getWorkingTreeStatus();
        $fullfilename   = $context->getPath().'/'.$this->filename;
        if (file_exists($fullfilename)) {
            $this->mtime = \DateTime::createFromFormat('U',
                    filemtime($fullfilename));
            $this->mtime->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
        }
    }
}