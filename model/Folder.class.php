<?php
/**
 * Represents a folder
 */
final class Folder extends ModelBase {
	/**
	 * ID
	 * @var int
	 */
	private $id = NULL;
	
	/**
	 * Parent folder ID
	 * @var int
	 */
	private $pid = NULL;

	/**
	 * User ID
	 * @var int
	 */
	private $uid = NULL;

	
	/**
	 * Name
	 * @var string
	 */
	private $name;
	
	/**
	 * Path (for nice URLs)
	 * @var string
	 */
	private $path;
	
	/**
	 * Folders
	 * @var object[]
	 */
	private $folders = array();
	
	/**
	 * Files
	 * @var object[]
	 */
	private $files = array();

	private $readonly = false;
	
	/**
	 * Invalid folder names
	 * @const string
	 */
	const INVALID_NAMES = 'add,edit,delete';
	
	const READONLY = 'id,pid,name,path,folders,files';
	
	/**
	 * Constructor
	 * @param int ID
	 */
	public function __construct() { }
	
	protected function assign(array $row) {
		$this->isNewRecord = false;
		
		$this->id	= $row['_id'];
		$this->pid	= $row['parent'];
		$this->name	= $row['name'];	
		$this->uid  = $row['user_ID'];

		if(!System::getUser()->isAdmin && (System::getUser()->uid == NULL || $this->uid != System::getUser()->uid)) {
			$readonly = true;
		}
		
		$this->createPath();
	}
	
	public function save() {
		$data = array(
			':parent'	=> $this->pid,
			':name'		=> $this->name
		);
		
		if($this->name == '') {
			throw new InvalidFolderNameException();	
		}
		
		$query = 'SELECT _id FROM folders WHERE name = :name AND ';
		#die("{\"success\":false,\"message\":\"".var_export($query,true)."\"}");
		$params = array(':name' => $this->name);
		if($this->pid===NULL) {
			$query .= 'parent IS NULL AND ';
		} else {
			$query .= 'parent = :parent AND ';
			$params[':parent'] = $this->pid;
		}
		if($this->id===NULL) {
			$query .= '_id IS NOT NULL ';
		} else {
			$query .= '_id = :id ';
			$params[':id'] = $this->id;
		}
		$sql = System::getDatabase()->prepare($query);
		System::getDatabase()->beginTransaction();
		$sql->execute($params);
		
		if($sql->rowCount() != 0) {
			System::getDatabase()->rollBack();
			throw new FolderAlreadyExistsException();	
		}
		if($this->isNewRecord) {
			$data[':uid'] = System::getUser()->uid;
			
			$sql = System::getDatabase()->prepare('INSERT INTO folders (parent, user_ID, name) VALUES (:parent, :uid, :name)');
			$sql->execute($data);
			
			$this->id = System::getDatabase()->lastInsertId();
			$this->createPath();
		} else {
			$data[':id'] = $this->id;
			
			$sql = System::getDatabase()->prepare('UPDATE folders SET parent = :parent, name = :name WHERE _id = :id');
			$sql->execute($data);
		}
		System::getDatabase()->commit();
	}
	
	
	private function createPath() {
		// Generate path
		$path = array();
		
		$f = $this;
        
		while($f != NULL && $f->id != NULL) {
			if($f->name != '') {
				$path[] = Folder::nameToURL($f->name);
			}
			
			$f = Folder::find('_id', $f->pid);
		}
		
		$this->path = implode('/', array_reverse($path));	
	}
	
	/**
	 * Getter
	 */
	public function __get($property) {
		if(property_exists($this, $property)) {
			return $this->$property;	
		}
	}
	
	/**
	 * Setter
	 */
	public function __set($property, $value) {
		if(!in_array($property, explode(',', File::READONLY))) {
			if($property == 'parent' && $value instanceof Folder) {
				$this->pid = $value->id;
				return;	
			}
			
			$this->$property = $value;
			
			$this->createPath();
		} else {
			throw new InvalidArgumentException('Property '.$property.' is readonly');
		}
	}
	
