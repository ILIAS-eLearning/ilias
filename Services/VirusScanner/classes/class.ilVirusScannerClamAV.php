<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface to the ClamAV virus protector
 * @author        Ralf Schenk <rs@databay.de>
 * @version       $Id$
 * @extends       ilVirusScanner
 */
require_once "./Services/VirusScanner/classes/class.ilVirusScanner.php";

class ilVirusScannerClamAV extends ilVirusScanner
{

    const ADD_SCAN_PARAMS = '--no-summary -i';

    /**
     * Constructor
     * @access        public
     * @param        string virus scanner command
     */
    public function __construct($a_scancommand, $a_cleancommand)
    {
        parent::__construct($a_scancommand, $a_cleancommand);
        $this->type = "clamav";
        $this->scanZipFiles = true;
    }

    /**
     * @return string $scanCommand
     */
    protected function buildScanCommand($file = '-') // default means piping
    {
        return $this->scanCommand . ' ' . self::ADD_SCAN_PARAMS . ' ' . $file;
    }
    
    /**
     * @return bool $isBufferScanSupported
     */
    protected function isBufferScanPossible()
    {
        $functions = array('proc_open', 'proc_close');
        
        foreach ($functions as $func) {
            if (function_exists($func)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * @param string $buffer (any data, binary)
     * @return bool $infected
     */
    public function scanBuffer($buffer)
    {
        if (!$this->isBufferScanPossible()) {
            return $this->scanFileFromBuffer($buffer);
        }
        
        return $this->processBufferScan($buffer);
    }
    
    /**
     * @param string $buffer (any data, binary)
     * @return bool
     */
    protected function processBufferScan($buffer)
    {
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")		// stderr for the child
        );
        
        $pipes = array(); // will look like follows after passing
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout

        $process = proc_open($this->buildScanCommand(), $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            return false; // no scan, no virus detected
        }

        fwrite($pipes[0], $buffer);
        fclose($pipes[0]);

        $detectionReport = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errorReport = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $return = proc_close($process);
        
        return $this->hasDetections($detectionReport);
    }
    
    /**
     * @param $detectionReport
     * @return int
     */
    protected function hasDetections($detectionReport)
    {
        return preg_match("/FOUND/", $detectionReport);
    }

    /**
     * scan a file for viruses
     * @param        string        path of file to check
     * @param        string        original name of the file to ckeck
     * @return   string  virus message (empty if not infected)
     * @access        public
     */
    public function scanFile($a_filepath, $a_origname = "")
    {
        // This function should:
        // - call the external scanner for a_filepath
        // - set scanFilePath to a_filepath
        // - set scanFileOrigName to a_origname
        // - set scanFileIsInfected according the scan result
        // - set scanResult to the scanner output message
        // - call logScanResult() if file is infected
        // - return the scanResult, if file is infected
        // - return an empty string, if file is not infected

        $this->scanFilePath = $a_filepath;
        $this->scanFileOrigName = $a_origname;

        // Make group readable for clamdscan
        $currentPermission = fileperms($a_filepath);
        $perm = $currentPermission | 0640;
        chmod($a_filepath, $perm);

        //begin-patch skyguide
        $this->log->info('Scanning file: ' . $this->scanFilePath . ' original name: ' . $this->scanFileOrigName);
        //end-patch skyguide

        // Call of antivir command
        $cmd = $this->buildScanCommand($a_filepath) . " 2>&1";
        exec($cmd, $out, $ret);
        $this->scanResult = implode("\n", $out);

        if ($perm != $currentPermission) {
            chmod($a_filepath, $currentPermission);
        }


        //begin-patch skyguide
        if (strlen($this->scanResult)) {
            $this->log->info('Scan result ... ');
            $this->log->dump($this->scanResult, ilLogLevel::INFO);
        }
        $is_debug_virus = false;
        if ($a_origname == 'clamav_infected.txt') {
            $is_debug_virus = true;
            $this->scanResult = 'Debug file "clamav_infected" triggered virus alert. ';
        }
        //end-patch skyguide

        // sophie could be called
        //begin-patch skyguide
        if ($this->hasDetections($this->scanResult) || $is_debug_virus) {
            $this->scanFileIsInfected = true;
			#$this->logScanResult();
            $vir_info = array();
            $vir_info['found'] = 1;
            $vir_info['date_time'] = date("d-m-Y H:i:s");
            if ($GLOBALS['ilUser'] instanceof ilObjUser) {
                $vir_info['info_text'] = 'User ' . $GLOBALS['ilUser']->getLogin();
                $this->log->error('File is infected: ' . $this->scanFilePath . ' uploaded by ' . $GLOBALS['ilUser']->getLogin());
                $this->log->logStack();
            } else {
                $vir_info['info_text'] = 'Unknown User';
                $this->log->error('File is infected: ' . $this->scanFilePath . ' uploaded by unknown.');
                $this->log->logStack();
            }

            if (!empty($this->scanFileOrigName)) {
                $vir_info['info_text'] .= ' tried to upload an infected file named ' . $this->scanFileOrigName . '.';
            } else {
                $vir_info['info_text'] .= ' tried to upload an infected file with unknown name.';
            }

            if(isset($out[0])) {
                //separate path to temp file and virus type message, add the latter to info.
                $vir_info['info_text'] .= ' Virus type ' . explode(":", $out[0])[1] . '.';
            }

            //$info_string = implode("#", $vir_info);
            $file = ILIAS_LOG_DIR.'/clamd_status.log';
            $status_file = fopen($file, "w");
            //fwrite($status_file, $info_string);
            fwrite($status_file, "1");
            fclose($status_file);
            //end-patch skyguide
            return $this->scanResult;
		}
		else
		{            //begin-patch skyguide
            if ($GLOBALS['ilUser'] instanceof ilObjUser) {
                $this->log->info('File is clean: ' . $this->scanFilePath . ' uploaded by ' . $GLOBALS['ilUser']->getLogin());
            } else {
                $this->log->info('File is clean: ' . $this->scanFilePath . ' uploaded by unknown.');
                $this->log->logStack();
            }
            //end-patch skyguide
            $this->scanFileIsInfected = false;
            return "";
        }

        // antivir has failed (todo)
        $this->log->write("ERROR (Virus Scanner failed): "
            . $this->scanResult
            . "; COMMAMD=" . $cmd);
    }
}
