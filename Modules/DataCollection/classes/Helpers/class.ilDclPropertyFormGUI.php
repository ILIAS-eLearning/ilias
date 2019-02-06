<?php

/**
 * Class ilDclPropertyFormGUI
 *
 * @author       Michael Herren <mh@studer-raimann.ch>
 * @version      1.0.0
 * @ilCtrl_Calls ilDclPropertyFormGUI: ilFormPropertyDispatchGUI
 */
class ilDclPropertyFormGUI extends ilPropertyFormGUI {

	/**
	 * Expose method for save confirmation
	 *
	 * @param      $a_hash
	 * @param      $a_field
	 * @param      $a_tmp_name
	 * @param      $a_name
	 * @param      $a_type
	 * @param null $a_index
	 * @param null $a_sub_index
	 */
	public function keepTempFileUpload($a_hash, $a_field, $a_tmp_name, $a_name, $a_type, $a_index = null, $a_sub_index = null) {
		$this->keepFileUpload($a_hash, $a_field, $a_tmp_name, $a_name, $a_type, $a_index, $a_sub_index);
	}

    /**
     * Import upload into temp directory
     *
     * @param string $a_hash unique form hash
     * @param string $a_field form field
     * @param string $a_tmp_name temp file name
     * @param string $a_name original file name
     * @param string $a_type file mime type
     * @param mixed $a_index form field index (if array)
     * @param mixed $a_sub_index form field subindex (if array)
     * @return bool
     * @throws ilException
     */
    protected function keepFileUpload($a_hash, $a_field, $a_tmp_name, $a_name, $a_type, $a_index = null, $a_sub_index = null)
    {
        if (trim($a_tmp_name) == "")
        {
            return;
        }

        $a_name = ilUtil::getAsciiFileName($a_name);

        $tmp_file_name = implode("~~", array(session_id(),
            $a_hash,
            $a_field,
            $a_index,
            $a_sub_index,
            str_replace("/", "~~", $a_type),
            str_replace("~~", "_", $a_name)));

        // make sure temp directory exists
        $temp_path = ilUtil::getDataDir() . "/temp";
        if (!is_dir($temp_path))
        {
            ilUtil::createDirectory($temp_path);
        }

        ilUtil::moveUploadedFile($a_tmp_name, $tmp_file_name, $temp_path."/".$tmp_file_name);
    }


	/**
	 * return temp-filename
	 *
	 * @param      $a_hash
	 * @param      $a_field
	 * @param      $a_name
	 * @param      $a_type
	 * @param null $a_index
	 * @param null $a_sub_index
	 *
	 * @return string
	 */
	public static function getTempFilename($a_hash, $a_field, $a_name, $a_type, $a_index = null, $a_sub_index = null) {
        $a_name = ilUtil::getAsciiFileName($a_name);

		$tmp_file_name = implode(
			"~~", array(session_id(),
			            $a_hash,
			            $a_field,
			            $a_index,
			            $a_sub_index,
			            str_replace("/", "~~", $a_type),
			            str_replace("~~", "_", $a_name))
		);

		// make sure temp directory exists
		$temp_path = ilUtil::getDataDir() . "/temp/";

		return $temp_path . $tmp_file_name;
	}


    /**
     * Return temp files
     *
     * @param $hash
     *
     * @return array
     * @throws ilDclException
     */
	public static function getTempFileByHash($hash) {
		$temp_path = ilUtil::getDataDir() . "/temp";
		if (is_dir($temp_path)) {
            $reload = array();

            $temp_files = glob($temp_path . "/" . session_id() . "~~" . $hash . "~~*");
            if (is_array($temp_files)) {
				foreach ($temp_files as $full_file) {
					$file = explode("~~", basename($full_file));
					$field = $file[2];
					$idx = $file[3];
					$idx2 = $file[4];
					$type = $file[5] . "/" . $file[6];
					$name = $file[7];

					if ($idx2 != "") {
						if (!$_FILES[$field]["tmp_name"][$idx][$idx2]) {
							$reload[$field]["tmp_name"][$idx][$idx2] = $full_file;
							$reload[$field]["name"][$idx][$idx2] = $name;
							$reload[$field]["type"][$idx][$idx2] = $type;
							$reload[$field]["error"][$idx][$idx2] = 0;
							$reload[$field]["size"][$idx][$idx2] = filesize($full_file);
						}
					} else {
						if ($idx != "") {
							if (!$_FILES[$field]["tmp_name"][$idx]) {
								$reload[$field]["tmp_name"][$idx] = $full_file;
								$reload[$field]["name"][$idx] = $name;
								$reload[$field]["type"][$idx] = $type;
								$reload[$field]["error"][$idx] = 0;
								$reload[$field]["size"][$idx] = filesize($full_file);
							}
						} else {
							if (!$_FILES[$field]["tmp_name"]) {
								$reload[$field]["tmp_name"] = $full_file;
								$reload[$field]["name"] = $name;
								$reload[$field]["type"] = $type;
								$reload[$field]["error"] = 0;
								$reload[$field]["size"] = filesize($full_file);
							}
						}
					}
				}
			}
		} else {
		    throw new ilDclException('temp dir path "' . $temp_path . '" is not a directory');
        }

		return $reload;
	}


	/**
	 * Get reloaded files
	 *
	 * @return mixed
	 */
	public function getReloadedFiles() {
		return $this->reloaded_files;
	}


	/**
	 * Cleanup temp-files
	 *
	 * @param $hash
	 * @param $user
	 */
	public function cleanupTempFiles($hash, $user) {
		$files = glob(ilUtil::getDataDir() . "/temp/" . $user . "~~" . $hash . "~~*");

		foreach ($files as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}
}