	/**
	 * Loads folders
	 */
	public function loadFolders() {
		$folders = Folder::find('parent', $this->id);
		
		if($folders == NULL) {
			$this->folders = array();
		} else if(!is_array($folders)) {
			$this->folders = array($folders);
		} else {
			$this->folders = $folders;	
			
			if($this->folders != NULL) {
				usort($this->folders, array('Folder', 'compare'));
			}
		}
		//$this->folders = $folders;
	}
	
	/**
	 * Loads files
	 */
	public function loadFiles() {
		$files = File::find('folder_ID', $this->id);
		if($files == NULL) {
			$this->files = array();
		} else if(!is_array($files) && $files != NULL) {
			$this->files = array($files);
		} else {
			$this->files = $files;
			
			if($this->files != NULL) {
				usort($this->files, array('File', 'compare'));
			}
		}
	}
	
	/**
	 * Downloads whole folder as zip
	 */
	
	public function downloadAsZip() {
		
		ob_start();
		
		$this->loadFiles();
		$this->loadFolders();
		
		$archive = new ZipArchive();
		
		$zipfile = tempnam(sys_get_temp_dir(), "FIL");
		
		$archive->open($zipfile, ZipArchive::OVERWRITE);
		$this->addToArchive($archive, "", true);
		$archive->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$this->name.'.zip"');
        header('Content-Length: '.filesize($zipfile));
    	
		if(!DEV_MODE) ob_clean();
		
        readfile($zipfile);
		unlink($zipfile);
		exit();
	}
	
	private function addToArchive(ZipArchive $archive, $dir, $root = false) {
		$this->loadFiles();
		$this->loadFolders();
		
		if(!$root) {
			$archive->addEmptyDir($dir . $this->name);
			$dir .= $this->name . "/";
		} else {
			// Support if someone download an empty folder
			if((count($this->files) + count($this->folders)) == 0) {
				$archive->addEmptyDir($dir . $this->name);
				return;
			}
		}
		
		if(count($this->files) > 0) {
			foreach ($this->files as $file) {
				$archive->addFile($file->getAbsPath(), $dir . $file->filename);
			}
		}
		
		if(count($this->folders) > 0) {
			foreach ($this->folders as $folder) {
				$folder->addToArchive($archive, $dir);
			}
		}		
	}
	
	/**
	 * Deletes folder
	 */
	public function delete() {		
		/*
		$this->loadFolders();
		$this->loadFiles();
			
		if(count($this->folders) > 0) {
			foreach($this->folders as $folder) {
				$folder->delete();	
			}
		}
		
		if(count($this->files) > 0) {
			foreach($this->files as $file) {
				$file->delete();	
			}
		}
		 */
		if($this->readonly) {
			throw new NotAuthorisedException();
			exit;
		}

		$this->loadFolders();
		$this->loadFiles();
		if(count($this->folders) > 0 || count($this->files) >0) {
			throw new FolderNotEmptyException();
			exit;
		}

		$sql = System::getDatabase()->prepare('DELETE FROM folders WHERE _id = :id');
		$sql->execute(array(':id' => $this->id));

	}
	
	/**
	 * Moves a folder
     * @param object Folder (target)
	 */
	public function move(Folder $target) {
		if($this->readonly) {
			throw new NotAuthorisedException();
			exit;
		}
		if($this->pid != $target->id) {
			if($target->id == $this->id) {
				throw new InvalidArgumentException('Target folder must not be actual folder');	
			}
			
			if($target->isSubfolderOf($this)) {
				throw new InvalidArgumentException('Target folder must not be child of actual folder');	
			}
			
			$this->pid = $target->id;
		}
	}
	
	/**
	 * Checks if current folder is subfolder of given folder
	 * @param object Folder
	 * @return bool Result
	 */
	private function isSubfolderOf(Folder $folder) {
		if($this->id == NULL) {
			return false;	
		}
        
        
		$f = Folder::find('_id', $this->pid);
        if($this->pid == $folder->id || ($f != NULL && $f->isSubfolderOf($folder))) {
			return true;	
		}
        
		return false;
	}
	
	/**
	 * Returns an object used for JSON encoding
	 * @param boolean Determines whether response should include directory listing or not
	 * @return object
	 */
	public function toJSON($dirListing = false) {
		$obj = new Object();
		
		$obj->id	= $this->id;
		$obj->pid	= $this->pid;
		$obj->name	= $this->name;
		
		$obj->path  = $this->path;
		
		$obj->url	= Router::getInstance()->build('BrowserController', 'show', $this);
		
		if($dirListing == true) {
			$this->loadFiles();
			$this->loadFolders();
			
			$obj->files = array();
			$obj->folders = array();
			
			if(count($this->files) > 0) {
				foreach($this->files as $file) {
					$obj->files[] = $file->toJSON();	
				}
			}
			
			if(count($this->folders) > 0) {
				foreach($this->folders as $folder) {
					$obj->folders[] = $folder->toJSON();	
				}
			}
		}
		
		return $obj;
	}
	
	
	public function getContentSize() {
		$this->loadFiles();
		$this->loadFolders();
		
		$size = 0;
		
		if(count($this->files) > 0) {
			foreach ($this->files as $key => $file) {
				$size += $file->size;
			}
		}
		
		if(count($this->folders) > 0) {
			foreach ($this->folders as $key => $folder) {
				$size += $folder->getContentSize();
			}
		}
		
		return $size;
		
	}
	
	/**
	 * Gets a list of folders incl. subfolders
	 * @return object[]
	 */
	public static function getAll($parent = NULL, $keys = true, $exclude = array(), $prefix = ' / ') {	
		$list = array();
		
		// Add root folder if necessary
		if($parent == NULL) {
			$list[] = $prefix;
		}
		
		$folders = Folder::find('parent', $parent);
		
		if($folders != NULL) {
			if(!is_array($folders)) {
				$folders = array($folders);	
			}
			
			if(count($folders) > 0) {
				foreach($folders as $folder) {
					if(!in_array($folder->id, $exclude)) {
						if($keys == true) {
							$list[$folder->id] = $prefix . $folder->name;	
						} else {
							$list[] = $prefix . $folder->name;	
						}
						
						$list += Folder::getAll($folder->id, $keys, $exclude, $prefix . $folder->name . ' / ');
					}
				}
			}
		}
		
		return $list;
	}
	
	public static function find($column = '*', $value = NULL, array $options = array()) {
		if($column == '_id' && $value === NULL) {
			return new Folder();
		}		
		
		$query = 'SELECT * FROM folders';
		//$params = array(':uid' => System::getUser()->uid);
		$params = array();

		if($column != '*' && strlen($column) > 0) {
			if($value == NULL) {
				//$query .= ' WHERE '.Database::makeTableOrColumnName($column).' IS NULL AND user_ID = :uid';
				$query .= ' WHERE '.Database::makeTableOrColumnName($column).' IS NULL';
			} else {
				//$query .= ' WHERE '.Database::makeTableOrColumnName($column).' = :value AND user_ID = :uid';
				$query .= ' WHERE '.Database::makeTableOrColumnName($column).' = :value';
				$params[':value'] = $value;
			}
		} else {
			$query .= ' WHERE user_ID = :uid';	
		}

		if(isset($options['orderby']) && isset($options['sort'])) {
			$query .= ' ORDER BY '.Database::makeTableOrColumnName($options['orderby']).' ' . strtoupper($options['sort']);
		}
		
		if(isset($options['limit'])) {
			$query .= ' LIMIT ' . $options['limit'];
		}
		
		$sql = System::getDatabase()->prepare($query);
		$sql->execute($params);
		
		if($sql->rowCount() == 0) {			
			return NULL;
		} else if($sql->rowCount() == 1) {
			$folder = new Folder();
			$folder->assign($sql->fetch());
			
			return $folder;
		} else {
			$list = array();
			
			while($row = $sql->fetch()) {
				$folder = new Folder();
				$folder->assign($row);
				
				$list[] = $folder;	
			}
			
			return $list;
		}
		/*
		$list = array();
		while($row = $sql->fetch()) {
			$folder = new Folder();
			$folder->assign($row);
			$list[] = $folder;	
		}
		if(count($list) == 0)
			throw new FolderNotFoundException();
		return $list;*/
	}
	
	public static function compare($a, $b) {
		return strcmp($a->name, $b->name);	
	}
	
	public static function nameToURL($name) {
		$name	= preg_replace('~([^A-Za-z0-9-])~s', '-', $name);
		$name	= preg_replace('~-+~', '-', $name);
		
		return $name;
	}


}
;?>
